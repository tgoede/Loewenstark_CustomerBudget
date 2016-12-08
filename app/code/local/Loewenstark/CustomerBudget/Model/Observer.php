<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Observer
 *
 * @author tgoede
 */
class Loewenstark_CustomerBudget_Model_Observer extends Mage_Customer_Model_Observer {
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addBudgetColumn(Varien_Event_Observer $observer) {
        $store = Mage::app()->getStore();
        $grid = $observer->getBlock();
        if ($grid instanceof Mage_Adminhtml_Block_Customer_Grid) {
            $grid->addColumn(
                    'customer_budget', array(
                'header' => Mage::helper('customerbudget')->__('Customer Budget'),
                'index' => 'customer_budget',
                'type' => 'currency',
                'currency_code' => $store->getBaseCurrency()->getCode()
                    )
            );
            $grid->addColumn(
                    'customer_budget_spent', array(
                'header' => Mage::helper('customerbudget')->__('Customer Budget Spent'),
                'index' => 'customer_budget_spent',
                'type' => 'currency',
                'currency_code' => $store->getBaseCurrency()->getCode()
                    )
            );
            $grid->addColumn(
                    'customer_budget_remain', array(
                'header' => Mage::helper('customerbudget')->__('Customer Budget Remain'),
                'index' => 'customer_budget_remain',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'column_css_class' => 'a-right',
                'renderer' => new Loewenstark_CustomerBudget_Block_Adminhtml_Renderer_BudgetRemain()
                    )
            );
        }
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     * @return type
     */
    public function beforeCollectionLoad(Varien_Event_Observer $observer) {
        $collection = $observer->getCollection();
        if (!isset($collection)) {
            return;
        }
        if ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            $collection->addAttributeToSelect('customer_budget');
            $collection->addAttributeToSelect('customer_budget_spent');
            $collection->addAttributeToSelect('customer_budget_remain');
        }
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function beforeItemsUpdateCartCheckout(Varien_Event_Observer $observer) {
        $check = $this->checkCustomerBudget($observer);

        if (!$check) {
            $returnToProductPage = false;
            $this->blockAddToCartAction($returnToProductPage);
        }
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterAddProductCartCheckout(Varien_Event_Observer $observer) {
        $check = $this->checkCustomerBudget($observer);

        if (!$check) {
            $returnToProductPage = true;
            $this->blockAddToCartAction($returnToProductPage);
        }
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterSaveCartCheckout(Varien_Event_Observer $observer) {
        $check = $this->checkCustomerBudget($observer);
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function completeProductAddCartCheckout(Varien_Event_Observer $observer) {
        $check = $this->checkCustomerBudget($observer);
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterPlaceOrderSales(Varien_Event_Observer $observer) {

        $order = $observer->getOrder();

        $total = $order->getData('grand_total');

        $customer = $this->getCustomer($order);

        if ($customer) {
            $customerBudgetData = array();
            $this->getCustomerBudgetData($customerBudgetData, $customer, $cart);

            $customer->setData('customer_budget_spent', $customerBudgetData['newBudgetSpent']);
            $customer->save();
        }
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addCartCheckoutPredispatchAction($event) {
        $controller = $event->getControllerAction();
        $action = 'checkout_cart_add';
            
        /**
         * check budget on product page
         */
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $cart->setHasError(false);

        /**
         * get customer data
         */
        $customer = $this->getCustomer($cart);

        if ($customer) {
            /**
             * get customer budget data
             */
            $customerBudgetData = array();
            $this->getCustomerBudgetData($customerBudgetData, $customer, $cart);
            return $this->handleCustomerBudgetMessages($customerBudgetData, $cart, $action, $controller); 
        }
        return;
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    private function checkCustomerBudget($observer) {
        $action = "checkout_cart_index";            
        
        /**
         * check budget on product page
         */
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $cart->setHasError(false);

        /**
         * get customer data
         */
        $customer = $this->getCustomer($cart);

        if ($customer) {
            /**
             * get customer budget data
             */
            $customerBudgetData = array();
            $this->getCustomerBudgetData($customerBudgetData, $customer, $cart);
            return $this->handleCustomerBudgetMessages($customerBudgetData, $cart, $action);      
        }
        return;
    }

    /**
     * 
     * @param type $customerBudgetData
     * @param type $cart
     * @param type $action
     * @param type $controller
     * @return boolean
     */
    private function handleCustomerBudgetMessages(&$customerBudgetData, &$cart, $action, &$controller = null) {
        if (($customerBudgetData['newBudgetRemain']) < 0) {
            Mage::getSingleton('core/session')->getMessages(true);
            $message = Mage::getModel('core/message_error', 'You\'ve exceeded your budget by ' . $customerBudgetData['newBudgetRemain'] . $this->getCurrencySymbol());

            Mage::getSingleton('core/session')->addUniqueMessages($message);
            $cart->setHasError(true);
            
            if($controller){
                $controller->getRequest()->setParam('return_url', $product->getProductUrl());
            }
            return false;
        }

        if ($this->getCurrentActionCtrl() == $action) {

            if (($customerBudgetData['newBudgetRemain'] / $customerBudgetData['customerBudget']) <= 0.1) {
                //if ratio is less than 10% 'warning'
                Mage::getSingleton('core/session')->getMessages(true);
                $message = Mage::getModel('core/message_notice', 'Beware: Only ' . $customerBudgetData['newBudgetRemain'] . $this->getCurrencySymbol() . ' of your budget remains!');
                Mage::getSingleton('core/session')->addUniqueMessages($message);
            } else {
                Mage::getSingleton('core/session')->getMessages(true);
                $message = Mage::getModel('core/message_success', $customerBudgetData['newBudgetRemain'] . $this->getCurrencySymbol() . ' of your budget remains!');
                Mage::getSingleton('core/session')->addUniqueMessages($message);
            }
            return true;
        }        
    }
    /**
     * 
     * @param type $customerBudgetData
     * @param type $customer
     * @param type $cart
     * @return string
     */
    private function getCustomerBudgetData(&$customerBudgetData, $customer, $cart) {
        /**
         * current cart grand total
         */
        $total = $cart->getGrandTotal();

        /**
         * get product data
         */
        $product = Mage::getModel('catalog/product')
                ->load(Mage::app()->getRequest()->getParam('product', 0));

        /**
         * check for special price and get price/special price
         */
        if (!empty($product->getSpecialPrice())) {
            $price = $product->getSpecialPrice();
        } else {
            $price = $product->getPrice();
        }

        if ($customer) {
            $customerBudgetData['customerBudget'] = $this->getCustomerBudget($customer);

            $customerBudgetData['currentBudgetSpent'] = $this->getCustomerBudgetSpent($customer);

            $customerBudgetData['newBudgetSpent'] = $this->getNewBudgetSpent($customerBudgetData['currentBudgetSpent'], $total);

            $customerBudgetData['newBudgetRemain'] = $customerBudgetData['customerBudget'] - $customerBudgetData['newBudgetSpent'] - $price;
        } else {
            return "customer not logged in";
        }
    }

    /**
     * 
     * @param type $returnToProductPage
     */
    private function blockAddToCartAction($returnToProductPage = false) {
        Mage::app()->getRequest()->setParam('product', false);
        if ($returnToProductPage) {
            $product = Mage::getModel('catalog/product')
                    ->load(Mage::app()->getRequest()->getParam('product', 0));

            Mage::app()->getResponse()->setRedirect($product->getProductUrl());
        }
    }

    /**
     * 
     * @return type
     */
    private function getCurrentActionCtrl() {
        return Mage::app()->getRequest()->getRouteName() . '_' .
                Mage::app()->getRequest()->getControllerName() . '_' .
                Mage::app()->getRequest()->getActionName();
    }

    /**
     * 
     * @param type $entity
     * @return type
     */
    private function getCustomer($entity) {
        $customerId = $entity->getCustomerId(); //evtl. Id
        return Mage::getModel('customer/customer')->load($customerId);
    }

    /**
     * 
     * @param type $customer
     * @return type
     */
    private function getCustomerBudget($customer) {
        return $customer->getData('customer_budget');
    }

    /**
     * 
     * @param type $customer
     * @return type
     */
    private function getCustomerBudgetSpent($customer) {
        return $customer->getData('customer_budget_spent');
    }

    /**
     * 
     * @param type $currentBudgetSpent
     * @param type $total
     * @return type
     */
    private function getNewBudgetSpent($currentBudgetSpent, $total) {
        return $currentBudgetSpent + $total;
    }

    /**
     * 
     * @return type
     */
    private function getCurrencySymbol() {
        $store = Mage::app()->getStore();
        return '&nbsp;' . Mage::app()->getLocale()->currency($store->getCurrentCurrencyCode())->getSymbol();
    }

}
