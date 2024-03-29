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

class Harapartners_SpeedTax_Helper_Processor extends Mage_Core_Helper_Abstract {
	
	const SPEEDTAX_INVOICE_STATUS_PENDING 			= 0;
	const SPEEDTAX_INVOICE_STATUS_POSTED 			= 100;
	const SPEEDTAX_INVOICE_STATUS_VOID 				= 200;
	const SPEEDTAX_INVOICE_STATUS_ERROR 			= 300;
	const SPEEDTAX_INVOICE_STATUS_FAILSAFE			= 900;
	
    // ========================== Actions ========================== //
 	public function queryQuoteAddress(Mage_Sales_Model_Quote_Address $mageQuoteAddress){
        if (!$this->_isTaxable($mageQuoteAddress)){
        	$this->_clearQuoteAddressTax($mageQuoteAddress);
            return false;
        }
        $sptxInvoice = Mage::helper('speedtax/connector_data')->prepareSpeedTaxInvoiceByMageQuoteAddress($mageQuoteAddress);
        if(!$sptxInvoice|| !$sptxInvoice->lineItems){
        	$this->_clearQuoteAddressTax($mageQuoteAddress);
            return false;
        }
		$responseResult = Mage::helper('speedtax/connector_speedtax')->calculateInvoiceRequest($sptxInvoice);
        $this->_applyResponseToQuote($responseResult, $mageQuoteAddress);
        return $responseResult;
    }
    
	public function postOrderInvoice(Mage_Sales_Model_Order_Invoice $mageOrderInvoice){
		if(!!$mageOrderInvoice->getData('speedtax_transaction_id')){
    		Mage::throwException('This invoice was already posted through SalesTax.');
    	}
    	
		$customerGroupId = $mageOrderInvoice->getOrder()->getCustomerGroupId();
        if($this->_isCustomerGroupTaxExempt($customerGroupId)){
        	return false;
        }
    	
        $sptxInvoice = Mage::helper('speedtax/connector_data')->prepareSpeedTaxInvoiceByMageOrderInvoice($mageOrderInvoice);
        if(!$sptxInvoice || !$sptxInvoice->lineItems){
            return false;
        }
		//No caching allowed for order invoice
        $responseResult = Mage::helper('speedtax/connector_speedtax')->postInvoiceRequest($sptxInvoice);
        $this->_applyResponseToInvoice($responseResult, $mageOrderInvoice);
        
        return $responseResult;
    }
    
    public function postOrderCreditmemo(Mage_Sales_Model_Order_Creditmemo $mageOrderCreditmemo) {
    	if(!!$mageOrderCreditmemo->getData('speedtax_transaction_id')){
    		Mage::throwException('This credit memo was already posted through SalesTax.');
    	}
    	
    	$customerGroupId = $mageOrderCreditmemo->getOrder()->getCustomerGroupId();
        if($this->_isCustomerGroupTaxExempt($customerGroupId)){
        	return false;
        }
    	
        $sptxInvoice = Mage::helper('speedtax/connector_data')->prepareSpeedTaxInvoiceByMageOrderCreditmemo($mageOrderCreditmemo);
        if(!$sptxInvoice || !$sptxInvoice->lineItems){
            return false;
        }
		//No caching allowed for order invoice
        $responseResult = Mage::helper('speedtax/connector_speedtax')->postCreditmemoRequest($sptxInvoice);
        $this->_applyResponseToCreditmemo($responseResult, $mageOrderCreditmemo);
        
        return $responseResult;
    }
    
	public function cancelAllOrderTransactions(Mage_Sales_Model_Order $mageOrder) {
    	$invoiceNumbers = array();
    	$updateObjectArray = array();
    	foreach($mageOrder->getInvoiceCollection() as $mageOrderInvoice){
    		if(!!$mageOrderInvoice->getData('speedtax_invoice_number') 
    				&& $mageOrderInvoice->getData('speedtax_invoice_status') == self::SPEEDTAX_INVOICE_STATUS_POSTED ){
    			$invoiceNumbers[] = $mageOrderInvoice->getData('speedtax_invoice_number');
    			$updateObjectArray[$mageOrderInvoice->getData('speedtax_invoice_number')] = $mageOrderInvoice;
    		}
    	}
		foreach($mageOrder->getCreditmemosCollection() as $mageOrderCreditmemo){
    		if(!!$mageOrderCreditmemo->getData('speedtax_invoice_number')
    				&& $mageOrderCreditmemo->getData('speedtax_invoice_status') == self::SPEEDTAX_INVOICE_STATUS_POSTED ){
    			$invoiceNumbers[] = $mageOrderCreditmemo->getData('speedtax_invoice_number');
    			$updateObjectArray[$mageOrderCreditmemo->getData('speedtax_invoice_number')] = $mageOrderCreditmemo;
    		}
    	}
    	
        if(!$invoiceNumbers){
            return false;
        }
		//No caching allowed for order invoice
        $responseResult = Mage::helper('speedtax/connector_speedtax')->batchVoidInvoices($invoiceNumbers);
        
        //Update status
        $batchVoidResults = json_decode($responseResult->data->result->batchVoidResults, 1);
		foreach($batchVoidResults as $invoiceNumber => $voidResult){
			$updateObject = $updateObjectArray[$invoiceNumber];
			if($voidResult == Harapartners_SpeedTax_Helper_Connector_Speedtax::RESPONSE_TYPE_SUCCESS){
				$updateObject->setData('speedtax_invoice_status', self::SPEEDTAX_INVOICE_STATUS_VOID);
			}else{
				$updateObject->setData('speedtax_invoice_status', self::SPEEDTAX_INVOICE_STATUS_ERROR);
			}
			$updateObject->save();
		}
        
        return $responseResult;
    }
    
    
    // ========================== Utility Functions ========================== //
    //Mage_Sales_Model_Quote_Address or Mage_Sales_Model_Order_Address 
    protected function _isTaxable($mageAddress) {
        //$mageAddress can be quote of order address, or null for virtual product
        //Note: only check shipping to avoid double tax calculation
        if(!($mageAddress instanceof Varien_Object)  
                || $mageAddress->getAddressType() != Mage_Sales_Model_Quote_Address::TYPE_SHIPPING
        ){
            return false;
        }
        
        //Check tax exempt customer group
        //Only for quote, order/inovice will always reports the amount the tax captured
        if($mageAddress instanceof Mage_Sales_Model_Quote_Address && !!$mageAddress->getQuote()){
        	//Note, 0 for guest group
        	$customerGroupId = $mageAddress->getQuote()->getCustomerGroupId();
        	if($this->_isCustomerGroupTaxExempt($customerGroupId)){
        		return false;
        	}
        }
        
        //Nexus test
        //We need to test for exceptions where billing address is used for calculation
        //Note this is after the address type test to avoid double tax calculation
    	$mappedAddress = Mage::helper('speedtax/connector_data')->mapAddressExceptions($mageAddress);
        $originsString = Mage::getStoreConfig('speedtax/speedtax/origins');
        if(!in_array($mappedAddress->getRegionId(), explode(',', $originsString))){
        	return false;
        }
        
        //By default, calculation tax
        return true;
    }
    
	protected function _isCustomerGroupTaxExempt($customerGroupId) {
        $taxExemptCustomerGroupString = Mage::getStoreConfig('speedtax/speedtax/tax_exempt_customer_group');
        return in_array($customerGroupId,  explode(',', $taxExemptCustomerGroupString));
	}
    
	//Mage_Sales_Model_Quote_Address ONLY
    protected function _applyResponseToQuote($responseResult, Mage_Sales_Model_Quote_Address $mageQuoteAddress){
        foreach ( $mageQuoteAddress->getAllItems() as $mageQuoteItem ) {
            $taxAmount = $this->_getLineItemTaxAmountByItemId($responseResult, $mageQuoteItem->getId());
            $mageQuoteItem->setTaxAmount($taxAmount);
            $mageQuoteItem->setBaseTaxAmount($taxAmount);
            if(($mageQuoteItem->getRowTotal() - $mageQuoteItem->getDiscountAmount()) > 0){
                $mageQuoteItem->setTaxPercent (sprintf("%.4f", 100*$taxAmount/($mageQuoteItem->getRowTotal() - $mageQuoteItem->getDiscountAmount())));
            }
        }
        $taxShippingAmount = $this->_getTaxShippingAmount($responseResult);
        if(!!$taxShippingAmount){
            $mageQuoteAddress->setShippingTaxAmount($taxShippingAmount);
            $mageQuoteAddress->setBaseShippingTaxAmount($taxShippingAmount);
        }
        return;
    }
    
	protected function _clearQuoteAddressTax(Mage_Sales_Model_Quote_Address $mageQuoteAddress){
		//Only clear tax related to this quote address
        foreach ( $mageQuoteAddress->getAllItems() as $mageQuoteItem ) {
            $mageQuoteItem->setTaxAmount(0.0);
            $mageQuoteItem->setBaseTaxAmount(0.0);
            $mageQuoteItem->setTaxPercent(0.0);
        }
        $mageQuoteAddress->setShippingTaxAmount(0.0);
        $mageQuoteAddress->setBaseShippingTaxAmount(0.0);
        return;
    }
    
    protected function _applyResponseToInvoice($responseResult, Mage_Sales_Model_Order_Invoice $mageOrderInvoice){
    	$mageOrderInvoice->setData('speedtax_transaction_id', $responseResult->transactionId);
    	$mageOrderInvoice->setData('speedtax_invoice_number', $responseResult->invoiceNumber);
    	$mageOrderInvoice->setData('speedtax_invoice_status', self::SPEEDTAX_INVOICE_STATUS_POSTED);
    	$mageOrderInvoice->save();
        return;
    }
    
	protected function _applyResponseToCreditmemo($responseResult, Mage_Sales_Model_Order_Creditmemo $mageOrderCreditmemo){
    	$mageOrderCreditmemo->setData('speedtax_transaction_id', $responseResult->transactionId);
    	$mageOrderCreditmemo->setData('speedtax_invoice_number', $responseResult->invoiceNumber);
    	$mageOrderCreditmemo->setData('speedtax_invoice_status', self::SPEEDTAX_INVOICE_STATUS_POSTED);
    	$mageOrderCreditmemo->save();
        return;
    }
    
    public function getTotalTax($responseResult) {
        return $responseResult->totalTax->decimalValue;
    }
    
	protected function _getLineItemTaxAmountByItemId($responseResult, $itemId) {
       	foreach($responseResult->lineItemBundles->lineItems as $responseLineItem){
       		if($responseLineItem->customReference == $itemId){
       			return $responseLineItem->taxAmount->decimalValue;
       		}
       	}
       	return 0.0;
    }
    
	protected function _getTaxShippingAmount($responseResult) {
		if(isset($responseResult->lineItemBundles->lineItems)){
			foreach($responseResult->lineItemBundles->lineItems as $responseLineItem){
	       		if($responseLineItem->productCode == Mage::getStoreConfig("speedtax/speedtax/shipping_tax_code")){
	       			return $responseLineItem->taxAmount->decimalValue;
	       		}
	       	}
		}
       	return 0.0;
    }
    
    
    //Adds a comment to order history. Method choosen based on Magento version.
//    protected function _addStatusHistoryComment($order, $comment) {
//        if(method_exists($order, 'addStatusHistoryComment')) {
//            $order->addStatusHistoryComment($comment)->save();;
//        } elseif(method_exists($order, 'addStatusToHistory')) {
//            $order->addStatusToHistory($order->getStatus(), $comment, false)->save();;
//        }
//        return $this;
//    }

}