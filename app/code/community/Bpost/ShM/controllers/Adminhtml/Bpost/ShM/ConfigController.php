<?php
class Bpost_ShM_Adminhtml_Bpost_ShM_ConfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * renders the bpost information popup
     */
    public function informationPopupAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * renders the bpost grid information popup when labels are generated
     */
    public function gridInformationPopupAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * action is used in for showing an example screenshot of how the module will look like.
     */
    public function screenshotPopupAction(){
        $this->loadLayout();

        //because Mage::getEdition will not work in all Magento versions,
        //we simple check on the filepath enterprise
        //if exists, enterprise is running

        $appPath = Mage::getBaseDir('app');
        $enterprisePath = $appPath.DS."code".DS."core".DS."Enterprise";

        $ioFile = new Varien_Io_File();
        try{
            $ioFile->cd($enterprisePath);

            //get enterprise screenshot
            $imagePath = Mage::getDesign()->getSkinUrl("images/bpost/ee-screenshot.png");
        }catch(Exception $e){
            //get community screenshot
            $imagePath = Mage::getDesign()->getSkinUrl("images/bpost/ce-screenshot.png");
        }

        $data = array("image_path" => $imagePath, "width" => "600px");

        $this->getLayout()->getBlock("screenshot.popup")->setData($data);
        $this->renderLayout();
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function exportTableratesAction(){
        $request = $this->getRequest();
        $fileName = $request->getParam('filename');

        /** @var $gridBlock Mage_Adminhtml_Block_Shipping_Carrier_Tablerate_Grid */
        $gridBlock = $this->getLayout()->createBlock('bpost_shm/adminhtml_shipping_carrier_bpost_tablerate_' . $request->getParam('method') . '_grid');
        $website = Mage::app()->getWebsite($this->getRequest()->getParam('website'));
        if ($this->getRequest()->getParam('conditionName')) {
            $conditionName = $this->getRequest()->getParam('conditionName');
        } else {
            $conditionName = $website->getConfig('carriers/bpost_parcellocker/table_rate_condition');
        }
        $gridBlock->setWebsiteId($website->getId())->setConditionName($conditionName);
        $content = $gridBlock->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }


    /**
     * checks if authentication is valid and updates the country codes for international shipping
     */
    public function importCountriesByApiAction(){

        $session = Mage::getSingleton("adminhtml/session");
        $configHelper = Mage::helper("bpost_shm/system_config");

        try{
            /** @var Bpost_ShM_Model_Api $api */
            $api = Mage::getModel( 'bpost_shm/api', true );
            $apiResponse = $api->getProductConfig();

            if(!$apiResponse) {
                Mage::throwException("Failed to authenticate with bpost, please check your credentials.");
            }

            $xml = simplexml_load_string($apiResponse->getBody());
            $product = $configHelper->getBpostCarriersConfig("product", "bpost_international", 0);

            if(!$product){
                $product = "bpack World Business";
            }else{
                $product = "bpack World Express Pro";
            }

            $countryCodes = array();
            foreach ($xml->deliveryMethod as $deliveryMethodData) {
                foreach($deliveryMethodData->product as $productData){
                    $attributes = $productData->attributes();
                    $productName = $attributes["name"];

                    if($productName == $product) {
                        $productData = $deliveryMethodData->product;
                        foreach ($productData->price as $priceData) {
                            $countryCode = (string)$priceData["countryIso2Code"];
                            $countryCodes[] = $countryCode;
                        }

                        break 2;
                    }
                }
            }


            if(!empty($countryCodes)){
                $countryModel = Mage::getModel("bpost_shm/country");
                $countryResourceModel = Mage::getResourceModel('bpost_shm/country');
                $countryCollection = $countryModel->getCollection();

                //we truncate our table first by calling our own truncate function
                $countryResourceModel->truncateTable();

                //then we populate bpost_country with our last data
                foreach($countryCodes as $countryCode){
                    $itemModel = Mage::getModel("bpost_shm/country");
                    $itemModel->setData(array("country_code" => $countryCode));
                    $countryCollection->addItem($itemModel);
                }

                $countryCollection->save();

            }else{
                $session->addNotice($configHelper->__("No bpost product configuration found for %s. Please check your bpost shipping manager for international delivery.", $product));
            }

            $session->addSuccess($configHelper->__("Successfully authenticated with bpost."));
        }catch(Exception $e){
            $session->addError($configHelper->__($e->getMessage()));
        }

        $this->_redirect("adminhtml/system_config/edit", array("section" => "carriers"));
    }


    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/bpost_shm');
    }
}