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

    //create the return label table
    $tableReturnLabel = $installer->getConnection()->newTable($installer->getTable('bpost_shm/bpost_country'))
        ->addColumn('country_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
            'identity' => true,
        ), 'country unique ID')
        ->addColumn('country_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 2, array(
            'nullable' => false,
            'default' => "",
        ), "country code")
        ->setComment('bpost country code');
    $installer->getConnection()->createTable($tableReturnLabel);
    $installer->endSetup();

}