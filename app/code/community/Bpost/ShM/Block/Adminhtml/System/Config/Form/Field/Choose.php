<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Choose
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Choose extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Set choose delivery date value to No when display delivery date is set to no
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return parent
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $displayDeliveryDate = (bool)$configHelper->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId());

        if(!$displayDeliveryDate) {
            $element->setValue(0);
        }

        return parent::_getElementHtml($element);
    }
}