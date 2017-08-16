<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
/**
 * Class Bpost_ShM_Model_System_Config_Source_Product_Bpack
 */
class Bpost_ShM_Model_System_Config_Source_Product_Bpack
{
    /**
     * Options getter.
     * Returns an option array for Shipping cost handler.
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('bpost_shm')->__('bpack 24h Business')),
            array('value' => 0, 'label' => Mage::helper('bpost_shm')->__('bpack 24h Pro')),
        );
    }

    /**
     * Get options in "key-value" format.
     * Returns an array for Shipping cost handler. (Magento basically expects both functions)
     *
     * @return array
     *
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('bpost_shm')->__('bpack 24h Pro'),
            1 => Mage::helper('bpost_shm')->__('bpack 24h Business'),
        );
    }

}