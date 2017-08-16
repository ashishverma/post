<?php
/**
 * Created by PHPro
 *
 * @subpackage   Shipping
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Block_Adminhtml_Sales_Order_View_Tab_Returnbarcode
 */
class Bpost_ShM_Block_Adminhtml_Sales_Order_View_Tab_Returnbarcode
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    /**
     * Constructs the block
     *
     */
    public function __construct(){
        parent::__construct();
        $this->setId('bpost_returnbarcode_grid');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $textString = "This order did not (yet) generate labels which automatically returned return label barcodes. This feature might not be actived. Please refer to the documentation and your Shipping Settings under 'System > Configuration > Sales' for more information.";
        $this->_emptyText = Mage::helper('adminhtml')->__($textString);
    }

    /**
     * prepare collection to use for the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('sales/order_shipment')
            ->getCollection()
            ->addFieldToFilter('order_id',array('eq' => $this->getOrder()->getId()))
            ->addFieldToFilter('bpost_return_barcode',array('notnull'=>1));
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    /**
     * prepare columns used in the grid.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('bpost_shm');

        $this->addColumn('increment_id', array(
            'header' => $helper->__('Shipment'),
            'type' => 'text',
            'index' => 'increment_id',
            'filter_index' => 'main_table.increment_id'
        ));

        $this->addColumn('returnbarcode', array(
            'header' => $helper->__('Barcode #'),
            'type' => 'text',
            'index' => 'bpost_return_barcode',
            'filter_index' => 'main_table.bpost_return_barcode'
        ));

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
     * Generate rowurl.
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }

    /**
     * Returns tab label.
     *
     * @return string
     */
    public function getTabLabel() {
        return Mage::helper('bpost_shm')->__('bpost Return Barcodes');
    }

    /**
     * Returns tab title.
     *
     * @return string
     */
    public function getTabTitle() {
        return Mage::helper('bpost_shm')->__('bpost Return Barcodes');
    }

    /**
     * Checks if tab can be shown.
     *
     * @return bool
     */
    public function canShowTab() {
        return true;
    }

    /**
     * Checks if the tab has to be hidden.
     *
     * @return bool
     */
    public function isHidden() {
        return false;
    }

    /**
     * Returns the order object.
     *
     * @return mixed
     */
    public function getOrder(){
        return Mage::registry('current_order');
    }
}