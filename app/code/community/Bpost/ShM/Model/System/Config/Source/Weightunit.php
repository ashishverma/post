<?php
/**
 * Created by PHPro
 *
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_System_Config_Source_Weightunit
 */
class Bpost_ShM_Model_System_Config_Source_Weightunit
{
    /**
     * Options getter.
     * Returns an option array for unit weight to pass to the webservice.
     *
     * @return array
     *
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1000, 'label' => Mage::helper('bpost_shm')->__('kg')),
            array('value' => 1, 'label' => Mage::helper('bpost_shm')->__('g')),
            array('value' => 453.592, 'label' => Mage::helper('bpost_shm')->__('lb')),
        );
    }

    /**
     * Get options in "key-value" format.
     * Returns an array for unit weight to pass to the webservice. (Magento basically expects both functions)
     *
     * @return array
     *
     */
    public function toArray()
    {
        return array(
            '1000' => Mage::helper('bpost_shm')->__('kg'),
            '1' => Mage::helper('bpost_shm')->__('g'),
            '453.592' => Mage::helper('bpost_shm')->__('lb')
        );
    }

}