<?php

class Bpost_ShM_Model_Api_Domcreator extends Bpost_ShM_Model_Api_Abstract
{
    protected $_xmlnsValue = "http://schema.post.be/shm/deepintegration/v3/national";
    protected $_xmlnsCommonValue = "http://schema.post.be/shm/deepintegration/v3/common";
    protected $_xmlnsTnsValue = "http://schema.post.be/shm/deepintegration/v3/";
    protected $_xmlnsInternational = "http://schema.post.be/shm/deepintegration/v3/international";
    protected $_xmlnsXsiValue = "http://www.w3.org/2001/XMLSchema-instance";
    protected $_xsiSchemaLocation = "http://schema.post.be/shm/deepintegration/v3/";

    protected $_shippingAddress;
    protected $_billingAddress;

    /**
     * @param Mage_Sales_Model_Order $order
     * function builds the necessary DOM document and returns it
     * this result will be used in the API calls as post data
     * @return DOMDocument
     */
    public function getCreateOrderDomDocument($order, $returnOrder = false){
        //initialize helper
        $configHelper = Mage::helper("bpost_shm/system_config");
        $bpostHelper = Mage::helper("bpost_shm");
        $shippingMethod = $order->getShippingMethod();
        $storeId = $order->getStoreId();

        //get sender
        $senderData = new Varien_Object();
        $senderData->setBpostName($configHelper->getBpostShippingConfig("sender_name", $storeId));
        $senderData->setBpostCompany($configHelper->getBpostShippingConfig("sender_company", $storeId));
        $senderData->setBpostStreet($configHelper->getBpostShippingConfig("sender_streetname", $storeId));
        $senderData->setBpostHouseNumber($configHelper->getBpostShippingConfig("sender_streetnumber", $storeId));
        $senderData->setBoxNumber($configHelper->getBpostShippingConfig("sender_boxnumber", $storeId));
        $senderData->setPostcode($configHelper->getBpostShippingConfig("sender_postal_code", $storeId));
        $senderData->setCity($configHelper->getBpostShippingConfig("sender_city", $storeId));
        $senderData->setCountryId($configHelper->getBpostShippingConfig("sender_country", $storeId));
        $senderData->setEmail($configHelper->getBpostShippingConfig("sender_email", $storeId));
        $senderData->setTelephone(preg_replace('/[^0-9]/s', '',$configHelper->getBpostShippingConfig("sender_phonenumber", $storeId)));

        //get receiver
        $this->_shippingAddress = $order->getShippingAddress();
        $this->_shippingAddress = $bpostHelper->formatAddress($this->_shippingAddress);
        $this->_billingAddress = $order->getBillingAddress();

        //if return order, we switch the sender and receiver data
        if($returnOrder){
            $tmpShippingAddress = $this->_shippingAddress;
            $this->_shippingAddress = $senderData;
            $senderData = $tmpShippingAddress;

            if($shippingMethod != "bpostshm_bpost_international" && $shippingMethod != "bpostshm_bpost_homedelivery"){
                $senderData->setBpostName($this->_billingAddress->getFirstname()." ".$this->_billingAddress->getLastname());
            }

            if($shippingMethod != "bpostshm_bpost_international"){
                //we force shipping to 'bpostshm_bpost_homedelivery' method because we are creating a return order
                $shippingMethod = "bpostshm_bpost_homedelivery";
            }
        }

        $document = new DOMDocument('1.0','UTF-8');
        $document->formatOutput = true;
        $orderElement = $document->createElement('tns:order');

        //we add the extra attributes to the order element
        $xmlnsAttribute = $document->createAttribute("xmlns");
        $xmlnsAttribute->value = $this->_xmlnsValue;

        $xmlnsCommonAttribute = $document->createAttribute("xmlns:common");
        $xmlnsCommonAttribute->value = $this->_xmlnsCommonValue;

        $xmlnsTnsAttribute = $document->createAttribute("xmlns:tns");
        $xmlnsTnsAttribute->value = $this->_xmlnsTnsValue;

        $xmlnsIAttribute = $document->createAttribute("xmlns:international");
        $xmlnsIAttribute->value = $this->_xmlnsInternational;

        $xmlnsXsiAttribute = $document->createAttribute("xmlns:xsi");
        $xmlnsXsiAttribute->value = $this->_xmlnsXsiValue;

        $xsiSchemaLocation = $document->createAttribute("xsi:schemaLocation");
        $xsiSchemaLocation->value = $this->_xsiSchemaLocation;

        $orderElement->appendChild($xmlnsAttribute);
        $orderElement->appendChild($xmlnsCommonAttribute);
        $orderElement->appendChild($xmlnsTnsAttribute);
        $orderElement->appendChild($xmlnsIAttribute);
        $orderElement->appendChild($xmlnsXsiAttribute);
        $orderElement->appendChild($xsiSchemaLocation);
        //end adding order attributes

        $document->appendChild($orderElement);

        //add order elements
        $accountId = $document->createElement('tns:accountId');
        $accountId->appendChild($document->createTextNode($this->_accountId));
        $orderElement->appendChild($accountId);

        $reference = $document->createElement('tns:reference');
        $referenceId = $order->getIncrementId();

        $items = $order->getAllItems();
        $reference->appendChild($document->createTextNode($referenceId));
        $orderElement->appendChild($reference);

        //calculate weight
        $totalShipmentsWeight = $bpostHelper->processShipmentsWeight($order);

        //we add all order items to the document
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $nbOfItems = $document->createElement('tns:nbOfItems');
            $nbOfItems->appendChild($document->createTextNode((int)$item->getQtyOrdered()));

            $text = $document->createElement('tns:text');
            $text->appendChild($document->createTextNode($item->getName()));

            $orderLine = $document->createElement('tns:orderLine');
            $orderLine->appendChild($text);
            $orderLine->appendChild($nbOfItems);
            $orderElement->appendChild($orderLine);
        }

        $weightInGrams = round($totalShipmentsWeight);

        if(!$weightInGrams){
            $weightInGrams = 1;
        }

        if(($shippingMethod == "bpostshm_bpost_parcellocker" || $shippingMethod == "bpostshm_bpost_international" || $shippingMethod == "bpostshm_bpost_clickcollect") && $weightInGrams < 10){
            $weightInGrams = 10;
        }
        //end weight

        $senderName = $document->createElement('common:name');
        $senderName->appendChild($document->createTextNode($senderData->getBpostName()));

        $senderCompany = $document->createElement('common:company');
        $senderCompany->appendChild($document->createTextNode($senderData->getBpostCompany()));
        //sender address fields here
        $addressStreetName = $document->createElement('common:streetName');
        $addressStreetName->appendChild($document->createTextNode($senderData->getBpostStreet()));

        $addressNumber = $document->createElement('common:number');
        $addressNumber->appendChild($document->createTextNode($senderData->getBpostHouseNumber()));

        //only add element if available
        $boxNumber = $configHelper->getBpostShippingConfig($senderData->getBoxNumber(), $storeId);

        if($boxNumber && $boxNumber != ''){
            $addressBox = $document->createElement('common:box');
            $addressBox->appendChild($document->createTextNode($boxNumber));
        }

        $addressPostalCode = $document->createElement('common:postalCode');
        $addressPostalCode->appendChild($document->createTextNode($senderData->getPostcode()));

        $addressLocality = $document->createElement('common:locality');
        $addressLocality->appendChild($document->createTextNode($senderData->getCity()));

        $addressCountryCode = $document->createElement('common:countryCode');
        $addressCountryCode->appendChild($document->createTextNode($senderData->getCountryId()));
        //end sender address fields

        //add fields to address
        $senderAddress = $document->createElement('common:address');
        $senderAddress->appendChild($addressStreetName);
        $senderAddress->appendChild($addressNumber);

        if(isset($addressBox)){
            $senderAddress->appendChild($addressBox);
        }

        $senderAddress->appendChild($addressPostalCode);
        $senderAddress->appendChild($addressLocality);
        $senderAddress->appendChild($addressCountryCode);

        $senderEmail = $document->createElement('common:emailAddress');
        $senderEmail->appendChild($document->createTextNode($senderData->getEmail()));

        $senderPhoneNumber = $document->createElement('common:phoneNumber');
        $senderPhoneNumber->appendChild($document->createTextNode(preg_replace('/[^0-9]/s', '',$senderData->getTelephone())));

        //add all sender info to the sender element
        $sender = $document->createElement('tns:sender');
        $sender->appendChild($senderName);
        $sender->appendChild($senderCompany);
        $sender->appendChild($senderAddress);
        $sender->appendChild($senderEmail);
        $sender->appendChild($senderPhoneNumber);

        //we create the box element
        $box = $document->createElement('tns:box');
        $box->appendChild($sender);

        //add the national or international box element tags
        if($shippingMethod != "bpostshm_bpost_international"){
            $nationalBoxElements = $this->_getNationalBoxElements($document, $order, $returnOrder, $weightInGrams, $shippingMethod);
            $box->appendChild($nationalBoxElements);
        }else{
            $internationalElements = $this->_getInternationalBoxElements($document, $order, $returnOrder, $weightInGrams);
            $box->appendChild($internationalElements);
        }

        //we do the same for additionalCustomerRef
        //Free text. If not submitted, it will indicate the channel used for creating this order. Best used by integrators to indicate the origin of the order.

        $additionalCustomerRef = $document->createElement('tns:additionalCustomerReference');
        $additionalCustomerRef->appendChild($document->createTextNode("Magento_".Mage::getVersion()));
        $box->appendChild($additionalCustomerRef);

        //finally we add the box element to the order element
        $orderElement->appendChild($box);

        //we save the xml so we can check the file
        return $document;
    }

    //function adds the national box element tags based on the shipping method
    protected function _getNationalBoxElements(DOMDocument $document, $order, $returnOrder, $weightInGrams, $shippingMethod){
        $bpostHelper = Mage::helper("bpost_shm");
        $configHelper = Mage::helper("bpost_shm/system_config");
        $nationalBox = $document->createElement('tns:nationalBox');
        $manageLabels = $configHelper->getBpostShippingConfig("manage_labels_with_magento");

        $reqDeliveryDate = false;
        $dropDate = new DateTime($order->getBpostDeliveryDate());
        $currentDate = new DateTime();

        if($dropDate && $dropDate > $currentDate && !$returnOrder) {
            $reqDeliveryDate = $document->createElement('requestedDeliveryDate');
            $reqDeliveryDate->appendChild($document->createTextNode($order->getBpostDeliveryDate()));
        }

        //add product
        $product = $document->createElement('product');

        //add options
        $options = $document->createElement('options');

        //add weight
        $weight = $document->createElement('weight');
        $weight->appendChild($document->createTextNode($weightInGrams));

        //add address variables
        $streetName = $document->createElement('common:streetName');
        $streetNumber = $document->createElement('common:number');
        $streetPostalCode = $document->createElement('common:postalCode');
        $locality = $document->createElement('common:locality');
        $countryCode = $document->createElement('common:countryCode');

        //add receiver info
        $receiverName = $document->createElement('receiverName');
        $receiverCompany = $document->createElement('receiverCompany');
        $receiverCompany->appendChild($document->createTextNode($this->_shippingAddress->getCompany()));

        //we use the receivername from billing address instead of shipping address
        //this way we have the customer name and not the pickup point name
        $receiverName->appendChild($document->createTextNode($this->_billingAddress->getFirstname()." ".$this->_billingAddress->getLastname()));

        switch($shippingMethod){
            case "bpostshm_bpost_homedelivery":
                if(!$returnOrder){
                    $productType = $configHelper->getBpostCarriersConfig("product", "bpost_homedelivery", $order->getStoreId());
                    ($productType == 0 ? $productType = "bpack 24h Pro" : $productType = "bpack 24h business");

                    $product->appendChild($document->createTextNode($productType));

                    //then get config the option settings
                    $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_homedelivery", "second_presentation", "automaticSecondPresentation");
                    $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_homedelivery", "insurance", "insured");
                    $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_homedelivery", "signature", "signed");
                    $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_homedelivery", "saturday_delivery", "saturdayDelivery");

                }else{
                    $product->appendChild($document->createTextNode("bpack Easy Retour"));
                }

                //add receiver info
                $receiverName = $document->createElement('common:name');
                $receiverName->appendChild($document->createTextNode($this->_shippingAddress->getBpostName()));

                $receiverCompany = $document->createElement('common:company');
                $receiverCompany->appendChild($document->createTextNode($this->_shippingAddress->getBpostCompany()));

                $receiverEmailAddress = $document->createElement('common:emailAddress');
                $receiverEmailAddress->appendChild($document->createTextNode($this->_billingAddress->getEmail()));

                $receiverPhoneNumber = $document->createElement('common:phoneNumber');
                $receiverPhoneNumber->appendChild($document->createTextNode(preg_replace('/[^0-9]/s', '',$this->_shippingAddress->getTelephone())));

                //we add the receiver address data
                $streetName->appendChild($document->createTextNode($this->_shippingAddress->getBpostStreet()));
                $streetNumber->appendChild($document->createTextNode($this->_shippingAddress->getBpostHouseNumber()));

                $streetPostalCode->appendChild($document->createTextNode($this->_shippingAddress->getPostcode()));
                $locality->appendChild($document->createTextNode($this->_shippingAddress->getCity()));
                $countryCode->appendChild($document->createTextNode($this->_shippingAddress->getCountryId()));

                $receiverAddress = $document->createElement('common:address');
                $receiverAddress->appendChild($streetName);
                $receiverAddress->appendChild($streetNumber);

                //only add element if available
                if($this->_shippingAddress->getBoxNumber()){
                    $streetBox = $document->createElement('common:box');
                    $streetBox->appendChild($document->createTextNode($this->_shippingAddress->getBoxNumber()));
                    $receiverAddress->appendChild($streetBox);
                }

                $receiverAddress->appendChild($streetPostalCode);
                $receiverAddress->appendChild($locality);
                $receiverAddress->appendChild($countryCode);

                $receiver = $document->createElement('receiver');
                $receiver->appendChild($receiverName);
                $receiver->appendChild($receiverCompany);
                $receiver->appendChild($receiverAddress);
                $receiver->appendChild($receiverEmailAddress);
                $receiver->appendChild($receiverPhoneNumber);
                //end receiver

                $atHome = $document->createElement('atHome');
                $atHome->appendChild($product);

                if($options->hasChildNodes()){
                    $atHome->appendChild($options);
                }

                $atHome->appendChild($weight);
                $atHome->appendChild($receiver);

                if($reqDeliveryDate){
                    $atHome->appendChild($reqDeliveryDate);
                }

                $nationalBox->appendChild($atHome);
            break;

            case "bpostshm_bpost_pickuppoint":
                if(!$order->getBpostPickuplocationId()){
                    //only throw error in backend
                    if($manageLabels){
                        Mage::throwException("No pickup location order data found.");
                    }
                }

                $product->appendChild($document->createTextNode("bpack@bpost"));

                //add the notification options
                //check if bpost notification sms or mail isset
                if($order->getBpostNotificationSms() || $order->getBpostNotificationEmail()){
                    if($order->getBpostNotificationSms()){
                        $options->appendChild($this->_addNotificationMessageOption($document, $order, "keepMeInformed", "sms"));
                    }else{
                        $options->appendChild($this->_addNotificationMessageOption($document, $order, "keepMeInformed"));
                    }
                }

                //then get the config option settings
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_pickuppoint", "insurance", "insured");
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_pickuppoint", "saturday_delivery", "saturdayDelivery");

                $formattedAddress = $bpostHelper->formatShippingAddress($this->_shippingAddress);

                $pugoId = $document->createElement('pugoId');
                $pugoId->appendChild($document->createTextNode($order->getBpostPickuplocationId()));

                $pugoName = $document->createElement('pugoName');
                $pugoName->appendChild($document->createTextNode($this->_shippingAddress->getLastname()));

                //Address tags
                $streetName->appendChild($document->createTextNode($formattedAddress["street"]));
                $streetNumber->appendChild($document->createTextNode($formattedAddress["number"]));

                $streetPostalCode->appendChild($document->createTextNode($formattedAddress["postcode"]));
                $locality->appendChild($document->createTextNode($formattedAddress["city"]));
                $countryCode->appendChild($document->createTextNode($this->_shippingAddress->getCountryId()));

                $pugoAddress = $document->createElement('pugoAddress');
                $pugoAddress->appendChild($streetName);
                if (null !== $formattedAddress["number"]) {
                    $pugoAddress->appendChild($streetNumber);
                }
                $pugoAddress->appendChild($streetPostalCode);
                $pugoAddress->appendChild($locality);
                $pugoAddress->appendChild($countryCode);
                //end address tags

                $atBpost = $document->createElement('atBpost');
                $atBpost->appendChild($product);

                if($options->hasChildNodes()){
                    $atBpost->appendChild($options);
                }

                $atBpost->appendChild($weight);
                $atBpost->appendChild($pugoId);
                $atBpost->appendChild($pugoName);
                $atBpost->appendChild($pugoAddress);
                $atBpost->appendChild($receiverName);
                $atBpost->appendChild($receiverCompany);

                if($reqDeliveryDate) {
                    $atBpost->appendChild($reqDeliveryDate);
                }

                $nationalBox->appendChild($atBpost);

                break;

            case "bpostshm_bpost_parcellocker":

                if(!$order->getBpostPickuplocationId() && $manageLabels){
                        Mage::throwException("No parcel locker data found.");
                }

                $product->appendChild($document->createTextNode("bpack 24h Pro"));

                //then get the config option settings
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_parcellocker", "insurance", "insured");
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_parcellocker", "saturday_delivery", "saturdayDelivery");

                $formattedAddress = $bpostHelper->formatShippingAddress($this->_shippingAddress);

                $parcelsDepotId = $document->createElement('parcelsDepotId');
                $parcelsDepotId->appendChild($document->createTextNode($order->getBpostPickuplocationId()));

                $parcelsDepotName = $document->createElement('parcelsDepotName');
                $parcelsDepotName->appendChild($document->createTextNode($this->_shippingAddress->getLastname()));

                //Address tags
                $streetName->appendChild($document->createTextNode($formattedAddress["street"]));
                $streetNumber->appendChild($document->createTextNode($formattedAddress["number"]));

                $streetPostalCode->appendChild($document->createTextNode($formattedAddress["postcode"]));
                $locality->appendChild($document->createTextNode($formattedAddress["city"]));
                $countryCode->appendChild($document->createTextNode($this->_shippingAddress->getCountryId()));

                $parcelsDepotAddress = $document->createElement('parcelsDepotAddress');
                $parcelsDepotAddress->appendChild($streetName);
                if (null !== $formattedAddress["number"]) {
                    $parcelsDepotAddress->appendChild($streetNumber);
                }
                $parcelsDepotAddress->appendChild($streetPostalCode);
                $parcelsDepotAddress->appendChild($locality);
                $parcelsDepotAddress->appendChild($countryCode);
                //end address tags

                //add unregistered element
                $unregistered = $document->createElement('unregistered');

                //message language
                //we take the locale from the store where the order is placed from
                $locale = $bpostHelper->getLocaleByOrder($order);
                $messageLanguage = $document->createElement('language');
                $messageLanguage->appendChild($document->createTextNode($locale));

                $email = $document->createElement('emailAddress');
                $email->appendChild($document->createTextNode($this->_billingAddress->getEmail()));

                //Indication if this user has reduced mobility Y/N
                //comes from frontend
                $reducedMobilityZone = $document->createElement('reducedMobilityZone');
                $unregistered->appendChild($messageLanguage);

                if($order->getBpostNotificationSms()){
                    $mobilePhone = $document->createElement('mobilePhone');
                    $mobilePhone->appendChild($document->createTextNode(preg_replace('/[^0-9]/s', '',$this->_shippingAddress->getTelephone())));
                    $unregistered->appendChild($mobilePhone);
                }

                $unregistered->appendChild($email);

                if($order->getBpostReducedMobility()){
                    $unregistered->appendChild($reducedMobilityZone);
                }
                //end unregistered element

                $atTwentyFourSeven = $document->createElement('at24-7');
                $atTwentyFourSeven->appendChild($product);

                if($options->hasChildNodes()){
                    $atTwentyFourSeven->appendChild($options);
                }


                $atTwentyFourSeven->appendChild($weight);
                $atTwentyFourSeven->appendChild($parcelsDepotId);
                $atTwentyFourSeven->appendChild($parcelsDepotName);
                $atTwentyFourSeven->appendChild($parcelsDepotAddress);

                //add unregistered element
                $atTwentyFourSeven->appendChild($unregistered);
                $atTwentyFourSeven->appendChild($receiverName);
                $atTwentyFourSeven->appendChild($receiverCompany);

                if($reqDeliveryDate) {
                    $atTwentyFourSeven->appendChild($reqDeliveryDate);
                }

                $nationalBox->appendChild($atTwentyFourSeven);
                break;

            case "bpostshm_bpost_clickcollect":
                if(!$order->getBpostPickuplocationId()){
                    //only throw error in backend
                    if($manageLabels){
                        Mage::throwException("No pickup location order data found.");
                    }
                }

                $product->appendChild($document->createTextNode("bpack Click & Collect"));

                //add the notification options
                //check if bpost notification sms or mail isset
                if($order->getBpostNotificationSms() || $order->getBpostNotificationEmail()){
                    if($order->getBpostNotificationSms()){
                        $options->appendChild($this->_addNotificationMessageOption($document, $order, "keepMeInformed", "sms"));
                    }else{
                        $options->appendChild($this->_addNotificationMessageOption($document, $order, "keepMeInformed"));
                    }
                }

                //then get the config option settings
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_clickcollect", "insurance", "insured");
                $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_clickcollect", "saturday_delivery", "saturdayDelivery");

                $formattedAddress = $bpostHelper->formatShippingAddress($this->_shippingAddress);

                $pugoId = $document->createElement('pugoId');
                $pugoId->appendChild($document->createTextNode($order->getBpostPickuplocationId()));

                $pugoName = $document->createElement('pugoName');
                $pugoName->appendChild($document->createTextNode($this->_shippingAddress->getLastname()));

                //Address tags
                $streetName->appendChild($document->createTextNode($formattedAddress["street"]));
                $streetNumber->appendChild($document->createTextNode($formattedAddress["number"]));

                $streetPostalCode->appendChild($document->createTextNode($formattedAddress["postcode"]));
                $locality->appendChild($document->createTextNode($formattedAddress["city"]));
                $countryCode->appendChild($document->createTextNode($this->_shippingAddress->getCountryId()));

                $pugoAddress = $document->createElement('pugoAddress');
                $pugoAddress->appendChild($streetName);
                if (null !== $formattedAddress["number"]) {
                    $pugoAddress->appendChild($streetNumber);
                }
                $pugoAddress->appendChild($streetPostalCode);
                $pugoAddress->appendChild($locality);
                $pugoAddress->appendChild($countryCode);
                //end address tags

                $atBpost = $document->createElement('atBpost');
                $atBpost->appendChild($product);

                if($options->hasChildNodes()){
                    $atBpost->appendChild($options);
                }

                $atBpost->appendChild($weight);
                $atBpost->appendChild($pugoId);
                $atBpost->appendChild($pugoName);
                $atBpost->appendChild($pugoAddress);
                $atBpost->appendChild($receiverName);
                $atBpost->appendChild($receiverCompany);

                if($reqDeliveryDate) {
                    $atBpost->appendChild($reqDeliveryDate);
                }

                $nationalBox->appendChild($atBpost);

                break;
        }

        return $nationalBox;
    }

    //function adds the international box element tags
    protected function _getInternationalBoxElements(DOMDocument $document, $order, $returnOrder, $weightInGrams){
        $configHelper =  Mage::helper("bpost_shm/system_config");
        $storeId = $order->getStoreId();

        //start creating xml
        $internationalBox = $document->createElement('tns:internationalBox');
        $internationalWrapper = $document->createElement('international:international');

        if(!$returnOrder){
            $productType = $configHelper->getBpostCarriersConfig("product", "bpost_international", $storeId);
            ($productType == 0 ? $productType = "bpack World Business" : $productType = "bpack World Express Pro");
        }else{
            $productType = "bpack World Easy Return";
        }

        //add product
        $internationalProduct = $document->createElement('international:product');
        $internationalProduct->appendChild($document->createTextNode($productType));

        //add options
        $options = $document->createElement('international:options');

        //not possible yet, maybe in feature
        if($productType == "bpack Europe Business"){
            $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_international", "second_presentation", "automaticSecondPresentation");
        }

        $options = $this->_checkIfOptionIsValid($document, $options, $order, "bpost_international", "insurance", "insured");

        //add receiver info
        $receiverName = $document->createElement('common:name');
        $receiverName->appendChild($document->createTextNode($this->_shippingAddress->getBpostName()));

        $receiverCompany = $document->createElement('common:company');
        $receiverCompany->appendChild($document->createTextNode($this->_shippingAddress->getBpostCompany()));

        $receiverEmailAddress = $document->createElement('common:emailAddress');
        $receiverEmailAddress->appendChild($document->createTextNode($this->_billingAddress->getEmail()));

        $receiverPhoneNumber = $document->createElement('common:phoneNumber');
        $receiverPhoneNumber->appendChild($document->createTextNode(preg_replace('/[^0-9]/s', '',$this->_shippingAddress->getTelephone())));

        //add address info
        $streetName = $document->createElement('common:streetName');
        $streetName->appendChild($document->createTextNode($this->_shippingAddress->getBpostStreet()));

        $streetNumber = $document->createElement('common:number');
        $streetNumber->appendChild($document->createTextNode($this->_shippingAddress->getBpostHouseNumber()));

        $streetPostalCode = $document->createElement('common:postalCode');
        $streetPostalCode->appendChild($document->createTextNode($this->_shippingAddress->getPostcode()));

        $locality = $document->createElement('common:locality');
        $locality->appendChild($document->createTextNode($this->_shippingAddress->getCity()));

        $countryCode = $document->createElement('common:countryCode');
        $countryCode->appendChild($document->createTextNode($this->_shippingAddress->getCountryId()));

        $receiverAddress = $document->createElement('common:address');
        $receiverAddress->appendChild($streetName);
        $receiverAddress->appendChild($streetNumber);

        //only add element if available
        if($this->_shippingAddress->getBoxNumber()){
            $streetBox = $document->createElement('common:box');
            $streetBox->appendChild($document->createTextNode($this->_shippingAddress->getBoxNumber()));
            $receiverAddress->appendChild($streetBox);
        }

        $receiverAddress->appendChild($streetPostalCode);
        $receiverAddress->appendChild($locality);
        $receiverAddress->appendChild($countryCode);
        //end address info

        //add receiver
        $receiver = $document->createElement('international:receiver');

        $receiver->appendChild($receiverName);
        $receiver->appendChild($receiverCompany);
        $receiver->appendChild($receiverAddress);
        $receiver->appendChild($receiverEmailAddress);
        $receiver->appendChild($receiverPhoneNumber);

        //create weight element
        $weight = $document->createElement('international:parcelWeight');
        $weight->appendChild($document->createTextNode($weightInGrams));

        //create customsInfo element
        $customsInfo = $document->createElement('international:customsInfo');

        //create customsInfo child elements
        $parcelValue = $document->createElement('international:parcelValue');
        
        $grandTotalExclDiscount = (round(($order->getGrandTotal()+abs($order->getDiscountAmount())), 2) * 100);
        if ($grandTotalExclDiscount < 100) {
            $grandTotalExclDiscount = 100;
        }

        $parcelValue->appendChild($document->createTextNode($grandTotalExclDiscount));

        $contentDescription = $document->createElement('international:contentDescription');
        $contentDescription->appendChild($document->createTextNode("no description"));

        $shipmentType = $document->createElement('international:shipmentType');
        $shipmentType->appendChild($document->createTextNode("OTHER"));

        $returnInstructions = $document->createElement('international:parcelReturnInstructions');
        $returnInstructions->appendChild($document->createTextNode("RTS"));

        $privateAddress = $document->createElement('international:privateAddress');
        $privateAddress->appendChild($document->createTextNode("false"));

        $customsInfo->appendChild($parcelValue);
        $customsInfo->appendChild($contentDescription);
        $customsInfo->appendChild($shipmentType);
        $customsInfo->appendChild($returnInstructions);
        $customsInfo->appendChild($privateAddress);
        //end customsInfo element

        //add elements to the international wrapper
        $internationalWrapper->appendChild($internationalProduct);

        if($options->hasChildNodes()){
            $internationalWrapper->appendChild($options);
        }

        $internationalWrapper->appendChild($receiver);
        $internationalWrapper->appendChild($weight);
        $internationalWrapper->appendChild($customsInfo);

        $internationalBox->appendChild($internationalWrapper);
        return $internationalBox;
    }

    /**
     * function checks if the an option is active / valid
     * and add it's if necessary
     */
    protected function _checkIfOptionIsValid(DOMDocument $document, $options, $order, $carrier, $configName, $bpostValue){
        $helper = Mage::helper("bpost_shm/system_config");
        $storeId = $order->getStoreId();
        $option = $helper->getBpostCarriersConfig($configName, $carrier, $storeId);
        $deliveryDate = $order->getBpostDeliveryDate();
        $dateModel = Mage::getSingleton('core/date');

        if($dateModel->date('N', strtotime($deliveryDate)) != 6 &&
            $bpostValue == "saturdayDelivery" &&
            Mage::helper("bpost_shm/system_config")->getBpostShippingConfig("display_delivery_date",$storeId)){
            return $options;
        }

        if($option){
            $optionFrom = $helper->getBpostCarriersConfig($configName."_from", $carrier, $storeId);
            $addOption = false;

            if($optionFrom && $optionFrom > 0){
                if($order->getGrandTotal() >= $optionFrom){
                    $addOption = true;
                }
            }else{
                $addOption = true;
            }

            if($addOption){
                $option = $document->createElement("common:$bpostValue");

                //check for insurance option
                //if so, we need to add an extra child
                if($bpostValue == "insured"){
                    $insuranceElement = $document->createElement("common:basicInsurance");
                    $option->appendChild($insuranceElement);
                }

                $options->appendChild($option);
            }
        }

        return $options;
    }

    /**
     * function adds a notification child element to the options element
     */
    protected function _addNotificationMessageOption(DOMDocument $document, $order, $notificationType, $communicationBy = "email"){
        $bpostHelper = Mage::helper("bpost_shm");

        $notificationElement = $document->createElement("common:$notificationType");
        $language = $bpostHelper->getLocaleByOrder($order, true);

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $languageAttribute = $document->createAttribute("language");
        $languageAttribute->value = $language;
        $notificationElement->appendChild($languageAttribute);

        if($communicationBy === "email"){
            $childElement = $document->createElement("common:emailAddress");
            $childElement->appendChild($document->createTextNode($billingAddress->getEmail()));
        }else{
            $childElement = $document->createElement("common:mobilePhone");
            $childElement->appendChild($document->createTextNode(preg_replace('/[^0-9]/s', '',$shippingAddress->getTelephone())));
        }

        $notificationElement->appendChild($childElement);

        return $notificationElement;
    }
}
