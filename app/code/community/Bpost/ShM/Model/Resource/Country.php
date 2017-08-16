<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Resource_Country
 */
class Bpost_ShM_Model_Resource_Country extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Sets model primary key.
     */
    protected function _construct()
    {
        $this->_init("bpost_shm/bpost_country", "country_id");
    }

    /**
     * we truncate our table
     */
    public function truncateTable(){
        $this->_getWriteAdapter()->query('TRUNCATE TABLE '.$this->getMainTable());
        return $this;
    }
}
