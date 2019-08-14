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
class Harapartners_SpeedTax_Model_Failsafe_Calculation extends Mage_Tax_Model_Calculation {
	
	public function getRate($request)
    {
    	if (!$request->getCountryId() || !$request->getRegionId() || !$request->getPostcode()) {
            return 0;
        }
        
        //Ignore default non-taxable goods, product_class_id = 0 or missing
        if(!$request->getData('product_class_id')){
        	return 0.0;
        }
        
        $failsafeCalRate = Mage::getModel('speedtax/failsafe_calculation_rate');
        // Level 1: exact match
        $failsafeCalRate->loadByCountryIdRegionIdPostcode($request->getCountryId(), $request->getRegionId(), $request->getPostcode());
        if(!!$failsafeCalRate->getId() && !!$failsafeCalRate->getTaxRate()){
        	return $failsafeCalRate->getTaxRate();
        }
        // Level 2: partial match
    	$failsafeCalRate->loadByCountryIdRegionId($request->getCountryId(), $request->getRegionId());
        if(!!$failsafeCalRate->getId() && !!$failsafeCalRate->getTaxRate()){
        	return $failsafeCalRate->getTaxRate();
        }
        
        // Default
        return 0.0;
    }

}