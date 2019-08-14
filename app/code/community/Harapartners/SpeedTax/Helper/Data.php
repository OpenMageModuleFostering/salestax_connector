<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license [^]
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 *
 */
class Harapartners_SpeedTax_Helper_Data extends Mage_Core_Helper_Abstract {
    
	protected $_xmlPathPrefix = 'speedtax/speedtax/';
    
    // =========================== config and essential flags =========================== //
	public function isSpeedTaxEnabled(){
		return Mage::getStoreConfig ( $this->_xmlPathPrefix . 'is_enabled' );
	}
	
	public function useTaxClass(){
	    return Mage::getStoreConfig ( $this->_xmlPathPrefix . 'customized_tax_class' );
	}
	
	public function isAddressValidationOn($address, $storeId) {
        return Mage::getStoreConfig( $this->_xmlPathPrefix . 'validate_address', $storeId);
    }
	
}