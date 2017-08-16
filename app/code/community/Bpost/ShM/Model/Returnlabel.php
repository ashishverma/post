<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Returnlabel
 */
class Bpost_ShM_Model_Returnlabel extends Mage_Core_Model_Abstract
{
    const MEDIA_RETURNLABEL_PATH = "/bpost/";

    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("bpost_shm/returnlabel");
    }


    /**
     * Gets label from webservice, saves it and returns the saved id.
     *
     * @param $orderId
     * @return int
     */
    public function generateLabelAndSave($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $bpostHelper = Mage::helper("bpost_shm");
        $returnLabelHelper = Mage::helper("bpost_shm/returnlabel");

        //check for return order
        $api = Mage::getModel('bpost_shm/api');
        $api->initialize($order->getStoreId());
        $returnlabelResponse = $api->createReturnLabel($order);

        if(!$returnlabelResponse){
            Mage::throwException("Failed to create a return label. Please check your error logs.");
        }

        $parsedLabelResponse = $bpostHelper->parseLabelApiResponse($returnlabelResponse, $order);

        //make sure we save with an unique name
        //if no barcode is returned (probably never)
        $barcode = $returnLabelHelper->getBarcodeByLabelResponse($order, $parsedLabelResponse);

        foreach($parsedLabelResponse["pdfString"] as $pdfString){
            //convertstring to pdf and save
            $pdfname = $bpostHelper->generatePdfAndSave($pdfString, "returnlabel", $barcode);
        }

        $returnLabelObject = new Bpost_ShM_Model_Returnlabel;
        $returnLabelObject
            ->setLabelBarcode($barcode)
            ->setLabelPdfPath("returnlabel/$pdfname.pdf")
            ->setOrderId($orderId)
            ->setDateCreated(time());

        $order->setBpostReturnLabelExists(true);

        //we start a transaction
        //we save multiple objects
        Mage::getModel('core/resource_transaction')
        ->addObject($returnLabelObject)
        ->addObject($order)
        ->save();

        return $returnLabelObject->getId();
    }

    /**
     * Sends email with custom bpost email and attached the pdf
     *
     * @param $order
     * @param $returnId
     * @return $this
     */
    public function sendEmail($returnId)
    {
        $file = new Varien_Io_File();
        $bpostHelper = Mage::helper("bpost_shm");
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $returnLabel = Mage::getModel('bpost_shm/returnlabel')->load($returnId);
        $order = Mage::getModel('sales/order')->load($returnLabel->getOrderId());
        $billingAddress = $order->getBillingAddress();
        $pdfAttachment = $returnLabel->getLabelPdfPath();

        $templateVars = array('returnlabel' => $returnLabel, 'order' => $order, 'store' => Mage::app()->getStore($order->getStoreId()));
        $transactionalEmail = Mage::getModel('core/email_template')->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()));

        $filePath = Mage::getBaseDir('media') . self::MEDIA_RETURNLABEL_PATH . $pdfAttachment;
        $pdfFileParser = Zend_Pdf::load($filePath);

        if (!empty($pdfAttachment) && $file->fileExists($filePath)) {
            $fileName = $bpostHelper->getFileNameByPath($pdfAttachment);
            $transactionalEmail->getMail()
                ->createAttachment(
                    $pdfFileParser->render(),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $fileName
                );
        }

        $transactionalEmail->sendTransactional('bpost_returnlabel_email_template',
            array('name' => Mage::getStoreConfig('trans_email/ident_support/name'),
                'email' => Mage::getStoreConfig('trans_email/ident_support/email')),
            $billingAddress->getEmail(),
            $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
            $templateVars);

        $translate->setTranslateInline(true);
        return $billingAddress->getEmail();
    }
}
