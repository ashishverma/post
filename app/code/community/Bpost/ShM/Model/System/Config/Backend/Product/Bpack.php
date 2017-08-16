<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 *
 * Class Bpost_ShM_Model_System_Config_Backend_Product_Bpack
 */
class Bpost_ShM_Model_System_Config_Backend_Product_Bpack extends Mage_Core_Model_Config_Data
{
    /**
     * After enable flat category required reindex
     *
     * @return Bpost_ShM_Model_System_Config_Backend_Product_Bpack
     */
    protected function _afterSave()
    {
        if ($this->isValueChanged() && true == $this->getValue()) {
            Mage::getConfig()->saveConfig('carriers/bpost_homedelivery/second_presentation', true);
            Mage::getConfig()->saveConfig('carriers/bpost_homedelivery/second_presentation_from', 0);
        }

        return $this;
    }
}
