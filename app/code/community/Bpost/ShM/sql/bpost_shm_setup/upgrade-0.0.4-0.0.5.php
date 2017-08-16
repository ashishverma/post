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
    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_label_exported', "int(11) null");
    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_label_path', "varchar(255) null default ''");
    $installer->getConnection()->addColumn($installer->getTable('sales/shipment'), 'bpost_tracking_url', "varchar(255) null default ''");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_label_exported', "bool null default 0");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_label_exists', "bool null default 0");

    $installer->endSetup();
}
