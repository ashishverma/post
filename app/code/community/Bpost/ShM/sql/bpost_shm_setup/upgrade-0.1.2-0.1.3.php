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

    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_pickuplocation_id', "varchar(255) null");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_pickuplocation_id', "varchar(255) null");

    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_notification_sms', "varchar(255) null");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_notification_email', "varchar(255) null");
    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_notification_sms', "varchar(255) null");
    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_notification_email', "varchar(255) null");

    $installer->endSetup();
}