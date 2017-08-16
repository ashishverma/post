<?php

/**
 * Class Bpost_ShM_Model_Adminhtml_Observer
 */
class Bpost_ShM_Model_Adminhtml_Observer extends Varien_Event_Observer
{
    /**
     * @param $observer
     * @return bool
     */
    public function core_block_abstract_to_html_before($observer)
    {
        if(Mage::helper('core')->isModuleEnabled('Bpost_ShippingManager') && !Mage::getSingleton('adminhtml/session')->getOldExtensionMessageShown() && Mage::helper('adminhtml')->getCurrentUserId() != "") {
            $popupMessage = Mage::helper('bpost_shm')->__('Please remove or disable the old bpost extension prior to the installation of the new one.');
            Mage::getSingleton('adminhtml/session')->addNotice($popupMessage);
            Mage::getSingleton('adminhtml/session')->setOldExtensionMessageShown(true);
        }

        $block = $observer->getBlock();

        //add download bpost label on shipment view page
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View && $block->getRequest()->getControllerName() == "sales_order_shipment") {
            $shipment = Mage::registry('current_shipment');
            $shipmentId = $shipment->getId();
            $order = Mage::getModel('sales/order')->load($shipment->getOrderId());

            if (strpos($order->getShippingMethod(), 'bpost') !== false && ($shipment->getBpostLabelPath() && $shipment->getBpostLabelExported())) {
                $block->addButton('download', array(
                    'label'     => Mage::helper('bpost_shm')->__('Download bpost label'),
                    'onclick'   => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/bpost_shM_download/label/shipment_id/' . $shipmentId) .'\')',
                    'class'     => 'scalable save',
                ), 1, 0);
            }
        }

        //add bpost return label on order view page
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View && $block->getRequest()->getControllerName() == "sales_order") {
            $order = Mage::registry('current_order');

            if(strpos($order->getShippingMethod(), 'bpostshm') !== false){
                $block->addButton('download', array(
                    'label'     => Mage::helper('bpost_shm')->__('bpost Return Label'),
                    'onclick'   => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/bpost_shM_download/returnLabel/order_id/' . $order->getId()) .'\')',
                    'class'     => 'scalable save',
                ), 1, 0);
            }
        }

        return false;
    }


    /**
     * @param $observer
     * @return bool
     */
    public function bpost_shm_prepare_grid_collection_after($observer){
        $collection = $observer->getCollection();
        $transaction = Mage::getModel('core/resource_transaction');

        //we only need to update the bpost status for orders that does not have the status “pending“, “completed”, “closed” or “cancelled”
        $stateArray = array(
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_COMPLETE,
            Mage_Sales_Model_Order::STATE_CANCELED,
        );

        $collection->addAttributeToSelect('bpost_reference');
        $bpostHelper = Mage::helper("bpost_shm");

        //loop collection and get statuses
        foreach($collection as $order){
            if(in_array($order->getState(), $stateArray)){
                continue;
            }

            $dataChanged = false;
            $orderStatus = false;

            if($order->getBpostReference()){
                try{
                    $shipmentStatuses = $bpostHelper->getBpostStatus($order);

                    if(!$shipmentStatuses){
                        continue;
                    }

                    $trackCollection = Mage::getResourceModel('sales/order_shipment_track_collection')
                        ->addFieldToFilter('order_id', $order->getId())
                        ->addFieldToFilter('carrier_code', 'bpostshm');


                    //extra count check
                    //normally not necessary. If the bpost reference exists a shipment track must exists too.
                    if($trackCollection->count() == 0){
                        continue;
                    }

                    foreach($trackCollection as $track){
                        $trackNumber = $track->getTrackNumber();

                        if($trackNumber && isset($shipmentStatuses[$trackNumber])){
                            $status = $shipmentStatuses[$trackNumber];
                            $shipment = $track->getShipment();
                            $shipment->setBpostStatus($status);
                            $transaction->addObject($shipment);

                            if($orderStatus && $status != $orderStatus){
                                $orderStatus = "multiple_statuses";
                            }elseif(!$orderStatus){
                                $orderStatus = $status;
                            }

                            $dataChanged = true;
                        }
                    }

                    $order->setBpostStatus($orderStatus);

                    //we use a transaction for saving our data
                    //need to save shipment and order
                    if($dataChanged){
                        $transaction->addObject($order);
                    }
                }catch(Exception $e){
                    Mage::helper("bpost_shm")->ApiLog($e->getMessage(), Zend_Log::ERR);
                }
            }
        }

        //save transaction
        $transaction->save();
    }


    /**
     * @return $this
     * function triggered when saving shipping settings bpost module
     * checks if authentication is valid
     */
    public function admin_system_config_changed_section_shipping()
    {
        /** @var Bpost_ShM_Model_Api $api */
        $session = Mage::getSingleton("adminhtml/session");
        $configHelper = Mage::helper("bpost_shm/system_config");

        try{
            $api = Mage::getModel( 'bpost_shm/api', true );
            $apiResponse = $api->getProductConfig();

            if(!$apiResponse) {
                Mage::throwException("Failed to authenticate with bpost, please check your credentials.");
            }

            //check if click & collect is allowed and trigger
            $this->_checkAndTriggerClickCollect($apiResponse->getBody());

            $session->addSuccess($configHelper->__("Successfully authenticated with bpost."));
        }catch(Exception $e){
            $session->addError($configHelper->__($e->getMessage()));
        }

        return $this;
    }


    protected function _checkAndTriggerClickCollect($apiResponseBody){
        $xml = simplexml_load_string($apiResponseBody);
        $configHelper = Mage::helper('bpost_shm/system_config');
        foreach ($xml->deliveryMethod as $deliveryMethodData) {
            if($deliveryMethodData['name'] == "Click & Collect" && $deliveryMethodData['visiblity'] == "VISIBLE"){
                Mage::getConfig()->saveConfig('carriers/bpost_clickcollect/activated', '1');
                Mage::getConfig()->saveConfig('carriers/bpost_clickcollect/active', '1');
                if($configHelper->getBpostCarriersConfig('marker', 'bpost_clickcollect') == ""){

                    $src = Mage::getBaseDir('skin') .
                        DS . 'frontend' .
                        DS . 'base' .
                        DS . 'default' .
                        DS . 'images' . DS . 'bpost' . DS . 'location_clickcollect_default.png';

                    $dest = Mage::getBaseDir('media') .
                        DS . 'bpost' . DS . 'default' . DS . 'location_clickcollect_default.png';

                    $io = new Varien_Io_File();
                    $io->cp($src, $dest);
                    Mage::getConfig()
                        ->saveConfig('carriers/bpost_clickcollect/marker', 'default/location_clickcollect_default.png');
                }
                Mage::app()->getCacheInstance()->cleanType('config');
                return $this;
            }
            elseif($configHelper->getBpostCarriersConfig('activated', 'bpost_clickcollect') == 1){
                Mage::getConfig()->saveConfig('carriers/bpost_clickcollect/activated', '0');
                Mage::getConfig()->saveConfig('carriers/bpost_clickcollect/active', '0');
                Mage::app()->getCacheInstance()->cleanType('config');
            }
        }
    }

    /**
     * Calculate and set the weight on the shipping to pass it to the webservice after a standard shipment save.
     *
     * @param $observer
     */
    public function sales_order_shipment_save_before($observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment->hasId() && !$shipment->getTotalWeight()) {
            $weight = Mage::helper('bpost_shm')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
        }
    }

}