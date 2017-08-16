<?php

/**
 * Class Bpost_ShM_Adminhtml_Bpost_ShM_DownloadController
 */
class Bpost_ShM_Adminhtml_Bpost_ShM_DownloadController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Fetches the label and puts it in a download response.
     */
    public function labelAction()
    {
        $request = $this->getRequest();
        $orderId = $request->getParam('order_id');
        $ioFile = new Varien_Io_File();
        $bpostMediaFilePath = Mage::getBaseDir('media') . Bpost_ShM_Model_Adminhtml_Bpostgrid::MEDIA_LABEL_PATH;
        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection');

        if($orderId){
            $shipmentCollection->addFieldToFilter('order_id', $orderId);
            $shipmentCollection->addFieldToFilter('bpost_label_path', array("neq" => ""));
            $shipment = $shipmentCollection->getFirstItem();

            //get shipments by order
            $order = Mage::getModel("sales/order")->load($orderId);
            $pdfDownloadName = $order->getIncrementId().".pdf";
        }else{
            $shipmentId = $request->getParam('shipment_id');
            $shipmentCollection->addFieldToFilter('entity_id', $shipmentId);
            $shipment = $shipmentCollection->getFirstItem();

            if (!$shipment->hasData() || $shipment->getBpostLabelPath() == "") {
                $message = Mage::helper('bpost_shm')->__("No label generated yet - please perform the ‘Generate Label and Complete’ action from the overview.");
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/sales_order_shipment/view/' , array('shipment_id' => $shipmentId));
                return;
            }

            $order = $shipment->getOrder();
            $pdfDownloadName = $shipment->getIncrementId().".pdf";
        }

        $pdfMerged = new Zend_Pdf();
        $merged = false;

        if ($shipment->getBpostLabelPath() != "") {
            $pdfNames = explode(":", $shipment->getBpostLabelPath());

            foreach($pdfNames as $pdfName){
                $pdfPath = $bpostMediaFilePath . $pdfName;

                if($ioFile->fileExists($pdfPath)){
                    $tmpPdf = Zend_Pdf::load($pdfPath);

                    foreach ($tmpPdf->pages as $page) {
                        $clonedPage = clone $page;
                        $pdfMerged->pages[] = $clonedPage;
                    }

                    $merged = true;
                }
            }
        }

        if($merged){
            $shipmentCollection->setDataToAll("bpost_label_exported", true);
            $shipmentCollection->save();
        }

        if(!count($pdfMerged->pages)) {
            $this->_getSession()->addError("Pdf(s) could not be found.");
            $this->_redirectUrl($this->_getRefererUrl());
            return;
        }else{
            $order->setBpostLabelExported(1)->save();
        }

        $this->_prepareDownloadResponse($pdfDownloadName, $pdfMerged->render(), 'application/pdf');
    }


    /**
     * return label action creates a return label
     */
    public function returnLabelAction(){

        try{
            $orderId = $this->getRequest()->getParam('order_id');
            $bpostHelper = Mage::helper('bpost_shm');

            if(!$orderId){
                Mage::throwException('No order id found to process.');
            }

            $returnLabelModel = Mage::getModel('bpost_shm/returnlabel');
            $returnLabelModel->generateLabelAndSave($orderId);

            $this->_getSession()->addSuccess($bpostHelper->__("Your return has been generated and is available under -bpost Return labels- in this order."));
        }catch(Exception $e){
            $this->_getSession()->addError($bpostHelper->__($e->getMessage()));
        }

        $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
    }


    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/bpost_orders/bpost_download_labels');
    }
}
