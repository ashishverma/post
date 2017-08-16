<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */
if (!Mage::helper('core')->isModuleEnabled('Bpost_ShippingManager')) {
$installer = $this;
$installer->startSetup();
$installer->run("CREATE TABLE IF NOT EXISTS {$installer->getTable('bpost_tablerates_pickuppoint')} (
 `pk` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
 `website_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Website Id',
 `dest_country_id` varchar(4) NOT NULL DEFAULT '0' COMMENT 'Destination coutry ISO/2 or ISO/3 code',
 `dest_region_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Destination Region Id',
 `dest_zip` varchar(10) NOT NULL DEFAULT '*' COMMENT 'Destination Post Code (Zip)',
 `condition_name` varchar(20) NOT NULL COMMENT 'Rate Condition name',
 `condition_value` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Rate condition value',
 `price` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Price',
 `cost` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Cost',
 PRIMARY KEY (`pk`),
 UNIQUE KEY `QIMuM8yoQUkmjsNM6FmJFjBrOZHNJAEu8kP` (`website_id`,`dest_country_id`,`dest_region_id`,`dest_zip`,`condition_name`,`condition_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bpost Pickuppoint Tablerate'");
$installer->endSetup();
}