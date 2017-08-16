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
    $tableReturnLabel = $installer->getConnection()->newTable($installer->getTable('bpost_shm/returnlabel'))
        ->addColumn('label_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
            'identity' => true,
        ), 'Label unique ID')
        ->addColumn('label_barcode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable' => false,
            'default' => "",
        ), "Barcode of the Label")
        ->addColumn('label_pdf_path', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable' => false,
        ), "Local path of the pdf file")
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'nullable' => false,
        ), "Id of the order")
        ->addColumn('date_created', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(), 'Creation Date')
        ->setComment('bpost Shipping Return Labels');
    $installer->getConnection()->createTable($tableReturnLabel);

    $installer->endSetup();

}