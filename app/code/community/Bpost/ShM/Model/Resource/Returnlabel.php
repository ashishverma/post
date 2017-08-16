<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Resource_Returnlabel
 */
class Bpost_ShM_Model_Resource_Returnlabel extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Sets model primary key.
     */
    protected function _construct()
    {
        $this->_init("bpost_shm/returnlabel", "label_id");
    }
}
