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
class Harapartners_SpeedTax_Model_Failsafe_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax {
	
	public function __construct(){
		parent::__construct();
        $this->_calculator  = Mage::getSingleton('speedtax/failsafe_calculation');
    }
    
	protected function _calculateShippingTax(Mage_Sales_Model_Quote_Address $address, $taxRateRequest){
        if(!Mage::getStoreConfig("speedtax/speedtax/tax_shipping")){
        	return $this;
        }
        return parent::_calculateShippingTax($address, $taxRateRequest);
    }
    
}