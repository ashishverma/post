<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Holidays
 */
class Bpost_ShM_Model_Holidays extends Mage_Core_Model_Abstract
{
    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("bpost_shm/holidays");
    }
}
