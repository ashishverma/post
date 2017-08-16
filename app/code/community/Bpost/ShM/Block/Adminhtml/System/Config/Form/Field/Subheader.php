<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Subheader
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Subheader extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $label = $element->getLabelHtml();
        $html = '<tr><td colspan="4" class="subheader value"><div id="' . $element->getHtmlId() . '">' .
            Mage::helper("bpost_shm")->__($label).'</div></td></tr>';

        return $html;
    }
}