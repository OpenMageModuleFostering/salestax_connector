<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 * 
 */

class Harapartners_SpeedTax_Model_Failsafe_Calculation_Rate extends Mage_Core_Model_Abstract {
	
	protected function _construct() {
		$this->_init('speedtax/failsafe_calculation_rate');
	}
	
	public function loadByCountryIdRegionIdPostcode($countryId, $regionId, $postcode){
		$this->addData($this->getResource()->loadByCountryIdRegionIdPostcode($countryId, $regionId, $postcode));
        return $this;
	}
	
	public function loadByCountryIdRegionId($countryId, $regionId){
		$this->addData($this->getResource()->loadByCountryIdRegionId($countryId, $regionId));
        return $this;
	}
	
	protected function _beforeSave(){
		$datetime = date('Y-m-d H:i:s');
    	if(!$this->getId()){
    		$this->setData('created_at', $datetime);
    	}
    	$this->setData('updated_at', $datetime);
    	parent::_beforeSave();
    }
	
}