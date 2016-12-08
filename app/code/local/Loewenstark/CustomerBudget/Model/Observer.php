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
    
    
    //private static $customerBudget;
    //private static $currentBudgetSpent;
    //private static $newBudgetSpent;
    //private static $newBudgetRemain;
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addBudgetColumn(Varien_Event_Observer $observer)
    {   
        $store = Mage::app()->getStore();
        $grid = $observer->getBlock();
        if ($grid instanceof Mage_Adminhtml_Block_Customer_Grid) {
            $grid->addColumn(
                'customer_budget',
                array(
                    'header' => Mage::helper('customerbudget')->__('Customer Budget'),
                    'index'  => 'customer_budget',
                    'type'   => 'currency',
                    'currency_code' => $store->getBaseCurrency()->getCode()
                )
            );
            $grid->addColumn(
                'customer_budget_spent',
                array(
                    'header' => Mage::helper('customerbudget')->__('Customer Budget Spent'),
                    'index'  => 'customer_budget_spent',
                    'type'   => 'currency',
                    'currency_code' => $store->getBaseCurrency()->getCode()
                )
            );
            $grid->addColumn(
                'customer_budget_remain',
                array(
                    'header' => Mage::helper('customerbudget')->__('Customer Budget Remain'),
                    'index'  => 'customer_budget_remain',
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
    public function beforeCollectionLoad(Varien_Event_Observer $observer)
    {
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
    public function beforeItemsUpdateCartCheckout(Varien_Event_Observer $observer)
    {
        $check = $this->checkCustomerBudget($observer);
        
        if(!$check) {
            $returnToProductPage = false;
            $this->blockAddToCartAction($returnToProductPage);
        }
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterAddProductCartCheckout(Varien_Event_Observer $observer)
    {   
        $check = $this->checkCustomerBudget($observer);
        
        if(!$check) {
            $returnToProductPage = true;
            $this->blockAddToCartAction($returnToProductPage);
        }            
        
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterSaveCartCheckout(Varien_Event_Observer $observer)
    {
        $check = $this->checkCustomerBudget($observer);
        
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function completeProductAddCartCheckout(Varien_Event_Observer $observer)
    {
        $check = $this->checkCustomerBudget($observer);        
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterPlaceOrderSales(Varien_Event_Observer $observer)
    {
        
        $order = $observer->getOrder();
        
        $total = $order->getData('grand_total');
        
        $customer = $this->getCustomer($order);
        
        $customerBudget = $customer->getData('customer_budget');
        
        $currentBudgetSpent = $this->getCustomerBudgetSpent($customer);
        
        $newBudgetSpent = $this->getNewBudgetSpent($currentBudgetSpent, $total);
        
        $customer->setData('customer_budget_spent', $newBudgetSpent);
        
        $customer->save();
        
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addCartCheckoutPredispatchAction($event)
    {
        $controller = $event->getControllerAction();
        
        /**
         * check budget on product page
         */
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $cart->setHasError(false);
        
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
        if(!empty($product->getSpecialPrice())) {
            $price = $product->getSpecialPrice();
        } else {
            $price = $product->getPrice();        
        }
            
        
        /**
         * get customer data
         */
        $customer = $this->getCustomer($cart);
        
        if($customer){
            
            $customerBudget = $this->getCustomerBudget($customer);
        
            $currentBudgetSpent = $this->getCustomerBudgetSpent($customer);

            $newBudgetSpent = $this->getNewBudgetSpent($currentBudgetSpent, $total);

            $newBudgetRemain = $customerBudget - $newBudgetSpent - $price;

            $currentControllerAction = Mage::app()->getRequest()->getRouteName() . '_' . 
                                        Mage::app()->getRequest()->getControllerName() . '_' . 
                                        Mage::app()->getRequest()->getActionName();
            
            if (($newBudgetRemain) < 0 ) {

                Mage::getSingleton('core/session')->getMessages(true);
                $message = Mage::getModel('core/message_error', 'You\'ve exceeded your budget by ' . $newBudgetRemain . $this->getCurrencySymbol());

                Mage::getSingleton('core/session')->addUniqueMessages( $message );
                
                $cart->setHasError(true);
                
                $controller->getRequest()->setParam('return_url',$product->getProductUrl());
                
            }
            
            if ($currentControllerAction == 'checkout_cart_add'){
                if (($newBudgetRemain/$customerBudget) <= 0.1) {
                    Mage::getSingleton('core/session')->getMessages(true);
                    $message = Mage::getModel('core/message_notice', 'Beware: Only ' . $newBudgetRemain . $this->getCurrencySymbol() . ' of your budget remains!');
                    Mage::getSingleton('core/session')->addUniqueMessages( $message );
                } else {
                    Mage::getSingleton('core/session')->getMessages(true);
                    $message = Mage::getModel('core/message_success', $newBudgetRemain . $this->getCurrencySymbol() . ' of your budget remains!');
                    Mage::getSingleton('core/session')->addUniqueMessages( $message );
                }
            }
        }
        else {
        
            return "customer not logged in";
    
        }
        return;
    }

    
    
    private function blockAddToCartAction($returnToProductPage = false) {
        Mage::app()->getRequest()->setParam('product', false);
        if($returnToProductPage){
            $product = Mage::getModel('catalog/product')
                        ->load(Mage::app()->getRequest()->getParam('product', 0));

            Mage::app()->getResponse()->setRedirect($product->getProductUrl());   
        }
    }
    /**
     * 
     * @param type $observer
     * @return type
     */
    private function checkCustomerBudget($observer)
    {
        $cart = Mage::getSingleton('checkout/session')->getQuote();
        $cart->setHasError(false);
        $total = $cart->getGrandTotal();
        
        $product = Mage::getModel('catalog/product')
                        ->load(Mage::app()->getRequest()->getParam('product', 0));
        
        if(!empty($product->getSpecialPrice())) {
            $price = $product->getSpecialPrice();
        } else {
            $price = $product->getPrice();        
        }
        
        $customer = $this->getCustomer($cart);
        
        if ( $customer ) {
            $customerBudget = $this->getCustomerBudget($customer);
        
            $currentBudgetSpent = $this->getCustomerBudgetSpent($customer);

            $newBudgetSpent = $this->getNewBudgetSpent($currentBudgetSpent, $total);

            $newBudgetRemain = $customerBudget - $newBudgetSpent - $price;

            if (($newBudgetRemain) < 0 ) {
                Mage::getSingleton('core/session')->getMessages(true);
                $message = Mage::getModel('core/message_error', 'You\'ve exceeded your budget by ' . $newBudgetRemain . $this->getCurrencySymbol());

                Mage::getSingleton('core/session')->addUniqueMessages( $message );
                $cart->setHasError(true);
                
                return false;
            }
            $currentControllerAction = Mage::app()->getRequest()->getRouteName() . '_' . 
                                        Mage::app()->getRequest()->getControllerName() . '_' . 
                                        Mage::app()->getRequest()->getActionName();

            if ($currentControllerAction == 'checkout_cart_index'){
                
                
                if (($newBudgetRemain/$customerBudget) <= 0.1) {
                    //if ratio is less than 10% 'warning'
                    Mage::getSingleton('core/session')->getMessages(true);
                    $message = Mage::getModel('core/message_notice', 'Beware: Only ' . $newBudgetRemain . $this->getCurrencySymbol() . ' of your budget remains!');
                    Mage::getSingleton('core/session')->addUniqueMessages( $message );
                } else {
                    Mage::getSingleton('core/session')->getMessages(true);
                    $message = Mage::getModel('core/message_success', $newBudgetRemain . $this->getCurrencySymbol() . ' of your budget remains!');
                    Mage::getSingleton('core/session')->addUniqueMessages( $message );
                }
            }
            return true;
        }
        
        return;
    }
    
    /**
     * 
     * @param type $entity
     * @return type
     */
    private function getCustomer($entity)
    {
        $customerId = $entity->getCustomerId(); //evtl. Id
        return Mage::getModel('customer/customer')->load($customerId);
    }
    
    /**
     * 
     * @param type $customer
     * @return type
     */
    private function getCustomerBudget($customer)
    {
        return $customer->getData('customer_budget');
    }
    
    /**
     * 
     * @param type $customer
     * @return type
     */
    private function getCustomerBudgetSpent($customer)
    {
        return $customer->getData('customer_budget_spent');
    }
    
    /**
     * 
     * @param type $currentBudgetSpent
     * @param type $total
     * @return type
     */
    private function getNewBudgetSpent($currentBudgetSpent, $total)
    {
        return $currentBudgetSpent + $total;
    }
    
    /**
     * 
     * @return type
     */
    private function getCurrencySymbol()
    {
        $store = Mage::app()->getStore();
        return '&nbsp;' . Mage::app()->getLocale()->currency($store->getCurrentCurrencyCode())->getSymbol();
    }
}
