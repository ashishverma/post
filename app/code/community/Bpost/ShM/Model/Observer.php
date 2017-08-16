<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Observer
 */
class Bpost_ShM_Model_Observer extends Varien_Event_Observer
{
    /**
     * @param $observer
     * @return $this
     */
    public function core_block_abstract_to_html_after($observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();

        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available) {
            $availablerates = $observer->getBlock()->getShippingRates();

            if (array_key_exists("bpostshm", $availablerates)) {
                //get HTML
                $html = $observer->getTransport()->getHtml();

                //add logo to carrier
                $logo = Mage::getDesign()->getSkinUrl("images/bpost/bpost_sym_RGB72_S.png");

                $html = str_replace('<dt>bpost</dt>', '<dt><img src="'.$logo.'" class="bpost-carrier-logo" />bpost</dt>', $html);
                //if onestepcheckout dd's are used instead of dt's
                $html = str_replace('<dd>bpost</dd>', '<dd><img src="'.$logo.'" class="bpost-carrier-logo" />bpost</dd>', $html);

                //intercept html and append block
                if(strpos($html, 'bpost-carrier-logo')){
                    $html .= Mage::app()->getLayout()->createBlock("bpost_shm/carrier_bpost")->setQuote($quote)->setTemplate("bpost/shm/append_bpost_shippingmethod.phtml")->toHtml();
                }
                //set HTML
                $observer->getTransport()->setHtml($html);
            }
        }

        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
            if (Mage::getSingleton('checkout/session')->getBpostReloadProgress()) {
                $html = $observer->getTransport()->getHtml();
                $html .= "<script>checkout.reloadProgressBlock('shipping');</script>";
                $observer->getTransport()->setHtml($html);
                Mage::getSingleton('checkout/session')->unsBpostReloadProgress();
            }
        }

        return $this;
    }

    /**
     * Observe shipping address and create alternative shipping address in the session. (we select only the necessary data to keep the object small)
     *
     * @param $observer
     * @return $this
     */
    public function controller_action_postdispatch_checkout_onepage_saveAddress($observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $shippingAddress = $checkoutSession->getQuote()->getShippingAddress();
        if (((bool)$shippingAddress->getSameAsBilling() &&
            strtolower($observer->getEvent()->getName()) == "controller_action_postdispatch_checkout_onepage_savebilling") ||
            strtolower($observer->getEvent()->getName()) == "controller_action_postdispatch_checkout_onepage_saveshipping") {

            $originalAddress = new Varien_Object();
            $street = $shippingAddress->getStreet(1);

            if($shippingAddress->getStreet(2) && $shippingAddress->getStreet(2) != ""){
                $street .= "\n" . $shippingAddress->getStreet(2);
            }

            $originalAddress
                ->setFirstname($shippingAddress->getFirstname())
                ->setLastname($shippingAddress->getLastname())
                ->setCompany($shippingAddress->getCompany())
                ->setStreet($street)
                ->setCity($shippingAddress->getCity())
                ->setRegion($shippingAddress->getRegion())
                ->setPostcode($shippingAddress->getPostcode())
                ->setCountryId($shippingAddress->getCountryId())
                ->setTelephone($shippingAddress->getTelephone())
                ->setFax($shippingAddress->getFax());

            if($shippingAddress->getAddressId() != "" && $shippingAddress->hasAddressId()){
                $originalAddress->setAddressId($shippingAddress->getAddressId());
            }

            $checkoutSession->setBpostOriginalShippingAddress($originalAddress);
        }
        return $this;
    }

    /**
     * Reset saturday delivery option so rate calculation doesn't show saturday incl. shipping rates
     *
     * @return $this
     */
    public function controller_action_predispatch_checkout_onepage_saveAddress()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        //always reset the saturday delivery option so we don't see multiplied rates
        $checkoutSession->getQuote()->setBpostDisableSaturdayDelivery(true);

        return $this;
    }

    /**
     * Change shipping address if needed, save previous shipping method, add progress bar reload flag.
     *
     * @return $this
     */
    public function checkout_controller_onepage_save_shipping_method()
    {
        //init all necessary data
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $address = $quote->getShippingAddress();

        //get the bpost data
        $params = Mage::app()->getRequest()->getPost();

        //add notifications to quote
        if($address->getShippingMethod() == "bpostshm_bpost_pickuppoint" ||
            $address->getShippingMethod() == "bpostshm_bpost_clickcollect"){
            if(isset($params["notification"])){
                if($params["notification"] == "email"){
                    $quote->setBpostNotificationEmail($address->getEmail());
                    $quote->setBpostNotificationSms("");

                }else{
                    $quote->setBpostNotificationSms($address->getTelephone());
                    $quote->setBpostNotificationEmail("");
                }
            }
        }elseif($address->getShippingMethod() == "bpostshm_bpost_parcellocker"){
            if(isset($params["parcel-notification"])){
                $quote->setBpostNotificationSms($address->getTelephone());
            }else{
                $quote->setBpostNotificationSms("");
                $quote->setBpostNotificationEmail("");
            }

            //check for reduced mobility
            if(isset($params["reduced-mobility"])){
                $quote->setBpostReducedMobility(true);
            }else{
                $quote->setBpostReducedMobility(false);
            }
        }

        //set the delivery date in the quote
        if(isset($params["bpost"]["deliverydate"])) {
            if($address->getShippingMethod() != "bpostshm_bpost_international" && $params["bpost"]["deliverydate"] != ""){
                $bpostHelper = Mage::helper("bpost_shm");

                $deliveryDate = $params["bpost"]["deliverydate"];
                $dropdate = $bpostHelper->getPrevDeliveryDate($deliveryDate);

                $quote->setBpostDropDate($dropdate);
                $quote->setBpostDeliveryDate($deliveryDate);
            }
        }

        //if all necessary fields are filled in.
        $validShippingMethods = array("bpostshm_bpost_pickuppoint", "bpostshm_bpost_parcellocker", "bpostshm_bpost_clickcollect");

        if (in_array($address->getShippingMethod(), $validShippingMethods) && $params["bpost"]["id"] && $params["bpost"]["name"] && $params["bpost"]["street"]
            && $params["bpost"]["city"] && $params["bpost"]["postcode"]
        ) {
            //set it in the quote
            $quote->setBpostPickuplocationId($params["bpost"]["id"]);

            if($address->getShippingMethod() == "bpostshm_bpost_pickuppoint") {
                $firstname = "bpost pick-up point: ";
            } elseif($address->getShippingMethod() == "bpostshm_bpost_parcellocker") {
                $firstname = "bpost parcel locker: ";
            } elseif($address->getShippingMethod() == "bpostshm_bpost_clickcollect") {
                $firstname = "bpost Click & Collect point: ";
            }

            //now set the current address to the bpost-spot
            $address->unsetAddressId()
                ->setSaveInAddressBook(0)
                ->setFirstname($firstname)
                ->setLastname($params["bpost"]["name"])
                ->setStreet($params["bpost"]["street"])
                ->setCity($params["bpost"]["city"])
                ->setPostcode($params["bpost"]["postcode"])
                ->save();
        } else {
            //revert to original shipping address if no pickup point or parcel locker is chosen
            if($checkoutSession->hasBpostOriginalShippingAddress()) {
                $originalAddress = $checkoutSession->getBpostOriginalShippingAddress();
                $address->unsetAddressId()
                    ->setSaveInAddressBook(0)
                    ->setFirstname($originalAddress->getFirstname())
                    ->setLastname($originalAddress->getLastname())
                    ->setStreet($originalAddress->getStreet())
                    ->setCity($originalAddress->getCity())
                    ->setPostcode($originalAddress->getPostcode())
                    ->save();
            }
        }


        //set saturday delivery option flag so shipping prices incl. saturday delivery are calculated
        $dateModel = Mage::getSingleton('core/date');
        if(isset($params['bpost']['deliverydate']) && ($dateModel->date('N', strtotime($params['bpost']['deliverydate'])) == 6)){
            $quote->setData("bpost_disable_saturday_delivery", false);
        }elseif(isset($params['bpost']['deliverydate']) && !($dateModel->date('N', strtotime($params['bpost']['deliverydate'])) == 6)){
            $quote->setData("bpost_disable_saturday_delivery", true);
        }
        elseif(isset($params['bpost_saturday_delivery']) && !$params['bpost_saturday_delivery']) {
            $quote->setData("bpost_disable_saturday_delivery", false);
        } elseif(isset($params['bpost_saturday_delivery']) && $params['bpost_saturday_delivery']) {
            $quote->setData("bpost_disable_saturday_delivery", true);
        }

        //re-collect our rates
        $address->setCollectShippingRates(true);

        //add a flag for progress reload
        $checkoutSession->setBpostReloadProgress(true);

        //set this so we know what the previous method was.
        $checkoutSession->setAlternativeShippingMethod($address->getShippingMethod());

        return $this;
    }

    /**
     * @param $observer
     * @return $this
     */
    public function checkout_submit_all_after($observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();

        $this->controller_action_postdispatch_checkout_onepage_saveAddress($observer);
        $this->checkout_controller_onepage_save_shipping_method();

        if (strpos($quote->getShippingAddress()->getShippingMethod(), "bpostshm_bpost") !== false) {
            $order = $observer->getEvent()->getOrder();

            $configHelper = Mage::helper("bpost_shm/system_config");

            $displayDeliveryDates = $configHelper->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId());
            $order->setBpostPickuplocationId($quote->getBpostPickuplocationId());

            if ($displayDeliveryDates) {
                $order->setBpostDisableSaturdayDelivery($quote->getBpostDisableSaturdayDelivery());
            }

            //function checks if manage labels with Magento is disabled
            //if so, we need to call the create order api function
            $manageLabels = $configHelper->getBpostShippingConfig("manage_labels_with_magento");

            if (!$manageLabels) {
                $apiModel = Mage::getModel("bpost_shm/api");
                $apiModel->initialize($order->getStoreId());
                $apiModel->createOrder($order);
                $order->setBpostReference($order->getIncrementId());
            }
            $order->save();
        }

        return $this;
    }


    /**
     * function sets default data on our quote
     * we need to do this for the onestepcheckout
     *
     * @param $observer
     * @return $this
     */
    public function sales_quote_collect_totals_before($observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $observer->getEvent()->getQuote();

        $params = Mage::app()->getRequest()->getParams();
        $originalRatePrices = $checkoutSession->getOriginalRatePrices();

        if(isset($params["shipping_method"]) && $originalRatePrices){
            $bpostConfigHelper = Mage::helper("bpost_shm/system_config");
            $bpostHelper = Mage::helper("bpost_shm");
            $shippingMethod = str_replace('bpostshm_', '', $params["shipping_method"]);

            foreach($quote->getShippingAddress()->getShippingRatesCollection() as $_rate){
                if($_rate->getCode() == $params["shipping_method"] && isset($originalRatePrices[$params["shipping_method"]])){
                    $saturdayDeliveryCost = $bpostHelper->formatSaturdayDeliveryCost($bpostConfigHelper->getBpostShippingConfig("saturday_delivery_cost", Mage::app()->getStore()->getId()));

                    if(isset($params["disable_saturday_delivery"]) && $params["disable_saturday_delivery"] == 1  && Mage::helper("bpost_shm/system_config")->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId())){
                        //check if the option saturday delivery is active for the current shipping method
                        if($bpostConfigHelper->getBpostCarriersConfig("saturday_delivery", $shippingMethod, Mage::app()->getStore()->getId())){
                            //remove extra cost
                            $_rate->setPrice($originalRatePrices[$params["shipping_method"]]);
                            $_rate->setSaturdayDelivery(0);
                            $quote->setBpostDisableSaturdayDelivery(true);
                        }

                    }elseif(isset($params["disable_saturday_delivery"]) && $params["disable_saturday_delivery"] == 0  && Mage::helper("bpost_shm/system_config")->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId())){
                        //add extra cost
                        if($bpostConfigHelper->getBpostCarriersConfig("saturday_delivery", $shippingMethod, Mage::app()->getStore()->getId())){
                            $_rate->setPrice($originalRatePrices[$params["shipping_method"]]+$saturdayDeliveryCost);
                            $_rate->setSaturdayDelivery(1);
                            //make sure the original prices are applied
                            $quote->setBpostDisableSaturdayDelivery(false);
                        }
                    }
                }
            }
            
            $quote->getShippingAddress()->setCollectShippingrates(true)->save();
        }


        if(Mage::getStoreConfig('onestepcheckout/general/rewrite_checkout_links', $quote->getStore())) {
            if (Mage::getSingleton('customer/session')->isLoggedIn() && (!$quote->getBpostNotificationSms() || !$quote->getBpostNotificationEmail())) {
                $customer = $quote->getCustomer();

                if(!$quote->getBpostNotificationEmail()){
                    $email = $customer->getEmail();
                    $quote->getShippingAddress()->setEmail($email);
                }

                if(!$quote->getBpostNotificationSms()){
                    $primaryShipping = $customer->getPrimaryShippingAddress();
                    if(is_object($primaryShipping)){
                        $telephone = $primaryShipping->getTelephone();
                        $quote->getShippingAddress()->setTelephone($telephone);
                    }
                }
            }
        }

        return $this;
    }


    public function controller_action_predispatch_onestepcheckout_index_index(){
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $shippingMethod = str_replace("bpostshm_", "",$quote->getShippingAddress()->getShippingMethod());
        $configHelper = Mage::helper("bpost_shm/system_config");
        if(!$shippingMethod){
            Mage::getSingleton('checkout/session')->getQuote()->setData("bpost_disable_saturday_delivery", true);
        }elseif($configHelper->getBpostShippingConfig("choose_delivery_date", Mage::app()->getStore()->getId())){
            Mage::getSingleton('checkout/session')->getQuote()->setData("bpost_disable_saturday_delivery", true);
        }
        else{
            $dates = Mage::helper('bpost_shm')->getBpostShippingDates();
            Mage::getSingleton('checkout/session')->getQuote()->setData("bpost_disable_saturday_delivery", !(bool)$dates[$shippingMethod]["is_saturday"]);
        }
    }
}
