<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$this->startSetup();

$this->removeAttribute('customer', 'customer_budget');
$this->removeAttribute('customer', 'customer_budget_spent');
$this->removeAttribute('customer', 'customer_budget_remain');

$entityTypeId     = $this->getEntityTypeId('customer');
$attributeSetId   = $this->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $this->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$this->addAttribute('customer', 'customer_budget', array(
    'type' => 'decimal',
    'label' => 'Budget',
    'input' => 'text',
    'source' => '',
    'backend' => '',
    'frontend' => '',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => '0',
    'default' => '0',
    'user_defined' => '1',
    'apply_to' => 'simple',
    'note' => '',
    'visible' => '1',
    'searchable' => '1',
    'filterable_in_search' => '0',
    'used_in_product_listing' => '1'
));

$this->addAttribute('customer', 'customer_budget_spent', array(
    'type' => 'decimal',
    'label' => 'Budget spent',
    'input' => 'text',
    'source' => '',
    'backend' => '',
    'frontend' => '',
    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => '0',
    'default' => '0',
    'user_defined' => '0',
    'apply_to' => 'simple',
    'note' => '',
    'visible' => '1',
    'searchable' => '1',
    'filterable_in_search' => '0',
    'used_in_product_listing' => '1'
));

$this->addAttribute('customer', 'customer_budget_remain', array(
    'type' => 'decimal',
    'label' => 'Budget remain',
    'input' => 'text',
    'source' => '',
    'backend' => '',
    'frontend' => '',
    'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => '0',
    'default' => '0',
    'user_defined' => '0',
    'apply_to' => 'simple',
    'note' => '',
    'visible' => '1',
    'searchable' => '1',
    'filterable_in_search' => '0',
    'used_in_product_listing' => '1'
));

$used_in_forms = array('adminhtml_customer',
            'customer_account_create',
            'customer_account_edit');

Mage::getSingleton("eav/config")->getAttribute("customer", 'customer_budget')->setData("used_in_forms", $used_in_forms)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100)
        ->save();

Mage::getSingleton("eav/config")->getAttribute("customer", 'customer_budget_spent')->setData("used_in_forms", $used_in_forms)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 0)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100)
        ->save();

Mage::getSingleton("eav/config")->getAttribute("customer", 'customer_budget_remain')->setData("used_in_forms", $used_in_forms)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 0)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100)
        ->save();

$customers = Mage::getModel('customer/customer')->getCollection();

foreach($customers as $customer) {
    $id = $customer->getId();
    
    $orderCollection = $this->getCustomerOrderCollection($id);
    $totalSum = array_sum($orderCollection);
    $customer->setData('customer_budget_spent', $totalSum);
    
    $customer->save();
    
}

$this->endSetup();