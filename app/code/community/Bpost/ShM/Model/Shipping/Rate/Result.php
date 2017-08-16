<?php
class Bpost_ShM_Model_Shipping_Rate_Result extends Mage_Shipping_Model_Rate_Result
{
    /**
     * we create an empty sort function
     * this way our shipping rates will keep their sort order
     */
    public function sortRatesByPrice(){
        return;
    }
}
