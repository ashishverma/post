<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
class Bpost_ShM_Model_Adminhtml_System_Config_Source_Shipping
{

    /**
     * standard option array function
     * if no function is added to the source model, this function will be called
     */
    public function toOptionArray(){

    }

    /**
     * @return array
     * function returns the available label sizes
     */
    public function getLabelSizesOptionsArray(){
        return array("a4" => "A4", "a6" => "A6");
    }

    /**
     * @return array
     */
    public function nrOfDeliveryDaysShownOptionsArray(){
        return array(2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7);
    }

    /**
     * @return array
     * function returns an array with all available log levels
     */
    public function getLogLevels(){
        return array(0 => "Emergency", 1 => "Alert", 2 => "Critical", 3 => "Error", 4 => "Warning", 5 => "Notice", 6 => "Informational", 7 => "Debug");
    }


    /**
     * @return array
     */
    public function getCountries(){
        return array("BE" => Mage::helper("bpost_shm")->__("Belgium"));
    }


}
