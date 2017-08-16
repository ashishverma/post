<?php

/**
 * Class Bpost_ShM_Block_Adminhtml_Sales_Grid
 */
class Bpost_ShM_Block_Adminhtml_Sales_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setUseAjax(true);
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid|void
     */
    protected function _prepareCollection(){
        //bugfix collection getSize function
        //trigger getSize before group statement will set the correct collection size
        $this->getCollection()->getSize();

        if ($this->getCollection()) {
            $this->getCollection()->getSelect()->joinLeft(Mage::getConfig()->getTablePrefix() . 'sales_flat_shipment as sfs', 'sfs.order_id=`main_table`.entity_id', array(
                'bpost_label_path' => 'bpost_label_path',
            ))->group('main_table.entity_id');
        }

        parent::_prepareCollection();
    }

    /**
     * prepare columns used in the grid.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $configHelper = Mage::helper('bpost_shm/system_config');

        $helper = Mage::helper('bpost_shm');
        $this->addColumn('real_order_id', array(
            'header' => $helper->__('Order #'),
            'width' => '80px',
            'type' => 'text',
            'index' => 'increment_id',
            'filter_index' => 'main_table.increment_id'
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => $helper->__('Purchased From (Store)'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
                'filter_index' => 'sfo.store_id'
            ));
        }
        $this->addColumn('created_at', array(
            'header' => $helper->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
            'filter_index' => 'main_table.created_at'
        ));
        $this->addColumn('billing_name', array(
            'header' => $helper->__('Bill to Name'),
            'index' => 'billing_name',
            'filter_index' => 'main_table.billing_name'
        ));
        $this->addColumn('shipping_name', array(
            'header' => $helper->__('Ship to Name'),
            'index' => 'shipping_customer_name',
            'filter_condition_callback' => array($this, '_shippingNameFilter'),
        ));
        $this->addColumn('grand_total', array(
            'header' => $helper->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter_index' => 'main_table.grand_total'
        ));
        $this->addColumn('total_qty_ordered', array(
            'header' => $helper->__('# of Items'),
            'type' => 'int',
            'index' => 'total_qty_ordered',
            'width' => '100px',
        ));
        $this->addColumn('status', array(
            'header' => $helper->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
            'filter_index' => 'main_table.status'
        ));
        $this->addColumn('bpost_status', array(
            'header' => $helper->__('bpost status'),
            'index' => 'bpost_status',
            'type' => 'text',
            'width' => '100px'
        ));
        $this->addColumn('bpost_label_exists', array(
            'header' => $helper->__('Label download'),
            'index' => 'bpost_label_exists',
            'width' => '100px',
            'renderer' => 'bpost_shm/adminhtml_sales_grid_renderer_label_download',
            'type'    => 'options',
            'options' => array('1' => 'Yes', '0' => 'No'),
            'filter_index' => 'sfo.bpost_label_exists'
        ));
        if ($configHelper->getBpostShippingConfig('display_delivery_date', Mage::app()->getStore()->getId())) {
            $this->addColumn('bpost_drop_date', array(
                'header' => $helper->__('Drop date'),
                'index' => 'bpost_drop_date',
                'type' => 'date',
                'width' => '100px',
                'renderer' => 'bpost_shm/adminhtml_sales_grid_renderer_dropdate_dateformat'
            ));
        }
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header' => $helper->__('Action'),
                    'width' => '50px',
                    'type' => 'action',
                    'getter' => 'getId',
                    'actions' => array(
                        array(
                            'caption' => $helper->__('View'),
                            'url' => array('base' => '*/sales_order/view', 'params' => array('bpostReturn' => '2')),
                            'field' => 'order_id',
                        )
                    ),
                    'filter' => false,
                    'sortable' => false,
                    'index' => 'stores',
                    'is_system' => true,
                ));
        }
        return parent::_prepareColumns();
    }

    /**
     * Gets grid url for callbacks.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Prepares Massactions for the grid.
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('`main_table`.entity_id');
        $massaActionBlock = $this->getMassactionBlock();
        $massaActionBlock->setFormFieldName('entity_id');

        $massaActionBlock->addItem('generateAndComplete', array(
            'label' => Mage::helper('bpost_shm')->__('Generate Label and Complete'),
            'url' => $this->getUrl('*/*/ajaxGenerateAndComplete'),
        ));

        $massaActionBlock->addItem('dowloadAllUndownloaded', array(
            'label' => Mage::helper('bpost_shm')->__('Download all undownloaded'),
            'url' => $this->getUrl('*/*/dowloadAllUndownloaded'),
        ));
        return $this;
    }

    /**
     * Prepare grid massaction block
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassactionBlock()
    {
        $massActionJsName = $this->getHtmlId()."_massactionJsObject";
        $gridJsObjectName = $this->getHtmlId()."JsObject";
        $massActionBlock = $this->getLayout()->createBlock($this->getMassactionBlockName());

        $buttonHtml = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('bpost_shm')->__('Submit'),
                'onclick'   => "generateAndComplete('".$massActionJsName."','".$gridJsObjectName."')",
            ))->toHtml();

        $this->setChild('massaction', $massActionBlock
            ->setSubmitButtonHtml($buttonHtml)
            ->setUseAjax(true)
            ->setTemplate("bpost/widget/grid/massaction.phtml")
        );

        $this->_prepareMassaction();

        if($this->getMassactionBlock()->isAvailable()) {
            $this->_prepareMassactionColumn();
        }

        return $this;
    }


    /**
     * @param $collection
     * @param $column
     * @return $this
     */
    protected function _shippingNameFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $collection->getSelect()->where(
            "IF(shipping_method='bpostshm_bpost_international', CONCAT('International: ', shipping_name), shipping_name) like ?"
            , "%$value%"
        );
        
        return $this;
    }
}