<?php
/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */
/**
 * Class Bpost_ShM_Model_System_Config_Source_Country
 */
class Bpost_ShM_Model_System_Config_Source_Country
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('bpost_shm/country_collection')->loadData()->toOptionArray(false);
        }

        $options = $this->_options;

        return $options;
    }

}
