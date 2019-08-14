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
class Harapartners_SpeedTax_Helper_Failsafe extends Mage_Core_Helper_Abstract {
	
	const FALLBACK_CALCULATION_PRECISION = 4;
    
    public function updateFailsafeRate(Mage_Sales_Model_Quote_Address $mageQuoteAddress, $responseResult){
    	if(!$mageQuoteAddress || !$responseResult){
    		return;
    	}
    	
    	$decPrecision = pow(0.1, Harapartners_SpeedTax_Helper_Failsafe::FALLBACK_CALCULATION_PRECISION);
		$requestData = array();
		$sptxInvoice = Mage::helper('speedtax/connector_data')->prepareSpeedTaxInvoiceByMageQuoteAddress($mageQuoteAddress);
		if(!empty($sptxInvoice->lineItems)){
			foreach($sptxInvoice->lineItems as $lineItem){
				if(!empty($lineItem->customReference) && !empty($lineItem->salesAmount->decimalValue)){
					$requestData[$lineItem->customReference] = $lineItem->salesAmount->decimalValue;
				}
			}
		}
    	$responseData = array();
		if(!empty($responseResult->lineItemBundles->lineItems)){
			foreach($responseResult->lineItemBundles->lineItems as $lineItem){
				if(!empty($lineItem->customReference) && !empty($lineItem->taxAmount->decimalValue)){
					$responseData[$lineItem->customReference] = $lineItem->taxAmount->decimalValue;
				}
			}
		}
    	
		$taxRate = 0.0; //This is a percentage
		foreach($requestData as $customReference => $salesAmount){
			if($salesAmount > $decPrecision && isset($responseData[$customReference])) {
				$taxRate = round($responseData[$customReference] / $salesAmount * 100.0, Harapartners_SpeedTax_Helper_Failsafe::FALLBACK_CALCULATION_PRECISION);
				break;
			}
		}
		
		//Do not update zero tax rates
		if($taxRate <= $decPrecision){
			return;
		}

		$countryId 	= $mageQuoteAddress->getCountryId();
    	$regionId	= $mageQuoteAddress->getRegionId();
		$postcode	= $mageQuoteAddress->getPostcode(); //$postcode = preg_replace('/[^0-9\-]*/', '', $address->getPostcode()); //US zip code clean up
		$failsafeCalRate = Mage::getModel('speedtax/failsafe_calculation_rate');
		$failsafeCalRate->loadByCountryIdRegionIdPostcode($countryId, $regionId, $postcode);
		
		if($failsafeCalRate->getTaxRate() != $taxRate){
			$failsafeCalRate->setData(array(
					'country_id'		=> $countryId,
					'region_id'			=> $regionId,
					'postcode'			=> $postcode,
					'tax_rate'			=> $taxRate
			));
			$failsafeCalRate->save();
		}

    	return null;
    }
	
}