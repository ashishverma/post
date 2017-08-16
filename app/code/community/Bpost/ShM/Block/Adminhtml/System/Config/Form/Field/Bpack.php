<?php

/**
 * Created by PHPro
 *
 * @package      Bpost
 * @subpackage   ShM
 * @author       PHPro (info@phpro.be)
 *
 * Class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Bpack
 */
class Bpost_ShM_Block_Adminhtml_System_Config_Form_Field_Bpack extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    const CACHE_KEY = 'bpost_product_config_cache';
    const BPACK_BUSINESS = 'bpack 24h business';
    /**
     * @var bool
     */
    protected $_showElement = false;

    /**
     * Enter description here...
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        try {
            $xml = $this->getProductConfig();

            $this->_showElement = false;
            foreach ($xml->deliveryMethod as $deliveryMethodData) {
                foreach ($deliveryMethodData->product as $productData) {
                    $attributes = $productData->attributes();
                    $productName = (string) $attributes["name"];
                    if ($productName == self::BPACK_BUSINESS) {
                        $this->_showElement = true;
                    }
                }
            }
        } catch (Exception $e) {
            $element->setValue(0);
            $this->_showElement = false;
        }

        return $element->getElementHtml();
    }

    /**
     * Decorate field row html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml($element, $html)
    {
        if ($this->showElement()) {
            return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>' . $this->getJavascript($element);
        }

        return '<tr id="row_' . $element->getHtmlId() . '" style="display:none">' . $html . '</tr>';
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function getJavascript($element)
    {
        $javascript = <<<EOT
<script type="text/javascript">
    document.observe('dom:loaded', onBpackProductChange);
    Event.observe('{$element->getHtmlId()}', 'change', onBpackProductChange);
    
    function onBpackProductChange() 
    {
        specific=$('{$element->getHtmlId()}').value;
        $('{$this->getSecondPresentationElementId($element)}').disabled = (specific==1);
        $('{$this->getSecondPresentationElementId($element)}_from').disabled = (specific==1);              
        if (specific == 1) {                            
            $('{$this->getSecondPresentationElementId($element)}').value = 1;
            $('{$this->getSecondPresentationElementId($element)}_from').value = 0;
        }
        
        $('row_{$this->getSecondPresentationElementId($element)}_from').hide();
        if ($('{$this->getSecondPresentationElementId($element)}').value == 1) {
            $('row_{$this->getSecondPresentationElementId($element)}_from').show();
        }
    }
</script>
EOT;

        return $javascript;
    }

    /**
     * @param $element
     * @return string
     */
    protected function getSecondPresentationElementId($element)
    {
        return substr($element->getId(), 0, strpos($element->getId(), 'product')) . 'second_presentation';
    }

    /**
     * Weather or not to show the element.
     *
     * @return string
     */
    protected function showElement()
    {
        return (bool) $this->_showElement;
    }

    /**
     * @return SimpleXMLElement
     */
    protected function getProductConfig()
    {
        $cache = Mage::app()->getCache();

        if (false === $cache->load(self::CACHE_KEY)) {
            $api = Mage::getModel('bpost_shm/api', true);
            $apiResponse = $api->getProductConfig();

            if (!$apiResponse) {
                Mage::throwException('Failed to authenticate with bpost, please check your credentials.');
            }

            $xml = $apiResponse->getBody();
            $cache->save($xml, self::CACHE_KEY, array('bpost_cache'), 60*60);
        }

        return simplexml_load_string($cache->load(self::CACHE_KEY));
    }
}
