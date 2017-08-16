<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
if (!Mage::helper('core')->isModuleEnabled('Bpost_ShippingManager')) {
    $installer = $this;
    $installer->startSetup();

    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_drop_date', "date null");

    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_status', "varchar(255) null default ''");
    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_shipment_automated', "bool null default 0");

    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_drop_date', "date null");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_status', "varchar(255) null default ''");


    $installer->endSetup();
}
