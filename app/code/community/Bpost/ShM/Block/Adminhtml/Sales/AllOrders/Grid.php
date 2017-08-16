<?php

class Bpost_ShM_Block_Adminhtml_Sales_AllOrders_Grid extends Bpost_ShM_Block_Adminhtml_Sales_Grid
{
    /**
     * Constructs the grid and sets basic parameters.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('bpost_shm_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * prepare collection to use for the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var Mage_Sales_Model_Resource_Order_Grid_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_grid_collection');
        $collection->getSelect()->join(
            Mage::getConfig()->getTablePrefix() . 'sales_flat_order as sfo',
            'sfo.entity_id=`main_table`.entity_id',
            array(
                'shipping_method' => 'shipping_method',
                'total_qty_ordered' => 'ROUND(sfo.total_qty_ordered,0)',
                'bpost_label_exported' => 'bpost_label_exported',
                'bpost_label_exists' => 'bpost_label_exists',
                'bpost_drop_date' => 'bpost_drop_date',
                'bpost_status' => 'bpost_status',
                'state' => 'state'
            )
        );
        $collection->addExpressionFieldToSelect(
            'shipping_customer_name',
            "IF(sfo.shipping_method='bpostshm_bpost_international', CONCAT('International: ', main_table.shipping_name), main_table.shipping_name)" ,
            'shipping_customer_name'
        );
        $collection->addAttributeToFilter('sfo.shipping_method', array('like' => '%bpostshm%'));
        $this->setCollection($collection);

        parent::_prepareCollection();

        Mage::dispatchEvent('bpost_shm_prepare_grid_collection_after', array('collection' => $collection));
        return $this;
    }



    /**
     * Generate rowurl.
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId(), 'bpostReturn' => '1'));
        }
        return false;
    }
}