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
    $installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'bpost_saturday_cost_applied', "bool null");
    $installer->endSetup();
}
