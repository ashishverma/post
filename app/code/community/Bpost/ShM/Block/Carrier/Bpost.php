<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Block_Carrier_Bpost
 */
class Bpost_ShM_Block_Carrier_Bpost extends Mage_Core_Block_Template
{
    /**
     * Returns a settings array used during checkout
     *
     * @return array
     */
    public function getSettings()
    {
        $bpostHelper = Mage::helper('bpost_shm');
        $configHelper = Mage::helper("bpost_shm/system_config");
        $settings = array(
            "monday" => $bpostHelper->__('Monday'),
            "tuesday" => $bpostHelper->__('Tuesday'),
            "wednesday" => $bpostHelper->__('Wednesday'),
            "thursday" => $bpostHelper->__('Thursday'),
            "friday" => $bpostHelper->__('Friday'),
            "saturday" => $bpostHelper->__('Saturday'),
            "sunday" => $bpostHelper->__('Sunday'),
            "closed" => $bpostHelper->__('Closed'),
            "close_label" => $bpostHelper->__('Close'),
            "select_text" => $bpostHelper->__('Click here to choose a bpost pick-up point.'),
            "select_text_parcel_locker" => $bpostHelper->__('Click here to choose a bpost parcel locker point.'),
            "select_text_clickcollect" => $bpostHelper->__('Click here to choose a bpost Click & Collect point.'),
            "change_text" => $bpostHelper->__('Click here to change the bpost pick-up point.'),
            "change_text_parcel_locker" => $bpostHelper->__('Click here to change the bpost parcel locker point.'),
            "change_text_clickcollect" => $bpostHelper->__('Click here to change the bpost Click & Collect point.'),
            "label_filter" => $bpostHelper->__("Filter"),
            "label_select" => $bpostHelper->__("Select"),
            "label_postcode" => $bpostHelper->__("Type in a location"),
            "label_loading" => $bpostHelper->__("Please wait. Loading bpost map based on your address"),
            "notifications_text_pickup_point" => $bpostHelper->__("How do you want to be notified when your parcel is available in the pick-up point?"),
            "notifications_text_parcel_locker" => $bpostHelper->__("How do you want to be notified when your parcel is available in the parcel locker?"),
            "notifications_sms_pickup_point" => $bpostHelper->__("via SMS"),
            "notifications_sms_parcel_locker" => $bpostHelper->__("send additional SMS notification"),
            "notifications_email" => $bpostHelper->__("via E-mail"),
            "no_telephone_number" => $bpostHelper->__("no telephone number found, please fill in a number in the shipping address"),
            "no_points_found" => $bpostHelper->__("No points could be found. Please use the filter above or drag the map to get a better result."),
            "imgpath" => DS . "skin" . DS . "frontend" . DS . "base" . DS . "default" . DS . "images" . DS . "bpost" . DS,
            "location_postoffice_default_image" => $this->getSkinUrl('images/bpost/location_postoffice_default.png'),
            "location_postpoint_default_image" => $this->getSkinUrl('images/bpost/location_postpoint_default.png'),
            "location_parcellocker_default_image" => $this->getSkinUrl('images/bpost/location_parcellocker_default.png'),
            "location_clickcollect_default_image" => $this->getSkinUrl('images/bpost/location_clickcollect_default.png'),
            "location_clickcollect_custom_image" => ($configHelper->getBpostCarriersConfig('marker', 'bpost_clickcollect', Mage::app()->getStore()->getId()) ? Mage::getBaseUrl('media') . "bpost/" . $configHelper->getBpostCarriersConfig("marker", "bpost_clickcollect", Mage::app()->getStore()->getId()) : ""),
            "base_url" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, Mage::app()->getStore()->isCurrentlySecure()),
            "datepicker_display" => (bool)$configHelper->getBpostShippingConfig("display_delivery_date", Mage::app()->getStore()->getId()),
            "datepicker_choose" => (bool)$configHelper->getBpostShippingConfig("choose_delivery_date", Mage::app()->getStore()->getId()),
            "datepicker_text" => $bpostHelper->__("Select your preferred delivery date"),
            "datepicker_delivery_date_text" => $bpostHelper->__("Delivery Date"),
            "datepicker_saturday_delivery_text" => $bpostHelper->__("I don't want my parcel to be delivered on a Saturday"),
            "datepicker_saturday_delivery_cost" => $bpostHelper->__("(extra cost Saturday delivery: %s EUR)"),
            "datepicker_saturday_homedelivery" => (bool)$configHelper->getBpostCarriersConfig("saturday_delivery", "bpost_homedelivery", Mage::app()->getStore()->getId()),
            "datepicker_saturday_pickuppoint" => (bool)$configHelper->getBpostCarriersConfig("saturday_delivery", "bpost_pickuppoint", Mage::app()->getStore()->getId()),
            "datepicker_saturday_parcellocker" => (bool)$configHelper->getBpostCarriersConfig("saturday_delivery", "bpost_parcellocker", Mage::app()->getStore()->getId()),
            "datepicker_saturday_clickcollect" => (bool)$configHelper->getBpostCarriersConfig("saturday_delivery", "bpost_clickcollect", Mage::app()->getStore()->getId()),
            "datepicker_days" => $this->getShippingDates(),
            "onestepcheckout_active" => $bpostHelper->isOnestepCheckout(),
            "onestepcheckout_shipping_address_error" => $bpostHelper->__("Please select a postcode or city first.")
        );

        return $settings;
    }

    /**
     * Returns the quote's shipping address telephone number
     *
     * @return string
     */
    public function getBpostNotificationSms()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getTelephone();
    }

    /**
     * Returns the quote's shipping address e-mail address
     *
     * @return string
     */
    public function getBpostNotificationEmail()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getEmail();
    }

    /**
     * Returns the current shipping method
     *
     * @return string
     */
    public function getCurrentShippingMethod()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();
    }

    /**
     * Returns the delivery date array
     *
     * @return array
     */
    public function getShippingDates()
    {
        return Mage::helper('bpost_shm')->getBpostShippingDates();
    }

    /*
     * This method retrieves the fixed saturday delivery cost
     *
     * @return string|bool
     */
    public function getSaturdayDeliveryCost()
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $saturdayDeliveryCost = $configHelper->getBpostShippingConfig("saturday_delivery_cost", Mage::app()->getStore()->getId());

        if(!empty($saturdayDeliveryCost) && $saturdayDeliveryCost > 0) {
            return $saturdayDeliveryCost;
        }

        return false;
    }
}
