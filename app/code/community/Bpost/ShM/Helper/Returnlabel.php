<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Helper_Returnlabel
 */
class Bpost_ShM_Helper_Returnlabel extends Mage_Core_Helper_Abstract
{
    /**
     * @param $orderId
     * function returns the nr of returnlabels an order has.
     * unfortunately, we must do this with collection count
     */
    public function getOrderReturnlabelsCount($orderId){
        $labelCollection = Mage::getModel("bpost_shm/returnlabel");
        $collection = $labelCollection->getCollection()->addFieldToFilter("order_id", $orderId);

        return $collection->count();
    }


    /**
     * @param $order
     * @param $parsedLabelResponse
     * @return string
     * function returns the barcode by parsed label response
     * make sures we return an unique name
     * if no barcode is returned (probably never)
     */
    public function getBarcodeByLabelResponse($order, $parsedLabelResponse){
        if(!isset($parsedLabelResponse["returnBarcodeString"]) || $parsedLabelResponse["returnBarcodeString"][0] == ""){
            $barcode = "no-barcode-".$order->getIncrementId();
        }else{
            $barcode = $parsedLabelResponse["returnBarcodeString"][0];
        }

        return $barcode;
    }
}
