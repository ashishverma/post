<?php
/**
 * Created by PHPro
 *
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

if (!Mage::helper('core')->isModuleEnabled('Bpost_ShippingManager')) {
    /* @var $installer Mage_Core_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();

    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_return_barcode', "varchar(255) null default ''");

    $installer->endSetup();
}