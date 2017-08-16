<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

if (!Mage::helper('core')->isModuleEnabled('Bpost_ShippingManager')) {
    /* @var $installer Mage_Core_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_reduced_mobility', "bool null default 0");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_reduced_mobility', "bool null default 0");

    $installer->endSetup();
}