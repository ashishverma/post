<?php
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Tablerates_Export_International
    extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website'),
            'method' => 'international',
            'filename' => 'bpost_international_tablerates.csv'
        );

        $data = array(
            'label' => Mage::helper('adminhtml')->__('Export CSV'),
            'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl("adminhtml/bpost_shM_config/exportTablerates", $params) . 'conditionName/\' + $(\'carriers_bpost_international_condition_name\').value)',
            'class' => '',
            'id' => 'carriers_international_tablerates_export'
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}