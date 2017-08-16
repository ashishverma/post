<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Helper_Data
 */
class Bpost_ShM_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Standard shm logging
     *
     * @param $message
     * @param $level
     * @param $storeId
     */
    public function log($message, $level, $storeId = 0)
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $allowedLogLevel = $configHelper->getBpostShippingConfig("log_level", $storeId);

        //first check if logging is enabled
        if ($configHelper->isLoggingEnabled()) {
            if ($level <= $allowedLogLevel) {
                Mage::log($message, $level, 'bpost.log');
            }
        }
    }

    /**
     * API logging if something goes wrong
     *
     * @param $message
     * @param $level
     * @param $translate
     * @param $storeId
     */
    public function ApiLog($message, $level, $translate = true, $storeId = 0)
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $allowedLogLevel = $configHelper->getBpostShippingConfig("api_log_level", $storeId);

        //first check if API logging is enabled
        if ($configHelper->isApiLoggingEnabled()) {
            if ($level <= $allowedLogLevel) {
                if($translate){
                    Mage::log($this->__($message), $level, "bpost-api.log");
                }else{
                    Mage::log($message, $level, "bpost-api.log");
                }
            }
        }
    }

    /**
     * Function checks if we have more address lines
     * If so, we use address 2 as housenumber
     * Else we need to get housenumber from the single address line
     *
     * @param $address
     * @return $address
     */
    public function formatAddress($address)
    {
        $address->setData("bpost_name", $address->getFirstname() . " " . $address->getLastname());
        $address->setData("bpost_company", $address->getCompany());

        //we assume that street 3 will be box number - not necessary
        if ($address->getStreet3()) {
            $address->setData("box_number", $address->getStreet3());
        }

        if ($address->getStreet2()) {
            if($address->getData("company") && $address->getData("company") != ""){
                $address->setData("bpost_company", $address->getData("bpost_name") . " (" . $address->getData("company") . ")");
            }else{
                $address->setData("bpost_company", $address->getData("bpost_name"));
            }

            $address->setData("bpost_name", $address->getStreet2());
        }

        $address->setData("bpost_house_number", ",");
        $address->setData("bpost_street", $address->getStreet1());

        return $address;
    }

    /**
     * Validate the postal code
     *
     * @param $country_id
     * @param $postcode
     * @return regex|string
     */
    public function validatePostcode($country_id, $postcode)
    {
        $zipValidationRules = array(
            'LT' => array('99999', '/^(\d{5})$/'),
            'LU' => array('9999', '/^(\d{4})$/'),
            'BE' => array('9999', '/^(\d{4})$/'),
            'LV' => array('9999', '/^(\d{4})$/'),
            'MT' => array('AAA 9999', '/[A-Z]{3} [0-9]{4}/'),
            'NL' => array('9999 AA', '/[0-9]{4} [A-Z]{2}/'),
            'PL' => array('99999', '/^(\d{5})$/'),
            'PT' => array('9999999', '/^([0-9]){7}$/'),
            'RO' => array('999999', '/^(\d{6})$/'),
            'SE' => array('999 99', '/^(\d{3} \d{2})$/'),
            'SI' => array('9999', '/^(\d{4})$/'),
            'SK' => array('999 99', '/^(\d{3} \d{2})$/'),
            'AT' => array('9999', '/^(\d{4})$/'),
            'BG' => array('9999', '/^(\d{4})$/'),
            'CZ' => array('999 99', '/^[0-9]{3} [0-9]{2}$/'),
            'DE' => array('99999', '/^(\d{5})$/'),
            'DK' => array('9999', '/^(\d{4})$/'),
            'EE' => array('99999', '/^(\d{5})$/'),
            'ES' => array('99999', '/^(\d{5})$/'),
            'FI' => array('99999', '/^(\d{5})$/'),
            'FR' => array('99999', '/^(\d{5})$/'),
            'GR' => array('999 99', '/^(\d{3}) \d{2}$/'),
            'HR' => array('99999', '/^(\d{5})$/'),
            'HU' => array('9999', '/^(\d{4})$/'),
            'IT' => array('99999', '/^(\d{5})$/')
        );
        if (array_key_exists($country_id, $zipValidationRules)) {
            $validationRegex = $zipValidationRules[$country_id][1];
            $validationFormat = $zipValidationRules[$country_id][0];

            if (!preg_match($validationRegex, $postcode)) {
                return $validationFormat;
            }
        }
        return 'passed';
    }

    /**
     * Creates new IO object and inputs base 64 pdf string fetched from webservice.
     *
     * @param $pdfString
     * @param $folder
     * @param $name
     *
     * @return string
     */
    public function generatePdfAndSave($pdfString, $folder, $name)
    {
        $hash = bin2hex(mcrypt_create_iv(5, MCRYPT_DEV_URANDOM));
        $name = $name . "-" . $hash;

        $varienFile = new Varien_Io_File();
        $varienFile->setAllowCreateFolders(true);
        $varienFile->open(array('path' => Mage::getBaseDir('media') . "/bpost/" . $folder));
        $varienFile->streamOpen($name . '.pdf', 'w+');
        $varienFile->streamLock(true);
        $varienFile->streamWrite($pdfString);
        $varienFile->streamUnlock();
        $varienFile->streamClose();
        return $name;
    }

    /**
     * Function returns the locale code by order
     *
     * @param $order
     * @param $useExceptionLanguage
     * @return string
     */
    public function getLocaleByOrder($order, $useExceptionLanguage = false)
    {
        $exceptionLanguages = array("DE");

        $store = $order->getStore()->getId();
        $locale = Mage::getStoreConfig("general/locale/code", $store);
        $locale = strtoupper(substr($locale, 0, 2));

        switch ($locale) {
            case "NL":
            case "FR":
            case "EN":
                return $locale;
                break;

            default:
                if ($useExceptionLanguage && in_array($locale, $exceptionLanguages)) {
                    return $locale;
                }

                return "EN";
                break;
        }
    }

    /**
     * Function parses the create label API response in a readable format
     *
     * @param $response
     * @return array
     */
    public function parseLabelApiResponse($response, $order){
        $xml = simplexml_load_string($response->getBody());
        $shippingMethod = $order->getShippingMethod();
        $returnArray = array();
        $returnArray["barcodeString"] = array();

        //international delivery can have more label elements (instead of one with multiple barcodes), so we have to loop
        foreach($xml->label as $label){
            $responseBarcodeArray = (array)$label->barcode;

            //we check for barcodes
            //for national delivery
            //barcodes ending on 030 -> regular barcode
            //barcodes ending on 050 -> return barcode

            //for international delivery
            //barcodes starting with CD -> regular barcode, no return

            //international delivery has other barcodes
            //check on barcode count label
            //if more than 1 -> national, otherwise international

            foreach($responseBarcodeArray as $responseBarcode){
                $responseBarcode = (string)$responseBarcode;

                $firstTwoCharacters = substr($responseBarcode, 0, 2);
                $lastThreeCharacters = substr($responseBarcode, -3);

                switch($shippingMethod){
                    case "bpostshm_bpost_international":
                        if(in_array($firstTwoCharacters, array("CD","EE","CE")) && $lastThreeCharacters != '134'){
                            $returnArray["barcodeString"][] = $responseBarcode;
                        }else{
                            if(!isset($returnArray["returnBarcodeString"])){
                                $returnArray["returnBarcodeString"] = array();
                            }
                            $returnArray["returnBarcodeString"][] = $responseBarcode;
                        }
                    break;

                    default:
                        if($lastThreeCharacters == "050"){
                            if(!isset($returnArray["returnBarcodeString"])){
                                $returnArray["returnBarcodeString"] = array();
                            }
                            $returnArray["returnBarcodeString"][] = $responseBarcode;
                        }else{
                            $returnArray["barcodeString"][] = $responseBarcode;
                        }
                    break;
                }
            }

            $pdfString = $label->bytes;

            //we use the Zend_XmlRpc_Value_Base64 for decoding our string
            $zendXmlRpc = new Zend_XmlRpc_Value_Base64($pdfString, true);
            $pdfString = $zendXmlRpc->getValue();

            if(!isset($returnArray["pdfString"])){
                $returnArray["pdfString"] = array();
                $returnArray["pdfString"][] = $pdfString;
            }else{
                $returnArray["pdfString"][] = $pdfString;
            }
        }

        //make sure the index barcode string isset
        if(empty($returnArray["barcodeString"])){
            $returnArray["barcodeString"][] = $order->getBpostReference();
        }

        return $returnArray;
    }

    /**
     * API function returns the bpost status
     *
     * @param $order
     * @return boolean|string
     */
    public function getBpostStatus($order){
        /** @var Bpost_ShM_Model_Api $apiModel */
        $apiModel = Mage::getModel('bpost_shm/api');
        $apiModel->initialize($order->getStoreId());
        $apiResponse = $apiModel->retrieveOrderDetails($order);

        if(!$apiResponse){
            return false;
        }

        $xml = simplexml_load_string($apiResponse->getBody());
        $statuses = array();

        foreach($xml->box as $box){
            if($box->barcode){
                $statuses[(string)$box->barcode] = (string)$box->status;
            }
        }

        if(empty($statuses)){
            return false;
        }

        return $statuses;
    }

    /**
     * Gets all spots from bpost webservice based on shipping address or coordinates
     *
     * @param $params
     * @return mixed
     */
    public function getBpostSpots($params = array())
    {
        $request = Mage::app()->getRequest();
        $bpostHelper = Mage::helper("bpost_shm");
        $bpostGmapsFilter = $request->getPost("bpost-gmaps-filter");
        $latitude = $request->getPost("lat");
        $longitude = $request->getPost("lng");
        $apiModel = Mage::getModel('bpost_shm/api', true);
        $latLng = false;
        $pointType = 3;

        if(isset($params["pointType"])){
            $pointType = $params["pointType"];
        }

        if(strlen($bpostGmapsFilter) > 0 || ($latitude && $longitude)) {
            // Use google geocoding to get spots
            if($bpostGmapsFilter) {
                // Call map with filtered result
                $geoCode = Mage::getModel("bpost_shm/shipping_geocode")->callGoogleMaps($bpostGmapsFilter);
            } else {
                // Call map with coordinates
                $geoCode = Mage::getModel("bpost_shm/shipping_geocode")->callGoogleMaps(false, $latitude, $longitude);
            }

            if ($geoCode) {
                $address = array();
                $address['street'] = '';
                $address['number'] = '';
                $address['zone'] = $geoCode->getPostalCode();

                $latLng = $geoCode->getLatLng();
            }
        } else {

            if($bpostHelper->isOnestepCheckout()){
                if(isset($params["address_id"]) && $params["address_id"] != null){
                    //load customer address and use
                    $shippingAddress = Mage::getModel("customer/address")->load($params["address_id"]);
                }else{
                    $shippingAddress = Mage::getModel("customer/address");

                    if(isset($params["city"]) && $params["city"] != ""){
                        $shippingAddress->setCity($params["city"]);
                    }

                    if(isset($params["postcode"]) && $params["postcode"] != ""){
                        $shippingAddress->setPostcode($params["postcode"]);
                    }

                    if(isset($params["street"]) && $params["street"] != ""){
                        $shippingAddress->setStreet($params["street"]);
                    }
                }
            }else{
                // Get shipping address from quote object
                $shippingAddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
            }

            $searchString = $shippingAddress->getStreet(1).', '.$shippingAddress->getPostcode().', '.$shippingAddress->getCountry();
            $geoCode = Mage::getModel("bpost_shm/shipping_geocode")->callGoogleMaps($searchString);

            if(is_object($geoCode) && $geoCode->getLatLng() != "" && $geoCode->getLatLng() != null){
                $latLng = $geoCode->getLatLng();
            }
            else{
                return $geoCode;
            }


            $address = $this->formatShippingAddress($shippingAddress);
        }

        try{
            $spots['poiList'] = $apiModel->getNearestServicePoints($address, $pointType);
            $spots['coordinates'] = $latLng;
        }catch (Exception $e){
            $bpostHelper->log("Webservice: not expected result returned:" . $e->getMessage(), Zend_Log::WARN);
            $spots = false;
            $spots['poiList'] = false;
            $spots['coordinates'] = $latLng;
        }

        return $spots;
    }

    /**
     * Gets opening hours of a specific bpost spot
     *
     * @param $id
     * @param $type
     * @return mixed
     */
    public function getBpostOpeningHours($id, $type) {
        $cache = Mage::app()->getCache();
        $key = 'bpost-shm-opening-hours-spotid-' . $id . '-type-' . $type;

        if(!$data = $cache->load($key)) {
            $apiCall = Mage::getModel('bpost_shm/api', true)->getServicePointDetails($id, $type);

            $data = serialize($apiCall);
            //set cache lifetime to null (infinite) as it should only be cleared by changing the rule
            $cache->save(urlencode($data), $key, array("bpost_shm"), 86400);
        } else {
            $apiCall = unserialize(urldecode($data));
        }

        return $apiCall;
    }

    /**
     * Formats the shipping address into a formatted array
     *
     * @param $address
     * @return array
     */
    public function formatShippingAddress($address){
        $formattedAddress = array();
        $number = explode(",", $address->getStreet(1));

        if (is_numeric($number[0])) {
            $replace = array($number[0], ",");
            if ($address->getStreet(1) != "") {
                $street = str_replace($replace, array('', ''), $address->getStreet(1));
            }
            $formattedAddress['street'] = trim($street);
            $formattedAddress['number'] = trim($number[0]);
        } else {
            if ($address->getStreet(1) != "") {
                $number = explode(" ", $address->getStreet(1));
                $street = str_replace(end($number), "", $address->getStreet(1));
            }

            if (preg_match('~[0-9]~', end($number))) {
                $formattedAddress['street'] = $street;
                $formattedAddress['number'] = end($number);
            } else {
                if ($address->getStreet(1) != "") {
                    $formattedAddress['street'] = $address->getStreet(1);
                }
            }
        }

        if ($address->getStreet(2) != "") {
            $formattedAddress['street'] = $address->getStreet(1);
            $formattedAddress['number'] = ",";
        }

        $formattedAddress['street'] = trim($formattedAddress['street']);
        $formattedAddress['zone'] = $address->getPostcode() ?: $address->getCity();
        $formattedAddress['postcode'] = $address->getPostcode();
        $formattedAddress['city'] = $address->getCity();

        return $formattedAddress;
    }

    /**
     * @return string
     */
    public function getMagentoWindowCssItemType(){
        if (Mage::getVersion() < '1.7' || Mage::getVersion() < '1.9' && Mage::getEdition() == 'Community' || Mage::getVersion() < '1.14' && Mage::getEdition() == 'Enterprise') {
            return 'js_css';
        } else {
            return 'skin_css';
        }
    }

    /**
     * @return string
     */
    public function getMageWindowCss()
    {
        if (Mage::getVersion() < '1.7' || Mage::getVersion() < '1.9' && Mage::getEdition() == 'Community' || Mage::getVersion() < '1.14' && Mage::getEdition() == 'Enterprise') {
            return 'prototype/windows/themes/magento.css';
        } else {
            return 'lib/prototype/windows/themes/magento.css';
        }
    }

    /**
     * Function gives filename by path, alternative for basename (GrumPHP)
     *
     * @param $path
     * @return array
     */
    public function getFileNameByPath($path){
        $fileName = explode("/", $path);
        $fileName = $fileName[(count($fileName)-1)];

        return $fileName;
    }

    /**
     * Returns all valid delivery dates
     *
     * @param $closedOn
     * @return array
     */
    public function getBpostShippingDates($closedOn = false)
    {
        //quote object
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $grandTotal = $quote->getGrandTotal();
        $shippingCost = $quote->getShippingAddress()->getData('shipping_incl_tax');
        //bpost helper
        $helper = Mage::helper('bpost_shm');
        //get config values
        $configHelper = Mage::helper("bpost_shm/system_config");
        $displayDeliveryDate = (bool)$configHelper->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId());
        $chooseDeliveryDate = (bool)$configHelper->getBpostShippingConfig("choose_delivery_date", Mage::app()->getStore()->getId());
        $daysBetweenShipment = $configHelper->getBpostShippingConfig("days_between_order_and_shipment", Mage::app()->getStore()->getId());
        $nrOfDaysShown = $configHelper->getBpostShippingConfig("nr_of_delivery_days_shown", Mage::app()->getStore()->getId());
        $cutoffTime = $configHelper->getBpostShippingConfig("next_day_delivery_allowed_till", Mage::app()->getStore()->getId());
        //get the Magento date model
        $dateModel = Mage::getSingleton('core/date');
        //days to add counter, will always be 1 since delivery is never on the same day
        $daysToStart = 1;
        $cutoffTimeSurpassed = false;
        //add a day if the current time surpasses the time treshold
        $time = $dateModel->date('H,i');
        if($cutoffTime <= $time && substr($cutoffTime, 0, 5) != '00,00') {
            $daysToStart = 2;
            $cutoffTimeSurpassed = true;
        }
        //get the current date
        $currentDate = $dateModel->date();
        //define all bpost shipping methods
        $shippingMethods = array(
            'bpost_homedelivery' => false,
            'bpost_pickuppoint' => false,
            'bpost_parcellocker' => false,
            'bpost_clickcollect' => false,
            'bpost_international' => false,
        );
        foreach ($shippingMethods as $method => $value) {
            //get saturday delivery flags
            $saturdayDelivery = (bool)$configHelper->getBpostCarriersConfig("saturday_delivery", $method, Mage::app()->getStore()->getId());
            $saturdayDeliveryFrom = $this->formatSaturdayDeliveryCost($configHelper->getBpostCarriersConfig("saturday_delivery_from", $method, Mage::app()->getStore()->getId()));
            //don't allow saturday delivery if saturday delivery 'yes' and 'as from' amount not exceeded
            if(($grandTotal - $shippingCost) < $saturdayDeliveryFrom) {
                $saturdayDelivery = false;
            }
            $extraDays = $daysToStart;
            //add total days between order and shipment
            $totalDays = $extraDays + $daysBetweenShipment;
            //loop over days checking for the first valid day
            for($i = 1; $i <= $totalDays; $i++) {
                $nextDate = $this->_formatDeliveryDate($currentDate.' +'.$i.' days');

                if(!$this->_isValidDeliveryDate($nextDate, $saturdayDelivery, $method, $closedOn)) {
                    $totalDays++;
                }
            }

            // PBMS-224: check if bpost can pick up the package if saturdayDelivery is enabled.
            if ($saturdayDelivery && !$this->_canBPostPickupPackage($currentDate, $cutoffTimeSurpassed)) {
                $totalDays++;
            }

            $startDate = $this->_formatDeliveryDate($currentDate.' +'.$totalDays.' days');
            //customer gets a date from the system
            if($displayDeliveryDate && !$chooseDeliveryDate) {
                $shippingMethods[$method] = array(
                    'date' => $this->_formatDeliveryDate($startDate),
                    'date_format' => $helper->__($this->_formatDeliveryDate($startDate, 'l'))." ".$this->_formatDeliveryDate($startDate, 'd/m'),
                    'is_saturday' => $this->_isSaturday($startDate),
                    'next_date' => $this->_getNextDeliveryDate($startDate),
                    'next_date_format' => $helper->__($this->_getNextDeliveryDate($startDate, 'l'))." ".$this->_getNextDeliveryDate($startDate, 'd/m')
                );
            //customer can choose own date
            } else if($displayDeliveryDate && $chooseDeliveryDate) {
                $days = array();
                $addedDays = 0;
                for($i = 0; $i < $nrOfDaysShown; $i++) {
                    //add starting date to array
                    if($i == 0) {
                        $days[$i] = array(
                            'date' => $startDate,
                            'date_format' => $helper->__($this->_formatDeliveryDate($startDate, 'l')).'<span>'.$this->_formatDeliveryDate($startDate, 'j')." ".strtolower($helper->__($this->_formatDeliveryDate($startDate, 'F'))).'</span>'
                        );
                    //move to next day
                    } else {
                        $addedDays++;
                        $nextDate = $this->_formatDeliveryDate($startDate.' +'.$addedDays.' days');
                        $validDate = false;
                        while($validDate == false) {
                            if($this->_isValidDeliveryDate($nextDate, $saturdayDelivery, $method, $closedOn)) {
                                $validDate = true;
                            } else {
                                $addedDays++;
                                $nextDate = $this->_formatDeliveryDate($startDate.' +'.$addedDays.' days');
                            }
                        }
                        $days[$i] = array(
                            'date' => $nextDate,
                            'date_format' => $helper->__($this->_formatDeliveryDate($nextDate, 'l')).'<span>'.$this->_formatDeliveryDate($nextDate, 'j')." ".strtolower($helper->__($this->_formatDeliveryDate($nextDate, 'F'))).'</span>'
                        );
                    }
                }
                $shippingMethods[$method] = $days;
            }
        }
        return $shippingMethods;
    }


    /**
     * @return date
     */
    public function getPrevDeliveryDate($deliveryDate){
        $days = 1;
        $validDate = false;
        $previousWeekday = $this->_formatDeliveryDate($deliveryDate.' -'.$days.' weekdays');

        while($validDate == false) {
            if($this->_isValidDeliveryDate($previousWeekday)) {
                $validDate = true;
            } else {
                $days++;
                $previousWeekday = $this->_formatDeliveryDate($deliveryDate.' -'.$days.' weekdays');
            }
        }

        return $previousWeekday;
    }


    /**
     * Returns the next first valid delivery date
     *
     * @param $date
     * @param $format
     * @return array
     */
    protected function _getNextDeliveryDate($date, $format = "Y-m-d") {
        $nextDate = false;

        if($this->_isSaturday($date)) {
            //add a weekday so we end up on monday
            $extraWeekDays = 1;
            $nextDate = $this->_formatDeliveryDate("$date +$extraWeekDays weekdays");

            //check for holidays (monday will most likely not be a saturday or a sunday, and holidays sadly don't take a week)
            $validDeliveryDate = false;
            while($validDeliveryDate == false) {
                if($this->_isValidDeliveryDate($nextDate)) {
                    $validDeliveryDate = true;
                } else {
                    $extraWeekDays++;
                    $nextDate = $this->_formatDeliveryDate("$date +$extraWeekDays weekdays");
                }
            }

            $nextDate = $this->_formatDeliveryDate($nextDate, $format);
        }

        return $nextDate;
    }

    /**
     * PBMS-224: A check to see if the current date is a valid date for picking up packages.
     * BPost can't pickup packages on holidays, sunday, saturday and after cutoffTime.
     *
     * @param $currentDate
     * @param bool $cutoffTimeSurpassed
     * @return bool
     */
    protected function _canBPostPickupPackage($currentDate, $cutoffTimeSurpassed = true)
    {
        if ($this->_isHoliday($currentDate)) {
            return false;
        }
        if ($this->_isSunday($currentDate)) {
            return false;
        }
        if ($this->_isSaturday($currentDate)) {
            return false;
        }
        if ($cutoffTimeSurpassed && $this->_isFriday($currentDate)) {
           return false;
        }

        return true;
    }


    protected function _isValidDeliveryDate($deliveryDate, $saturdayAllowed = false, $method = false, $closedOn = false) {
        if($this->_isHoliday($deliveryDate)) {
            return false;
        }
        if($this->_isSunday($deliveryDate)) {
            return false;
        }
        if(!$saturdayAllowed && $this->_isSaturday($deliveryDate)) {
            return false;
        }
        if($method == 'bpost_pickuppoint') {
            //check if pick-up point is open on this day
            if(is_array($closedOn)) {
                $day = $this->_formatDeliveryDate($deliveryDate, 'l');

                if(in_array($day, $closedOn)) {
                    return false;
                }
            }
        }

        if($method == 'bpost_clickcollect') {
            //check if pick-up point is open on this day
            if(is_array($closedOn)) {
                $day = $this->_formatDeliveryDate($deliveryDate, 'l');

                if(in_array($day, $closedOn)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if date is on a friday
     *
     * @param $date
     * @return bool
     */
    protected function _isFriday($date)
    {
        return ($this->_formatDeliveryDate($date, "N") == 5);
    }

    /**
     * Check if date is on a saturday
     *
     * @param $date
     * @return bool
     */
    protected function _isSaturday($date)
    {
        return ($this->_formatDeliveryDate($date, "N") == 6);
    }

    /**
     * Check if date is on a sunday
     *
     * @param $date
     * @return bool
     */
    protected function _isSunday($date)
    {
        return ($this->_formatDeliveryDate($date, "N") == 7);
    }

    /**
     * Check if date is a holiday (according to Belgian holidays)
     *
     * @param $date
     * @return bool
     */
    protected function _isHoliday($date)
    {
        $holidays = Mage::getModel('bpost_shm/holidays')->getCollection()->getHolidays();

        if(in_array($date, $holidays)) {
            return true;
        }

        return false;
    }


    /**
     * Format the delivery date to any format (default: Y-m-d)
     *
     * @param $date
     * @param $format
     * @return date
     */
    protected function _formatDeliveryDate($date, $format = "Y-m-d") {
        return date($format, strtotime($date));
    }

    /**
     * Format the saturday delivery cost
     *
     * @param $value
     * @return bool|float
     */
    public function formatSaturdayDeliveryCost($value)
    {
        $value = str_replace(',', '.', $value);
        if (!is_numeric($value)) {
            return false;
        }
        $value = (float)sprintf('%.4F', $value);
        if ($value < 0.0000) {
            return false;
        }
        return $value;
    }

    /**
     * Check if on Onestepcheckout page or if Onestepcheckout is the refferer
     *
     * @return bool
     */
    public function isOnestepCheckout()
    {
        if (strpos(Mage::helper("core/url")->getCurrentUrl(), 'onestepcheckout') !== false || strpos(Mage::app()->getRequest()->getHeader('referer'), 'onestepcheckout') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get Onestepcheckout version
     *
     * @return string
     */
    public function getOnestepCheckoutVersion()
    {
        $version = Mage::getConfig()->getNode()->modules->Idev_OneStepCheckout->version;
        list($major, $minor) = explode('.', $version);

        return (string) $major . '.' . $minor;
    }

    /**
     * Calculates total weight of a shipment.
     *
     * @param $shipment
     * @return int
     */
    public function calculateTotalShippingWeight($shipment)
    {
        $weight = 0;
        $shipmentItems = $shipment->getAllItems();
        foreach ($shipmentItems as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            if (!$orderItem->getParentItemId()) {
                $weight = $weight + ($shipmentItem->getWeight() * $shipmentItem->getQty());
            }
        }

        return $weight;
    }


    /**
     * @param $shipments
     * @return int
     */
    public function processShipmentsWeight($order)
    {
        $bpostHelper = Mage::helper("bpost_shm/system_config");
        $weightUnit = $bpostHelper->getBpostShippingConfig("weight_unit", $order->getStoreId());

        $totalShipmentsWeight = 0;

        $shipmentCollection = Mage::getModel("sales/order_shipment")->getCollection()
        ->addFieldToFilter("order_id", $order->getId());

        foreach ($shipmentCollection as $shipment){
            if ($weightUnit == "") {
                $totalShipmentsWeight += $shipment->getTotalWeight() * 100;
            } else {
                $totalShipmentsWeight += $shipment->getTotalWeight() * $weightUnit;
            }
        }

        return $totalShipmentsWeight;
    }
}
