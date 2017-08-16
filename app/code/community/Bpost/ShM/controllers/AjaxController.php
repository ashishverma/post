<?php
class Bpost_ShM_AjaxController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Return a list of all bpost spots
     */
    public function getwindowAction() {
        $request = $this->getRequest();
        $isAjax = $request->isAjax();

        if ($isAjax) {
            $params = $request->getParams();

            if(isset($params["pointType"])){
                $apiCall = Mage::helper("bpost_shm")->getBpostSpots($params);
            }else{
                //make call and check if it returns an error.
                $apiCall = Mage::helper("bpost_shm")->getBpostSpots();
            }
            if(!is_array($apiCall)){
                $payloadFull = array("error" => Mage::helper("bpost_shm")->__('Your address could not be determined, please return to the shipping address step to correct it.'), "poilist" => $apiCall, "coordinates" => "");
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(json_encode($payloadFull));
                return;
            }

            $error = array();
            $xml = simplexml_load_string($apiCall['poiList']);
            $coordinates = $apiCall['coordinates'];

            try{
                $poiList = $xml->PoiList;
            }catch (Exception $e){
                Mage::helper("bpost_shm")->log("Webservice: not expected result returned:" . $e->getMessage(), Zend_Log::WARN);
                $poiList = "";
                $error[] = Mage::helper('bpost_shm')->__("Sorry, there was a problem contacting bpost, please contact the store owner for support.");
            }
            $payloadFull = array("error" => $error, "poilist" => $poiList, "coordinates" => $coordinates);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($payloadFull));
        }
    }

    /**
     * Return opening hours of a bpost spot
     */
    public function gethoursAction() {
        $request = $this->getRequest();
        $isAjax = $request->isAjax();

        if ($isAjax) {
            $error = array();
            $params = $request->getParams();

            $id = isset($params["id"]) ? $params["id"] : false;
            $type = isset($params["type"]) && is_numeric($params["type"]) ? $params["type"] : false;
            $spots = isset($params["spots"]) ? json_decode($params["spots"]) : false;

            if($id){
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $quote->getShippingAddress()->setData('bpost_pickuplocation_id',$id);
            }

            if($id && $type) {
                $apiCall = Mage::helper('bpost_shm')->getBpostOpeningHours($id, $type);

                $xml = simplexml_load_string($apiCall);

                try {
                    $openingHours = $xml->Poi->Record->Hours;
                } catch (Exception $e) {
                    Mage::helper("bpost_shm")->log("Webservice: not expected result returned:" . $e->getMessage(), Zend_Log::WARN);
                    $openingHours = "";
                    $error[] = Mage::helper('bpost_shm')->__("Sorry, there was a problem contacting bpost, please contact the store owner for support.");
                }

                $payloadFull = array("error" => $error, "hours" => $openingHours);

            } elseif(is_array($spots)) {
                foreach ($spots as $spot) {
                    $id = $spot->id;
                    $type = $spot->type;

                    $apiCall = Mage::helper('bpost_shm')->getBpostOpeningHours($id, $type);

                    $xml = simplexml_load_string($apiCall);

                    try {
                        $openingHours = $xml->Poi->Record->Hours;
                    } catch (Exception $e) {
                        Mage::helper("bpost_shm")->log("Webservice: not expected result returned:" . $e->getMessage(), Zend_Log::WARN);
                        $openingHours = "";
                        $error[] = Mage::helper('bpost_shm')->__("Sorry, there was a problem contacting bpost, please contact the store owner for support.");
                    }

                    $payloadFull[$id] = array("error" => $error, "hours" => $openingHours);
                }
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($payloadFull));
        }
    }

    /**
     * Return opening dates of a bpost spot
     */
    public function getdatesAction() {
        $request = $this->getRequest();
        $isAjax = $request->isAjax();

        $id = $request->getPost("id");
        $type = $request->getPost("type");

        if ($isAjax && $id && $type) {
            $apiCall = Mage::helper('bpost_shm')->getBpostOpeningHours($id, $type);
            $error = array();
            $weekDays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            $closedOn = array();

            $xml = simplexml_load_string($apiCall);

            try{
                $openingHours = $xml->Poi->Record->Hours;

                foreach($weekDays as $weekDay) {
                    $day = $openingHours->$weekDay;

                    if($day) {
                        if(empty($day->AMOpen[0]) && empty($day->AMClose[0]) && empty($day->PMOpen[0]) && empty($day->PMClose[0])) {
                            //assume the point is closed for the day
                            array_push($closedOn, $weekDay);
                        }
                    }
                }

                $shippingDates = Mage::helper('bpost_shm')->getBpostShippingDates($closedOn);

            }catch (Exception $e){
                Mage::helper("bpost_shm")->log("Webservice: not expected result returned:" . $e->getMessage(), Zend_Log::WARN);
                $shippingDates = "";
                $error[] = Mage::helper('bpost_shm')->__("Sorry, there was a problem contacting bpost, please contact the store owner for support.");
            }

            $payloadFull = array("error" => $error, "dates" => $shippingDates);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($payloadFull));
        }
    }
}
