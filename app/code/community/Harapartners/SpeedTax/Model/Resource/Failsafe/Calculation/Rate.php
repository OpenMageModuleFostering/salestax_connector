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

class Harapartners_SpeedTax_Model_Resource_Failsafe_Calculation_Rate extends Mage_Core_Model_Resource_Db_Abstract {
	
	protected function _construct() {
		$this->_init('speedtax/failsafe_calculation_rate', 'rate_id');
	}
	
	public function loadByCountryIdRegionIdPostcode($countryId, $regionId, $postcode){
        $readAdapter = $this->_getReadAdapter();
        $select = $readAdapter->select()
                ->from($this->getMainTable())
                ->where('country_id=:country_id')
                ->where('region_id=:region_id')
                ->where('postcode=:postcode');
        $result = $readAdapter->fetchRow($select, array('country_id' => $countryId, 'region_id' => $regionId, 'postcode' => $postcode));
        if (!$result) {
           $result = array(); 
        }
        return $result;
    }
    
	public function loadByCountryIdRegionId($countryId, $regionId){
        $readAdapter = $this->_getReadAdapter();
        $select = $readAdapter->select()
                ->from($this->getMainTable())
                ->where('country_id=:country_id')
                ->where('region_id=:region_id');
        $result = $readAdapter->fetchRow($select, array('country_id' => $countryId, 'region_id' => $regionId));
        if (!$result) {
           $result = array(); 
        }
        return $result;
    }
	
}