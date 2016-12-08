<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Loewenstark_CustomerBudget_Block_Header extends Mage_Page_Block_Html_Header
{
    public function __construct(array $args = array()) {
        //parent::__construct($args);
        
    }
    
    public function getRelative()
    {
        if($this->getCustomer()->getId()) {
            if ($this->getCustomerBudget()) {
                $rel = (int)($this->getNewBudgetSpent()*100/$this->getCustomerBudget());
                return ($rel >= 100) ? 100 : $rel;
            }
            
            else {
                return 100;
            }
        }
    }
    
    public function getBudgetStatus()
    {
        if($this->getCustomer()->getId()) {
                if ($this->getCustomerBudget()) {
                    if(($this->getCustomerBudget() - $this->getNewBudgetSpent()) < 0) {
                        return "error";      
                    }
                    
                    if((int)(($this->getNewBudgetSpent()/$this->getCustomerBudget())*100) >= 90) {
                        return "warning";
                    }
                
                }
                return "success";
            }
        }    
    
    public function getAbsolute()
    {
        if($this->getCustomer()->getId()) {
            
            if(($this->getCustomerBudget() - $this->getNewBudgetSpent()) < 0) {
                return (float)($this->getCustomerBudget() - $this->getNewBudgetSpent()) . $this->getCurrencySymbol();
            }
                
            return (float)($this->getCustomerBudget() - $this->getNewBudgetSpent()) . $this->getCurrencySymbol();
        }
    }
    
    public function showBudget()
    {
        if($this->getCustomer()->getId()) {
            return "block";
        } else {
            return "none";
        }
    }
    
    private function getCurrencySymbol()
    {
        $store = Mage::app()->getStore();
        
        return '&nbsp;' . Mage::app()->getLocale()->currency($store->getCurrentCurrencyCode())->getSymbol();
    }
    
    private function getNewBudgetSpent()
    {
        if($this->getCustomer()->getId()) {
            
            $total = $this->getCart()->getGrandTotal();
            
            $customer = $this->getCustomer();
            $customerBudgetSpent = $customer->getCustomerBudgetSpent();
            return $customerBudgetSpent + $total;
            
        }
    }
    
    private function getCustomerBudget()
    {
        $customer = $this->getCustomer();
        return $customer->getCustomerBudget();
            
    }
    
    private function getCustomer()
    {
        $customer = $this->getCart()->getCustomer();
        
        return $customer;
    }
    
    private function getCart()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
     
    }
}