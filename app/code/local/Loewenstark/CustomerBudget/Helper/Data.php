<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Loewenstark_CustomerBudget_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isActive()
    {
        return (bool)Mage::getStoreConfigFlag("customer/customerbudget/active");
    }
}
