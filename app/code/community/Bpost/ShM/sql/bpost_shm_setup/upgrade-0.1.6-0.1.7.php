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

    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_delivery_date', "date null");
    $installer->getConnection()->addColumn($installer->getTable('sales/order'), 'bpost_delivery_date', "date null");

    $installer->endSetup();
}
