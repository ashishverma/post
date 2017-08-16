<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Adminhtml_System_Config_Source_Datecomment
 */
class Bpost_ShM_Model_Adminhtml_System_Config_Source_Datecomment extends Mage_Core_Model_Config_Data
{

    public function getCommentText()
    {
        $bpostHelper = Mage::helper("bpost_shm");
        $popupUrl = "'" . Mage::helper("adminhtml")->getUrl('adminhtml/bpost_shM_config/screenshotpopup') . "'";

        $html = $bpostHelper->__("Displays calculated delivery date in the frontend:");
        $html .= "<br/>";
        $html .= '<a href="#" onclick="openPopup(' . $popupUrl . ',400,600);">';
        $html .= '<span>' . $bpostHelper->__("Example") . '</span> ';
        $html .= '</a>';

        return $html;
    }

}
