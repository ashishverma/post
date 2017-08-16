<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_Sales_Grid_Renderer_Label_Download
 */
class Bpost_ShM_Block_Adminhtml_Sales_Grid_Renderer_Label_Download extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     * function renders a label download link
     */
    public function render(Varien_Object $row)
    {
        $helper = Mage::helper("adminhtml");

        if(!$row->getBpostLabelExists() || !$row->getBpostLabelPath() || $row->getBpostLabelPath() == ""){
            return $helper->__("No");
        }

        $returnValue = '<a href="'.$helper->getUrl("*/bpost_shM_download/label", array("order_id" => $row->getId())).'"><img src="'.Mage::getDesign()->getSkinUrl('images/bpost/pdf_icon.png').'" alt="download label"/></a>';

        return $returnValue;
    }
}