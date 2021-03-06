<?php
class Bpost_ShM_Model_Resource_Tablerates_Homedelivery extends Mage_Core_Model_Resource_Db_Abstract{

    /**
     * Website Id selected in scope.
     *
     * @var int
     */
    protected $_importWebsiteId     = 0;

    /**
     * Array to fill with possible errors.
     *
     * @var array
     */
    protected $_importErrors        = array();

    /**
     * Number of rows imported.
     *
     * @var int
     */
    protected $_importedRows        = 0;

    /**
     * Conversion.
     *
     * @var array
     */
    protected $_importUniqueHash    = array();

    /**
     * Conversion.
     *
     * @var
     */
    protected $_importIso2Countries;

    /**
     * Conversion.
     *
     * @var
     */
    protected $_importIso3Countries;

    /**
     * Regions to import.
     *
     * @var
     */
    protected $_importRegions;

    /**
     * Condition name selected in sysconfig.
     *
     * @var
     */
    protected $_importConditionName;

    /**
     * Full name of condition selected in sysconfig.
     *
     * @var array
     */
    protected $_conditionFullNames  = array();


    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("bpost_shm/bpost_tablerates_homedelivery", "pk");
    }


    /**
     * Fetch rate from the table for selected shipping address.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return array
     */
    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $adapter = $this->_getReadAdapter();
        $bind = array(
            ':website_id' => (int) $request->getWebsiteId(),
            ':country_id' => $request->getDestCountryId(),
            ':region_id' => (int) $request->getDestRegionId(),
            ':postcode' => $request->getDestPostcode()
        );
        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('website_id = :website_id')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC', 'dest_zip DESC', 'condition_value DESC'))
            ->limit(1);

        // Render destination condition
        $orWhere = '(' . implode(') OR (', array(
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = ''",

                // Handle asterix in dest_zip field
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = 0 AND dest_zip = '*'",

                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = ''",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
            )) . ')';
        $select->where($orWhere);

        // Render condition by condition name
        if (is_array($request->getConditionName())) {
            $orWhere = array();
            $i = 0;
            foreach ($request->getConditionName() as $conditionName) {
                $bindNameKey  = sprintf(':condition_name_%d', $i);
                $bindValueKey = sprintf(':condition_value_%d', $i);
                $orWhere[] = "(condition_name = {$bindNameKey} AND condition_value <= {$bindValueKey})";
                $bind[$bindNameKey] = $conditionName;
                $bind[$bindValueKey] = $request->getData($conditionName);
                $i++;
            }

            if ($orWhere) {
                $select->where(implode(' OR ', $orWhere));
            }
        } else {
            $bind[':condition_name']  = $request->getConditionName();
            $bind[':condition_value'] = $request->getData($request->getConditionName());

            $select->where('condition_name = :condition_name');
            $select->where('condition_value <= :condition_value');
        }

        $result = $adapter->fetchRow($select, $bind);
        // Normalize destination zip code
        if ($result && $result['dest_zip'] == '*') {
            $result['dest_zip'] = '';
        }
        return $result;
    }


    /**
     * Upload and import table rates csv.
     *
     * @param Varien_Object $object
     * @return $this
     */
    public function uploadAndImport(Varien_Object $object)
    {
        if (empty($_FILES['groups']['tmp_name']['bpost_homedelivery']['fields']['import_homedelivery']['value'])) {
            return $this;
        }

        $csvFile = $_FILES['groups']['tmp_name']['bpost_homedelivery']['fields']['import_homedelivery']['value'];
        $website = Mage::app()->getWebsite($object->getScopeId());

        $this->_importWebsiteId     = (int)$website->getId();
        $this->_importUniqueHash    = array();
        $this->_importErrors        = array();
        $this->_importedRows        = 0;

        $io     = new Varien_Io_File();
        $info   = pathinfo($csvFile);
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        // check and skip headers
        $headers = $io->streamReadCsv();
        if ($headers === false || count($headers) < 5) {
            $io->streamClose();
            Mage::throwException(Mage::helper('bpost_shm')->__('Invalid Table Rates File Format'));
        }
        if ($object->getData('groups/bpost_homedelivery/fields/condition_name/inherit') == '1') {
            $conditionName = (string)Mage::getConfig()->getNode('default/carriers/bpost_homedelivery/condition_name');
        } else {
            $conditionName = $object->getData('groups/bpost_homedelivery/fields/condition_name/value');
        }
        $this->_importConditionName = $conditionName;
        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();

        try {
            $rowNumber  = 1;
            $importData = array();

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            // delete old data by website and condition name
            $condition = array(
                'website_id = ?'     => $this->_importWebsiteId,
                'condition_name = ?' => $this->_importConditionName
            );
            $adapter->delete($this->getMainTable(), $condition);

            while (false !== ($csvLine = $io->streamReadCsv())) {
                $rowNumber ++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->_getImportRow($csvLine, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }

                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();
        } catch (Mage_Core_Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::helper('bpost_shm')->log($e,Zend_Log::ERR);
            Mage::throwException(Mage::helper('bpost_shm')->__('An error occurred while importing table rates.'));
        }

        $adapter->commit();

        if ($this->_importErrors) {
            $error = Mage::helper('bpost_shm')->__('File has not been imported. See the following list of errors: %s', implode(" \n", $this->_importErrors));
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * Load directory countries.
     *
     * @return Mage_Shipping_Model_Resource_Carrier_Tablerate
     */
    protected function _loadDirectoryCountries()
    {
        if (!is_null($this->_importIso2Countries) && !is_null($this->_importIso3Countries)) {
            return $this;
        }

        $this->_importIso2Countries = array();
        $this->_importIso3Countries = array();

        /** @var $collection Mage_Directory_Model_Resource_Country_Collection */
        $collection = Mage::getResourceModel('directory/country_collection');
        foreach ($collection->getData() as $row) {
            $this->_importIso2Countries[$row['iso2_code']] = $row['country_id'];
            $this->_importIso3Countries[$row['iso3_code']] = $row['country_id'];
        }

        return $this;
    }

    /**
     * Load directory regions.
     *
     * @return Mage_Shipping_Model_Resource_Carrier_Tablerate
     */
    protected function _loadDirectoryRegions()
    {
        if (!is_null($this->_importRegions)) {
            return $this;
        }

        $this->_importRegions = array();

        /** @var $collection Mage_Directory_Model_Resource_Region_Collection */
        $collection = Mage::getResourceModel('directory/region_collection');
        foreach ($collection->getData() as $row) {
            $this->_importRegions[$row['country_id']][$row['code']] = (int)$row['region_id'];
        }

        return $this;
    }

    /**
     * Return import condition full name by condition name code.
     *
     * @param string $conditionName
     * @return string
     */
    protected function _getConditionFullName($conditionName)
    {
        if (!isset($this->_conditionFullNames[$conditionName])) {
            $name = Mage::getSingleton('shipping/carrier_tablerate')->getCode('condition_name_short', $conditionName);
            $this->_conditionFullNames[$conditionName] = $name;
        }
        return $this->_conditionFullNames[$conditionName];
    }

    /**
     * Validate row for import and return table rate array or false.
     * Error will be add to _importErrors array.
     *
     * @param array $row
     * @param int $rowNumber
     * @return array|false
     */
    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 5) {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Invalid Table Rates format in the Row #%s', $rowNumber);
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        // validate country
        if (isset($this->_importIso2Countries[$row[0]])){
            $countryId = $this->_importIso2Countries[$row[0]];
        } elseif (isset($this->_importIso3Countries[$row[0]])) {
            $countryId = $this->_importIso3Countries[$row[0]];
        } elseif ($row[0] == '*' || $row[0] == '') {
            $countryId = '0';
        } else {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Invalid Country "%s" in the Row #%s.', $row[0], $rowNumber);
            return false;
        }

        // validate region
        if ($countryId != '0' && isset($this->_importRegions[$countryId][$row[1]])) {
            $regionId = $this->_importRegions[$countryId][$row[1]];
        } elseif ($row[1] == '*' || $row[1] == '') {
            $regionId = 0;
        } else {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Invalid Region/State "%s" in the Row #%s.', $row[1], $rowNumber);
            return false;
        }

        // detect zip code
        if ($row[2] == '*' || $row[2] == '') {
            $zipCode = '*';
        } else {
            $zipCode = $row[2];
        }

        // validate condition value
        $value = $this->_parseDecimalValue($row[3]);
        if ($value === false) {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Invalid %s "%s" in the Row #%s.', $this->_getConditionFullName($this->_importConditionName), $row[3], $rowNumber);
            return false;
        }

        // validate price
        $price = $this->_parseDecimalValue($row[4]);
        if ($price === false) {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Invalid Shipping Price "%s" in the Row #%s.', $row[4], $rowNumber);
            return false;
        }

        // protect from duplicate
        $hash = sprintf("%s-%d-%s-%F", $countryId, $regionId, $zipCode, $value);
        if (isset($this->_importUniqueHash[$hash])) {
            $this->_importErrors[] = Mage::helper('bpost_shm')->__('Duplicate Row #%s (Country "%s", Region/State "%s", Zip "%s" and Value "%s").', $rowNumber, $row[0], $row[1], $zipCode, $value);
            return false;
        }
        $this->_importUniqueHash[$hash] = true;

        return array(
            $this->_importWebsiteId,    // website_id
            $countryId,                 // dest_country_id
            $regionId,                  // dest_region_id,
            $zipCode,                   // dest_zip
            $this->_importConditionName,// condition_name,
            $value,                     // condition_value
            $price                      // price
        );
    }

    /**
     * Save all import data.
     *
     * @param array $data
     * @return $this
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = array('website_id', 'dest_country_id', 'dest_region_id', 'dest_zip',
                'condition_name', 'condition_value', 'price');
            $this->_getWriteAdapter()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }

    /**
     * Conversion.
     *
     * @param $value
     * @return bool|float
     */
    protected function _parseDecimalValue($value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        $value = (float)sprintf('%.4F', $value);
        if ($value < 0.0000) {
            return false;
        }
        return $value;
    }
}