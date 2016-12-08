<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Loewenstark_CustomerBudget_Model_Resource_Setup
extends Mage_Catalog_Model_Resource_Setup
{
    /**
     * 
     * @return Loewenstark_CustomerBudget_Model_Resource_Setup
     */
    public function startSetup() {
        parent::startSetup();
        return $this;
    }
    
    /**
     * @param int  $customerId
     * @param null $grandTotal
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getCustomerOrderCollection($customerId, $grandTotal = null)
    {    
        $currentYearStart = date('Y') . '-01-01 00:00:00';
        $currentYearEnd = date('Y') . '-12-31 23:59:59';
        /*
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->addFieldToFilter('created_at', array('from' => $currentYearStart, 'to' => $currentYearEnd))

            ->setOrder('created_at', 'desc')
        ;
        if ($grandTotal && $grandTotal > 0) {
            $orderCollection->addFieldToFilter('base_grand_total', array ('gteq' => $grandTotal));
        }*/

        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('grand_total')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
            ->addFieldToFilter('created_at', array('from' => $currentYearStart, 'to' => $currentYearEnd))
            ->getColumnValues('grand_total')  
            //->setOrder('created_at', 'desc')
        ;

        //var_dump($orderCollection);
        return $orderCollection;
    }
      
    /**
     * Prepare database after install/upgrade
     * added config reinit to endSetup
     * 
     * @return Loewenstark_CustomerBudget_Model_Resource_Setup
     */
    public function endSetup()
    {
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
        return parent::endSetup();
    }
}