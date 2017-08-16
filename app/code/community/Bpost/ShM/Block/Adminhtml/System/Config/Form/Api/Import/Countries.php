<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Api_Import_Countries
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Api_Import_Countries
    extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');
        $url = Mage::helper('adminhtml')->getUrl("adminhtml/bpost_shM_config/importCountriesByApi");

        $data = array(
            'label' => Mage::helper('adminhtml')->__('Import'),
            'onclick' => 'setLocation(\'' . $url . '\')',
            'class' => '',
            'id' => 'carriers_international_countries_import'
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}