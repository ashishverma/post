<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

class Bpost_ShM_Model_Api_Abstract extends Mage_Core_Model_Abstract
{
    const API_PATH_PREFIX           = '/services/shm';
    const API_URI_TAXIPOST_BASE     = "http://taxipost.geo6.be";
    const API_TAXIPOST_APPID        = "A001";
    const API_TAXIPOST_PARTNER      = '107444';

    protected $_apiUriBase;
    protected $_accountId;
    protected $_passphrase;
    protected $_initialized = false;
    protected $_bpostConfigHelper;
    protected $_path = "";

    /**
     * Http client that is curently used.
     *
     * @var     Zend_Http_Client
     * @access  protected
     */
    protected $_httpClient;

    /**
     * Authorization key
     *
     * @var     string
     * @access  protected
     */
    protected $_authorization;


    //constructor
    public function __construct($initialize = false){

        //first we set the helper as variable
        //easier to access it
        $this->_bpostConfigHelper = Mage::helper("bpost_shm/system_config");

        //check if we need to initialize immediately
        if($initialize){
            $this->initialize();
        }
    }


    /**
     * Set the api domain, account id and passphrase of the given store
     *
     * @param null|int $storeId
     */
    public function initialize($storeId = null)
    {
        if (!$storeId) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $this->_apiUriBase = $this->_bpostConfigHelper->getBpostShippingConfig('api_url', $storeId);
        $this->_accountId = $this->_bpostConfigHelper->getBpostShippingConfig('accountid', $storeId);
        $this->_passphrase = $this->_bpostConfigHelper->getBpostShippingConfig('passphrase', $storeId);
        $this->_authorization = base64_encode($this->_accountId . ':' . $this->_passphrase);
        $this->_initialized = true;
    }

    /**
     * @return bool
     * function checks if the current object is initialized or not
     */
    public function isInitialized(){
        if($this->_initialized){
            return true;
        }

        return false;
    }

    /**
     * Returns a REST client we can use for connecting to the webservice.
     * @param array $headers
     *
     * @return Zend_Rest_Client
     */
    protected function _getRestClient($headers = null){
        try{
            //first make sure the current model is initialized
            if(!$this->isInitialized()){
                Mage::throwException('Please initialize your API model first by calling the initialize() function.');
            }

            $restClient = new Zend_Rest_Client($this->_apiUriBase);
            $this->_httpClient = new Zend_Http_Client($this->_apiUriBase, array('useragent' => 'Magento, with bpost Shipping Manager module.'));
            $this->_httpClient->setHeaders('Authorization', 'Basic ' . $this->_authorization);

            if ($headers) {
                $this->_httpClient->setHeaders($headers);
            }

            $restClient->setHttpClient($this->_httpClient);
            return $restClient;
        }catch(Exception $e){
            Mage::helper("bpost_shm")->ApiLog($e->getMessage(), Zend_Log::ERR);
        }

        return false;
    }


    /**
     * Returns a Http client we can use for connecting to the taxipost webservice.
     * @param array $headers
     *
     * @return Zend_Http_Client
     */
    protected function _getTaxipostHttpClient($urlExtension = false){
        try{
            $url = self::API_URI_TAXIPOST_BASE;

            if($urlExtension){
                $url .= DS.$urlExtension;
            }

            $httpClient = new Zend_Http_Client($url, array('useragent' => 'Magento, with bpost Shipping Manager module.'));

            return $httpClient;
        }catch(Exception $e){
            Mage::helper("bpost_shm")->ApiLog($e->getMessage(), Zend_Log::ERR);
        }

        return false;
    }


    /**
     * @param $path
     * @param $headers
     * function sends request
     */
    protected function _call($path, $headers, $errorHandlingData = array(), $action = "get", $document = null){
        $bpostHelper = Mage::helper("bpost_shm");
        $path = self::API_PATH_PREFIX.DS.$path;
        $restClient = $this->_getRestClient($headers);

        $bpostHelper->ApiLog("HEADERS:", Zend_Log::DEBUG, false);
        $bpostHelper->ApiLog("\n".implode("\n", $headers), Zend_Log::DEBUG, false);

        $bpostHelper->ApiLog("PATH:", Zend_Log::DEBUG,false);
        $bpostHelper->ApiLog($path, Zend_Log::DEBUG, false);


        if($restClient){
            if($action === "get"){
                $response = $restClient->restGet($path);
            }else{
                $data = trim($document->saveXml());
                $bpostHelper->ApiLog("DATA:", Zend_Log::DEBUG, false);
                $bpostHelper->ApiLog($data, Zend_Log::DEBUG, false);

                $httpClient = $restClient->getHttpClient();
                $httpClient->setRawData($data, 'text/xml');
                $httpClient->getUri()->setPath($path);

                $response = $httpClient->request('POST');
            }

            try{
                $bpostHelper->ApiLog("RESPONSE:", Zend_Log::DEBUG, false);
                $bpostHelper->ApiLog($response, Zend_Log::DEBUG, false);

                $this->_checkApiResponse($path, $response);

                return $response;
            }catch (Exception $e){
                $errorMessage = $e->getMessage();

                //we first check if we need to do something with this error
                $this->_errorHandling($errorHandlingData, $errorMessage);

                $bpostHelper->ApiLog("ERROR:", Zend_Log::ERR, false);
                $bpostHelper->ApiLog($errorMessage, Zend_Log::ERR);
                return false;
            }
        }

        return false;
    }


    /**
     * Function calls the taxipost api (for GE06 calls)
     *
     * @param array $params
     * @return bool
     */
    protected function _callTaxipostApi($params){
        $urlExtension = "Locator";

        if (!isset($params["Partner"])) {
            $params["Partner"] = self::API_TAXIPOST_PARTNER;
        }

        $params["AppId"] = self::API_TAXIPOST_APPID;

        $httpClient = $this->_getTaxipostHttpClient($urlExtension);
        $httpClient->setParameterGet($params);
        $response = $httpClient->request('GET');
        try{
            $this->_checkApiResponse($httpClient->getLastRequest(), $response);

            return $response;
        }catch (Exception $e){
            $errorMessage = $e->getMessage();

            Mage::helper("bpost_shm")->ApiLog($errorMessage, Zend_Log::ERR);
            return false;
        }
    }


    //function checks if we need to do something with this error
    //for example sending an email
    protected function _errorHandling($errorHandlingData, $errorMessage){
        $configHelper = Mage::helper("bpost_shm/system_config");

        if(!empty($errorHandlingData) && isset($errorHandlingData["request_name"])){

            switch($errorHandlingData["request_name"]){
                case "createOrder":
                    //only send error email if 'use Magento to manage labels' is false
                    $manageLabels = $configHelper->getBpostShippingConfig("manage_labels_with_magento");

                    if(!$manageLabels){
                        //require email template, add error message, and send it to the shopmanager
                        $receiver = Mage::getStoreConfig('trans_email/ident_general/email');
                        $receiverName = Mage::getStoreConfig('trans_email/ident_general/name');

                        if($receiver && $receiver != ""){
                            $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('bpost_shm_errorhandling_create_order_email_template');

                            //Create an array of email variables
                            $emailVars = array();
                            $orderId = $errorHandlingData["order_id"];

                            $emailVars["order_id"] = $orderId;
                            $emailVars["receiver_name"] = " ".$receiverName;
                            $emailVars["error_message"] = $errorMessage;

                            $emailTemplate->setTemplateSubject(Mage::helper('bpost_shm')->__('Error creating bpost order'));
                            $emailTemplate->setSenderName("Magento - bpost shipping manager");

                            //send mail to yourself from, to
                            $emailTemplate->setSenderEmail($receiver);

                            if($emailTemplate->send(array($receiver), null, $emailVars)){
                                Mage::helper("bpost_shm")->ApiLog("Error email sent", Zend_Log::DEBUG);
                            }
                        }
                    }
                break;
            }
        }
    }


    /**
     * @param Zend_Http_Response $response
     * function checks response for errors
     */
    protected static function _checkApiResponse($path = "", Zend_Http_Response $response){
        if ($response->isError()) {
            if ($response->getStatus() == 401) {
                Mage::throwException('401 Unauthorized: the account id or/and pass-phrase are not set correctly.');
            }

            Mage::throwException(sprintf('Request path: %s - Invalid response status code (HTTP/%s %s %s) - response body: %s', $path, $response->getVersion(), $response->getStatus(), $response->getMessage(), $response->getBody()));
        }
    }
}
