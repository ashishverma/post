<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
class Bpost_ShM_Controller_ShM_Order extends Mage_Adminhtml_Controller_Action
{
    //we use this class for functions that will be used more than one time
    //all orders and pending orders are extending this one

    /**
     * Generates a label and completes the shipment.
     * This is called by the action in the order grid dropdown.
     * By ajax call
     */
    public function ajaxGenerateAndCompleteAction(){
        $ajaxResult = array('messages' => '');

        ini_set('max_execution_time', 120);
        $orderIds = $this->getRequest()->getParam('entity_id');
        $counter = 0;
        $gridModel = Mage::getModel('bpost_shm/adminhtml_bpostgrid');

        $messages = array("success" => array(), "error" => array(), "notice" => array());

        if (!is_array($orderIds)) {
            try {

                $order = Mage::getModel("sales/order")->load($orderIds);
                $gridModel->generateAndCompleteOrder($order);

                if(!is_object(Mage::getSingleton('core/session')->getMessages()->getLastAddedMessage())){
                    $message = Mage::helper('bpost_shm')->__("Your label has been generated and statuses have been changed.");
                    $messages["success"][] = $message;
                    $ajaxResult['status'] = 'success';
                }
            } catch (Exception $e) {
                Mage::helper('bpost_shm')->log($e->getMessage(), Zend_Log::ERR);
                $message = Mage::helper('bpost_shm')->__("Your selected order is not ready to be shipped or has already been shipped, operation canceled.");
                $messages["error"][] = $message;
            }
        }else {

            try {
                $orderCollection = Mage::getModel("sales/order")->getCollection()->addFieldToFilter("entity_id", array("in" => $orderIds));

                foreach ($orderCollection as $order) {
                    try {
                        $counter += $gridModel->generateAndCompleteOrder($order);
                    } catch (Exception $e) {
                        Mage::helper('bpost_shm')->log($e->getMessage(), Zend_Log::ERR);
                        $messages["error"][] = $e->getMessage();
                    }
                }

                if($counter > 0){
                    $message = Mage::helper('bpost_shm')->__("%s label(s) have been generated and statuses have been changed.", $counter);
                    $messages["success"][] = $message;
                }
            } catch (Exception $e) {
                Mage::helper('bpost_shm')->log($e->getMessage(), Zend_Log::ERR);
                $message = Mage::helper('bpost_shm')->__("Some of the selected orders are not ready to be shipped or have already been shipped, operation canceled.");
                $messages["error"][] = $message;
            }
        }

        try {
            //we save our grid transaction
            $gridModel->saveTransaction();
        }catch (Exception $e){
            Mage::helper('bpost_shm')->log($e->getMessage(), Zend_Log::ERR);
            $message = Mage::helper('bpost_shm')->__($e->getMessage());
            $messages["error"][] = $message;
        }


        //first check if we have already messages in this request
        //if so, add these to the messages array
        foreach(Mage::getSingleton('core/session')->getMessages()->getItems() as $sessionMessage){
            switch($sessionMessage->getType()){
                case "notice":
                    $messages["notice"][] = $sessionMessage->getCode();
                break;

                case "error":
                    $messages["error"][] = $sessionMessage->getCode();
                break;

                case "success":
                    $messages["success"][] = $sessionMessage->getCode();
                break;
            }
        }

        $ajaxResult["messages"] = $messages;

        //remove messages from session
        Mage::getSingleton('core/session')->getMessages(true);

        $this->getResponse()->setBody ( Zend_Json::encode($ajaxResult) );
    }


    /**
     * Zips all undownloaded labels and gives downloadlink.
     */
    public function dowloadAllUndownloadedAction()
    {
        $orderIds = $this->getRequest()->getParam('entity_id');
        try {

            $fileName = Mage::getModel('bpost_shm/adminhtml_bpostgrid')->processUndownloadedLabels($orderIds);

            if (!$fileName) {
                $message = Mage::helper('bpost_shm')->__('No undownloaded labels found.');
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*/index');
            } else {

                //check for .zip files
                //if zip, do not render
                if(substr($fileName, -4) == ".zip"){
                    $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . Bpost_ShM_Model_Adminhtml_Bpostgrid::MEDIA_LABEL_PATH."zips".DS.$fileName;
                }else{
                    $url = $this->getUrl("*/*/download", array("file_name" => $fileName));
                }

                $message = Mage::helper('bpost_shm')->__('Successfully exported order(s). Download the file here: %s',
                    ' <a id="downloadzip" href="'
                    . $url . '" target="_blank">'
                    . $fileName . '</a>');

                Mage::getSingleton('core/session')->addSuccess($message);
                $this->_redirect('*/*/index');
            }

        }catch (Exception $e){
            Mage::helper('bpost_shm')->log($e->getMessage(), Zend_Log::ERR);
            $message = Mage::helper('bpost_shm')->__("The file(s) could not be downloaded, please check your bpost logs.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/index');
        }
    }


    /**
     * Download responseAction for the zip
     */
    public function downloadAction()
    {
        $fileName = $this->getRequest()->getParam('file_name');

        if(substr($fileName, -4) == ".zip"){
            $fileName = "zips".DS.$fileName;
        }

        $fileLocation = Mage::getBaseDir('media') . Bpost_ShM_Model_Adminhtml_Bpostgrid::MEDIA_LABEL_PATH. $fileName;
        $ioFile = new Varien_Io_File();

        if ($ioFile->fileExists($fileLocation)) {
            $file = Zend_Pdf::load($fileLocation);
            $this->_prepareDownloadResponse($fileName, $file->render());
        } else {
            $message = Mage::helper('bpost_shm')->__("The requested file does not exist, it is either removed or not readable.");
            $this->_getSession()->addError($message);
            $this->_redirect('*/*/index');
        }
    }
}

