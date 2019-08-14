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
class Harapartners_SpeedTax_Model_Rewrite_Tax_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax {
	
	public function collect(Mage_Sales_Model_Quote_Address $address) {
	    if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return parent::collect($address);
		}
	    
		$store = $address->getQuote()->getStore();
		$customer = $address->getQuote()->getCustomer();
		
		$address->setTotalAmount($this->getCode(), 0);
		$address->setBaseTotalAmount($this->getCode(), 0);
		
		$address->setTaxAmount(0);
		$address->setBaseTaxAmount(0);
		$address->setShippingTaxAmount(0);
		$address->setBaseShippingTaxAmount(0);
		
		//Init
		$this->_setAddress($address);
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
		
		try {
			$processor = Mage::helper('speedtax/processor');
			$responseResult = $processor->queryQuoteAddress($address);
			//Address line item amount and shipping tax amount are updated within the query
			if (!!$responseResult) {
				$taxAmount = $processor->getTotalTax($responseResult);
				$this->_addAmount(Mage::app()->getStore()->convertPrice($taxAmount, false));
				$this->_addBaseAmount($taxAmount);
			}
		} catch(Exception $e) {
			//Tax collecting is very important, this is within the collect total (cannot bubble exceptions), force a redirect
			Mage::logException($e);
			$maskedErrorMessage = 'There is an error calculating tax.';
			Mage::getSingleton('core/session')->addError($maskedErrorMessage);
			throw new Mage_Core_Model_Session_Exception($maskedErrorMessage); //Session exceptions will be redirected to base URL
		}
		
		return $this;
	}

}