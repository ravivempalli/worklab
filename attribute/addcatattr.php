<?php
require_once '../app/Mage.php';

Mage::app();



$installer = new Mage_Customer_Model_Entity_Setup('core_setup');
$installer->startSetup();
$cat = $installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'menucount', array(
    'group'         => 'General Information',
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'Menu Count',
    'backend'       => '',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => true,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$oAttribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'menucount');
$oAttribute->setData('used_in_forms', array('adminhtml_category'));
$oAttribute->save();
 
$installer->endSetup();

