<?php
class Bpost_ShM_Model_Adminhtml_System_Config_Backend_Shipping_Tablerates_Clickcollect extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the clickcollect tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('bpost_shm/tablerates_clickcollect')->uploadAndImport($this);
    }
}