<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
class Bpost_ShM_Helper_System_Config extends Mage_Core_Helper_Abstract
{

    /**
     * @param $code
     * @param int $storeId
     * @return mixed
     * function returns the configured value by system code
     */
    public function getBpostShippingConfig($code, $storeId = 0){
        $value = Mage::getStoreConfig("shipping/bpost_shm/$code", $storeId);

        if($code == "passphrase"){
            $value = Mage::helper('core')->decrypt($value);
        }

        return $value;
    }

    /**
     * @param $code
     * @param int $storeId
     * @return mixed
     * function returns the configured carrier value by system code
     */
    public function getBpostCarriersConfig($code, $carrier, $storeId = 0){
        return Mage::getStoreConfig("carriers/$carrier/$code", $storeId);
    }


    //we create separate functions for config values that are called a lot
    //...

    public function isApiLoggingEnabled($storeId = 0){
        return (bool)$this->getBpostShippingConfig("enable_log_api", $storeId);
    }

    public function isLoggingEnabled($storeId = 0){
        return (bool)$this->getBpostShippingConfig("enable_log", $storeId);
    }
}
