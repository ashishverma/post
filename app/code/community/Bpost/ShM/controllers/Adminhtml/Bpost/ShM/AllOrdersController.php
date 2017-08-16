<?php
class Bpost_ShM_Adminhtml_Bpost_ShM_AllOrdersController extends Bpost_ShM_Controller_ShM_Order
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        Mage::getSingleton('core/session')->setBpostReturn(0);
        $this->_title($this->__('bpost'))->_title($this->__('Bpost Orders'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/bpost_all_orders');
        $this->_addContent($this->getLayout()->createBlock('bpost_shm/adminhtml_sales_allOrders'));
        $this->renderLayout();
    }

    /**
     * Load the grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('bpost_shm/adminhtml_sales_allOrders_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        $configHelper = Mage::helper("bpost_shm/system_config");
        $manageLabels = $configHelper->getBpostShippingConfig("manage_labels_with_magento");

        if(!$manageLabels){
            $this->_getSession()->addNotice('Please enable the "Use Magento to manage labels" setting first under "system -> configuration -> shipping settings -> Bpost Shipping Manager" if you want to use this functionality.');
            return false;
        }

        return Mage::getSingleton('admin/session')->isAllowed('sales/bpost_orders/bpost_all_orders');
    }


    /**
     * Call this to send an email with the bpost template.
     * This calls the model to handle emails the magento way.
     * Logs all errors in try catch.
     */
    public function sendEmailAction()
    {
        $returnId = $this->getRequest()->getParam('return_id');
        try {
            $email = Mage::getModel('bpost_shm/returnlabel')->sendEmail($returnId);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirectReferer();
            return $this;
        }
        $message = Mage::helper('bpost_shm')->__("The email with return label has been sent to %s.", $email);
        Mage::getSingleton('core/session')->addSuccess($message);
        $this->_redirectReferer();
        return $this;
    }

}

