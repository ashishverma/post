<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Info
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Info extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $telephone = "02/201 11 11";
        $url = "http://bpost.freshdesk.com/solution/articles/174847";
        $html = "<tr><td colspan='4'>".Mage::helper("bpost_shm")->__("You need a user account from bpost to use this module. Call %s for more information.", $telephone)." <a target='_blank' href='$url'>".$url."</a></td></tr>";

        return $html;
    }
}