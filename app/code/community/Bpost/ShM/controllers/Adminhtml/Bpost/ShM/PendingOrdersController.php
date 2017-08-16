<?php
class Bpost_ShM_Adminhtml_Bpost_ShM_PendingOrdersController extends Bpost_ShM_Controller_ShM_Order
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        Mage::getSingleton('core/session')->setBpostReturn(0);
        $this->_title($this->__('bpost'))->_title($this->__('Bpost Orders'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/bpost_pending_orders');
        $this->_addContent($this->getLayout()->createBlock('bpost_shm/adminhtml_sales_pendingOrders'));
        $this->renderLayout();
    }

    /**
     * Load the grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('bpost_shm/adminhtml_sales_pendingOrders_grid')->toHtml()
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

        return Mage::getSingleton('admin/session')->isAllowed('sales/bpost_orders/bpost_pending_orders');
    }
}

