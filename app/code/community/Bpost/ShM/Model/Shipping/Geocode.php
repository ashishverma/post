<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Shipping_Geocode
 */
class Bpost_ShM_Model_Shipping_Geocode
{
    protected $_addressLine;
    /**
     * @var Bpost_ShM_Helper_Data
     */
    protected $_success = false;
    protected $_xml;

    /**
     * Make the call to google geocode
     * @param $gMapsAddress
     * @param $latitude
     * @param $longitude
     * @return $this|boolean
     */
    public function callGoogleMaps($gMapsAddress, $latitude = false, $longitude = false)
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $key = $configHelper->getBpostShippingConfig("server_api_key", Mage::app()->getStore()->getId());

        if($gMapsAddress) {
            $this->_addressLine = $gMapsAddress;
            $url = 'https://maps.googleapis.com/maps/api/geocode/xml?address=' . urlencode($gMapsAddress);
        } else {
            $coords = $latitude.','.$longitude;
            $this->_addressLine = $coords;
            $url = 'https://maps.googleapis.com/maps/api/geocode/xml?latlng=' . urlencode($coords);
        }
        $url .= '&language=nl';
        if($key) {
            $url .= '&key='.$key;
        }
        Mage::helper('bpost_shm')->log("Geo URL: " . $url, Zend_Log::DEBUG);
        try{
            $xml = simplexml_load_file($url);
            switch($xml->status){
                case "OK":
                    $this->_success = true;
                    $this->_xml = $xml;
                    Mage::helper('bpost_shm')->log("Geocode: OK ".$this->_addressLine." to xml" ,Zend_Log::DEBUG);
                    return $this;
                    break;
                case "ZERO_RESULTS":
                    $errormsg = "Geocode: no results found for ".$this->_addressLine;
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
                case "OVER_QUERY_LIMIT":
                    $errormsg = "Geocode: Over Query Limit. check your api console";
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
                case "REQUEST_DENIED":
                    $errormsg = "Geocode: Request denied";
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
                case "INVALID_REQUEST":
                    $errormsg = "Geocode: invalid request , address missing?";
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
                case "UNKNOWN_ERROR":
                    $errormsg = "Geocode: unknown Error";
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
                default:
                    $errormsg = "Geocode: unknown Status " . $xml->status;
                    Mage::helper('bpost_shm')->log($errormsg, Zend_Log::ERR);
                    return $errormsg;
                    break;
            }
        }catch (Exception $e){
            Mage::helper('bpost_shm')->log("Geocode: ". $e->getMessage() ,Zend_Log::ERR);
            return $e-getMessage();
        }
    }

    /**
     * Extract useful address information from the geocode xml
     * @return string|boolean
     */
    protected function _extractFromAdress($components, $type)
    {
        foreach ($components->address_component as $component) {
            if($component->type == $type) {
                return $component->long_name;
            }
        }
        return false;
    }

    /**
     * Get the coordinates from the xml
     * @return array|boolean
     */
    public function getLatLng()
    {
        if($this->_success){
            $lat = (string)$this->_xml->result[0]->geometry->location->lat;
            $lng = (string)$this->_xml->result[0]->geometry->location->lng;

            if(isset($lat) && isset($lng)) {
                return array('lat' => $lat, 'lng' => $lng);
            }
        }
        return false;
    }

    /**
     * Get the postal code/city from the xml
     * @return string|boolean
     */
    public function getPostalCode()
    {
        if($this->_success){
            $postalCode = $this->_extractFromAdress($this->_xml->result[0], 'postal_code');
            $locality = $this->_extractFromAdress($this->_xml->result[0], 'locality');

            if($postalCode) {
                return (string)$postalCode;
            } elseif($locality) {
                return (string)$locality;
            }
        }
        return false;
    }
}
