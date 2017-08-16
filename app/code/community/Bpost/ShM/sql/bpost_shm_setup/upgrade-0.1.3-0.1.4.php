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
    $tableHolidays = $installer->getConnection()->newTable($installer->getTable('bpost_shm/bpost_holidays'))
        ->addColumn('holiday_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
            'identity' => true,
        ), 'holiday unique ID')
        ->addColumn('date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable' => true,
            'default' => null,
        ), "date")
        ->setComment('bpost holiday list');
    $installer->getConnection()->createTable($tableHolidays);

    //import 2015 holidays
    $installer->run("
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-01-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-04-06');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-05-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-05-14');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-05-25');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-07-21');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-08-15');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-11-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-01-11');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2015-12-25');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-01-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-03-28');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-05-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-05-05');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-05-16');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-07-21');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-08-15');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-11-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-01-11');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2016-01-25');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-01-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-04-17');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-05-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-05-25');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-06-05');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-07-21');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-08-15');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-11-01');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-01-11');
        INSERT INTO {$installer->getTable('bpost_shm/bpost_holidays')} (date) values ('2017-01-25');
    ");

    $installer->endSetup();

}