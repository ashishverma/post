<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class Bpost_ShM_Model_Resource_Country_Collection
 */
class Bpost_ShM_Model_Resource_Country_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialises the model, the abstract file will render a collection from it.
     */
    public function _construct()
    {
        $this->_init("bpost_shm/country");
    }


    /**
     * Convert collection items to select options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->_toOptionArray('country_id', 'country_code');
        $sort = array();

        foreach ($options as $data) {
            $name = Mage::app()->getLocale()->getCountryTranslation($data['label']);
            if (!empty($name)) {
                $sort[$name] = $data['label'];
            }
        }

        Mage::helper('core/string')->ksortMultibyte($sort);

        $options = array();
        foreach ($sort as $label => $value) {
            $options[] = array(
                'value' => $value,
                'label' => $label
            );
        }

        return $options;
    }
}
