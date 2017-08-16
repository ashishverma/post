<?php
class Bpost_ShM_Model_Adminhtml_System_Config_Backend_Shipping_Tablerates_International extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the international tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('bpost_shm/tablerates_international')->uploadAndImport($this);
    }
}