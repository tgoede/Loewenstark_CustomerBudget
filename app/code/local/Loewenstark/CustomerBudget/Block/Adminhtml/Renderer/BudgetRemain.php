<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Loewenstark_CustomerBudget_Block_Adminhtml_Renderer_BudgetRemain extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {   
        $store = Mage::app()->getStore();
        
        return (float)($row->getCustomerBudget() - $row->getCustomerBudgetSpent()) . '&nbsp;' . Mage::app()->getLocale()->currency($store->getCurrentCurrencyCode())->getSymbol();
    }
}