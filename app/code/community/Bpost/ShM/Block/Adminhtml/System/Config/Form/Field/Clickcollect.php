<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Clickcollect
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Clickcollect extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $text = Mage::helper('bpost_shm')->__('You would like to deliver your parcel in your own stores?');
        $text .= " " . Mage::helper('bpost_shm')->__('Click & Collect is the most convenient option for you.');
        $text .= " " . Mage::helper('bpost_shm')->__('Please find more information on %s.',
            '<a href="http://www.bpost.be/site/en/business/send_post/parcels/clickandcollect.html">http://www.bpost.be/site/en/business/send_post/parcels/clickandcollect.html</a>' );
        $text .= "<br />  " . Mage::helper('bpost_shm')->__('The delivery method Click & Collect is not activated by default on your contract.');
        $text .= " " . Mage::helper('bpost_shm')->__('Please request an activation of the service in order to use it on your website.');
        $html = '<tr><td colspan="4" class="value fulltext"><div id="' . $element->getHtmlId() . '">' . $text . '</div></td></tr>';

        return $html;
    }
}