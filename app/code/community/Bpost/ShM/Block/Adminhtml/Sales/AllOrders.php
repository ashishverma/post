<?php
class Bpost_ShM_Block_Adminhtml_Sales_AllOrders extends Mage_Adminhtml_Block_Widget_Grid_Container{
    /**
     *
     */
    public function __construct()
    {
        $this->_blockGroup = 'bpost_shm';
        $this->_controller = 'adminhtml_sales_allOrders';
        $this->_headerText = Mage::helper('bpost_shm')->__('All bposts orders');
        parent::__construct();
        $this->_removeButton('add');
    }
}