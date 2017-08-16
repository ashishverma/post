<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Hour
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Hour extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * we use the standard time element but with no seconds
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $value = $element->getEscapedValue().",00";
        $element->setValue($value);

        $html = $element->getElementHtml();

        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $secondsElement = $dom->getElementsByTagName('select')->item(2);

        if(!$secondsElement){
            //return the original html
            return $html;
        }

        $secondsElement->parentNode->removeChild($secondsElement);

        //check if we have
        //remove last : character
        $html = $dom->saveHTML();

        if( ( $pos = strrpos( $html , ":" ) ) !== false ) {
            $html = substr_replace( $html , "" , $pos , 1 );
        }

        return $html;
    }
}