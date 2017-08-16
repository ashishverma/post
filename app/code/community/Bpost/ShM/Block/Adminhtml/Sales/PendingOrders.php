<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_Sales_PendingOrders
 */
class Bpost_ShM_Block_Adminhtml_Sales_PendingOrders extends Mage_Adminhtml_Block_Widget_Grid_Container{
    /**
     *
     */
    public function __construct()
    {
        $this->_blockGroup = 'bpost_shm';
        $this->_controller = 'adminhtml_sales_pendingOrders';
        $this->_headerText = Mage::helper('bpost_shm')->__('Pending bpost orders');
        parent::__construct();
        $this->_removeButton('add');
    }
}