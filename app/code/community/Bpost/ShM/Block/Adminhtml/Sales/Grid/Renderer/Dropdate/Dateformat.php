<?php
class Bpost_ShM_Block_Adminhtml_Sales_Grid_Renderer_Dropdate_Dateformat extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $returnValue = $value;
        $now = date('Y-m-d');
        if($value == $now){
            $returnValue = '<strong>'.$value.'</strong>';
        }
        elseif($value < $now){
            $returnValue = '<span style="color:red;">'.$value.'</span>';
        }
        return $returnValue;
    }
}