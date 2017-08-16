<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Logo
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Logo extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $bpostLogoMessage = Mage::helper('bpost_shm')->__('Click here for more information');
        $popupUrl = "'" . $this->getUrl('adminhtml/bpost_shM_config/informationpopup') . "'";
        $html = '<a id="' . $element->getHtmlId() . '" class="bpostInfo" href="#" onclick="openPopup(' . $popupUrl . ',600,400);">';
        $html .= '<span>' . $bpostLogoMessage . '</span> ';
        $html .= '</a>';
        return $html;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';
        $elementHtml = $this->_getElementHtml($element);
        $html .= '<td class="value" colspan="4">';
        $html .= $elementHtml;
        $html .= '</td>';

        return $html;
    }
}