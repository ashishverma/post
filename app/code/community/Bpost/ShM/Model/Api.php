<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

class Bpost_ShM_Model_Api extends Bpost_ShM_Model_Api_Abstract
{
    CONST TYPE_PICKUP_POINT     = 3;
    CONST TYPE_PARCEL_LOCKER    = 4;
    CONST TYPE_CLICK_COLLECT    = 8;

    
    /**
     * function returns the product configuration for a specific customer
     */
    public function getProductConfig(){
        $path = $this->_accountId.DS."productconfig";
        $headers = array("Accept:application/vnd.bpost.shm-productConfiguration-v3.1+XML");

        $response = $this->_call($path, $headers);
        return $response;
    }


    /**
     * @param $order
     * @param bool $shipment
     * @return bool|Zend_Http_Response
     *
     * function returns the API response of creating a RETURN label
     * this response can be passed to the parse label response helper function
     * and later on be saved
     */
    public function createReturnLabel($order){
        //first we need to create a return order
        //otherwise no label can be created
        $response = $this->createOrder($order, true);

        if(!$response) {
            return false;
        }

        //now we create the label
        return $this->createLabelByOrder($order);
    }


    /**
     * @param $order
     * @param bool $shipment
     * @param bool $returnOrder
     * @return bool|Zend_Http_Response
     * @throws Mage_Core_Exception
     * function creates an order/label for bpost orders
     */

    public function createOrder($order, $returnOrder = false){
        $path = $this->_accountId.DS."orders";
        $headers = array("Content-Type:application/vnd.bpost.shm-order-v3.3+XML");

        $shippingMethod = $order->getShippingMethod();

        //check to make sure we only handle bpost orders
        if(!$returnOrder && strpos($shippingMethod,"bpost_") === false){
            Mage::throwException("Only orders with delivery method 'bpost_*' can be send to bpost.");
        }

        //now we create a new dom element
        //we use the domcreator class for this
        $domCreator = Mage::getModel("bpost_shm/api_domcreator");
        $domCreator->initialize($order->getStoreId());
        $document = $domCreator->getCreateOrderDomDocument($order, $returnOrder);

        $errorHandlingData = array("request_name" => "createOrder", "order_id" => $order->getIncrementId());
        $response = $this->_call($path, $headers, $errorHandlingData, "post", $document);

        return $response;
    }


    /**
     * @param bool $order
     * @param bool $withReturnLabels
     * @param string $format
     * @return bool|Zend_Http_Response
     * function calls the createLabelByShipmentOrInBulk function with reference prefix empty
     */
    public function createLabelByOrder($order, $withReturnLabels = false, $format = "pdf"){
        return $this->createLabelByShipmentOrInBulk($order, $withReturnLabels, $format, "");
    }


    /**
     * @param $orderObject
     * @param bool $withReturnLabels
     * @param string $format
     * @return bool|Zend_Http_Response
     * function returns the API response of creating a label (with or without return label)
     * this response can be passed to the parse label response helper function
     * and later on be saved

     * if no shipment is given, we will download in bulk
     */
    public function createLabelByShipmentOrInBulk($orderObject = false, $withReturnLabels = false, $format = "pdf", $referencePrefix = "S"){
        if($format === "pdf"){
            $headers = array("Content-Type:application/vnd.bpost.shm-labelRequest-v3+XML","Accept:application/vnd.bpost.shm-label-pdf-v3+XML");
        }else{
            $headers = array("Content-Type:application/vnd.bpost.shm-labelRequest-v3+XML","Accept:application/vnd.bpost.shm-label-image-v3+XML");
        }

        $configHelper =  Mage::helper("bpost_shm/system_config");

        if($orderObject){
            $storeId = $orderObject->getStoreId();
            $referenceId = $referencePrefix.$orderObject->getIncrementId();

            if($orderObject->getNewReference()){
                $referenceId = $orderObject->getNewReference();
            }

            $labelFormat = strtoupper($configHelper->getBpostShippingConfig("label_format", $storeId));
            $path = $this->_accountId.DS."orders".DS.$referenceId.DS."labels".DS.$labelFormat;
        }else{
            $labelFormat = strtoupper($configHelper->getBpostShippingConfig("label_format"));
            $path = $this->_accountId.DS."orders".DS."labels".DS.$labelFormat;
        }

        if($withReturnLabels){
            $path .= DS."withReturnLabels";
        }

        $errorHandlingData = array("request_name" => "createLabel");
        $response = $this->_call($path, $headers, $errorHandlingData);

        return $response;
    }


    /**
     * @param $order
     * API function returns order details by reference
     * @return mixed
     */
    public function retrieveOrderDetails($order){
        $headers = array("Accept:application/vnd.bpost.shm-order-v3.3+XML");
        $referenceId = $order->getIncrementId();

        $path = $this->_accountId.DS."orders".DS.$referenceId;
        $errorHandlingData = array("request_name" => "orderDetails");

        $response = $this->_call($path, $headers, $errorHandlingData);
        return $response;
    }


    /**
     * GEO6 webservice call
     * function delivers the nearest bpost pick-up points to a location given as argument.
     * @param $addressData
     * addressData = array that contains: street, number & zone
     *    street
     *    number
     *    zone: can be postcode or city
     * @param $type
     * type:
     *    Type 1 = Post Office
     *    Type 2 = Post Point
     *    Type 4 = bpack 24/7
     *    for example type 3 = type1 & type2
     * @param $limit
     * limit: max number of service points you want to get
     * @param $language
     * language nl or fr
     * @return bool|Zend_Http_Response
     */
    public function getNearestServicePoints($addressData = array(), $type = self::TYPE_PICKUP_POINT, $limit = 10, $language = false){
        $function = "search";
        $format = "xml";

        if(!array_key_exists("zone", $addressData)){
            Mage::throwException("Your address data must contain a zone index (can be a postcode or city).");
        }

        $language = $this->_getCurrentLanguage($language);

        $params = array(
            "Type" => $type,
            "Limit" => $limit,
            "Language" => $language,
            "Function" => $function,
            "Format" => $format,
            "Zone" => $addressData["zone"],
            "Partner" => $this->_getTaxiPostPartnerForType($type)
        );
        //fixing PEBKAC issue (see Confluence)
        //$params["Street"] = $addressData["street"];
        //$params["Number"] = $addressData["number"];

        if ($type == self::TYPE_CLICK_COLLECT) {
            $params["CheckOpen"] = 0;
        }

        $response = $this->_callTaxipostApi($params);

        return $response->getBody();
    }


    /**
     * GEO6 webservice call
     * delivers the details for a bpost pick-up point referred to by its identifier.
     * @param $servicePointId
     * identifier
     * @param $type
     * type:
     *    Type 1 = Post Office
     *    Type 2 = Post Point
     *    Type 4 = bpack 24/7
     *    for example type 3 = type1 & type2
     * @param $language
     * language nl or fr
     * @return bool|Zend_Http_Response
     */
    public function getServicePointDetails($servicePointId, $type = self::TYPE_PICKUP_POINT, $language = false){
        $format = "xml";

        $language = $this->_getCurrentLanguage($language);

        $params = array(
            "Type" => $type,
            "Format" => $format,
            "Language" => $language,
            "Function" => "info",
            "Id" => $servicePointId,
            "Partner" => $this->_getTaxiPostPartnerForType($type)
        );

        $response = $this->_callTaxipostApi($params);

        return $response->getBody();
    }


    /**
     * GEO6 webservice call
     * delivers the details for a bpost Corner referred to by its identifier, presented in an HTML page.
     * @param $servicePointId
     * identifier
     * @param $type
     * type:
     *    Type 1 = Post Office
     *    Type 2 = Post Point
     *    Type 4 = bpack 24/7
     *    for example type 3 = type1 & type2
     * @param $language
     * language nl or fr
     * @return bool|Zend_Http_Response
     */
    public function getServicePointPage($servicePointId, $type = self::TYPE_PICKUP_POINT, $language = false){
        $language = $this->_getCurrentLanguage($language);
        $params = array(
            "Type" => $type,
            "Language" => $language,
            "Function" => "page",
            "Id" => $servicePointId,
            "Partner" => $this->_getTaxiPostPartnerForType($type)
        );

        $response = $this->_callTaxipostApi($params);

        return $response->getBody();
    }


    /**
     * @return string
     */
    protected function _getCurrentLanguage($language = false){

        if(!$language) {
            $language = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        }

        if($language != "nl" && $language != "fr"){
            $language = "fr";
        }

        return $language;
    }

    /**
     * @param int $type
     * @return string
     */
    protected function _getTaxiPostPartnerForType($type)
    {
        if ($type == self::TYPE_CLICK_COLLECT) {
            $configHelper = Mage::helper("bpost_shm/system_config");
            $storeId = Mage::app()->getStore()->getId();

            return $configHelper->getBpostShippingConfig('accountid', $storeId);
        }

        return self::API_TAXIPOST_PARTNER;
    }

}
