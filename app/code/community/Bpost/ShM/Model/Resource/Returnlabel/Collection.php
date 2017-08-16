<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Resource_Returnlabel_Collection
 */
class Bpost_ShM_Model_Resource_Returnlabel_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialises the model, the abstract file will render a collection from it.
     */
    public function _construct()
    {
        $this->_init("bpost_shm/returnlabel");
    }
}
