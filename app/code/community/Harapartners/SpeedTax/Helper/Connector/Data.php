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
class Harapartners_SpeedTax_Helper_Connector_Data extends Mage_Core_Helper_Abstract {
	
	const TAX_SHIPPING_LINEITEM_TAX_CLASS 		= 'TAX_SHIPPING';
    const TAX_SHIPPING_LINEITEM_REFERNCE_NAME 	= 'TAX_SHIPPING';
    
    protected $_productTaxClassNoneTaxableId 	= 0; //Magento default
//    protected $_allowedCountryIds = array('US', 'CA');
    
	protected $_shipFromAddress = null;
	protected $_shipToAddress = null;
	
	// ========================== Main entry points ========================== //
	public function prepareSpeedTaxInvoiceByMageQuoteAddress(Mage_Sales_Model_Quote_Address $mageQuoteAddress) {
		$sptxInvoice = new stdClass();
		$sptxInvoice->lineItems = array();
        $sptxInvoice->customerIdentifier = Mage::getStoreConfig ( 'speedtax/speedtax/username' );
        
        foreach ( $mageQuoteAddress->getAllItems () as $mageQuoteItem ) {
            if(!!$mageQuoteItem->getParentItemId()){
                continue;
            }
            //Multiple shipping checkout, $mageQuoteItem is instance of Mage_Sales_Model_Quote_Address_Item, not a sub-class of Mage_Sales_Model_Quote_Item
            //Many product related fields must be obtained from the product object directly
            if($mageQuoteItem->getProduct()->getTaxClassId() == $this->_productTaxClassNoneTaxableId){
                continue;
            }
            
            //Respect Magento tax/discount config
        	$taxableAmount = $mageQuoteItem->getRowTotal();
        	if(!!Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT, $mageQuoteItem->getStoreId())){
        		$taxableAmount = $taxableAmount - $mageQuoteItem->getDiscountAmount() + $mageQuoteItem->getHiddenTaxAmount();
        	}
            if($taxableAmount <= 0){
                continue;
            }
            
            $lineItem = new stdClass();
            $lineItem->productCode = $this->_getProductCode($mageQuoteItem);
            $lineItem->customReference = $mageQuoteItem->getId();
            $lineItem->quantity = $mageQuoteItem->getQty();
            $lineItem->shipFromAddress = $this->_getShipFromAddress();
            $lineItem->shipToAddress = $this->_getShippingToAddress($mageQuoteAddress); //Note, address type is validated at the entry point 'queryQuoteAddress'
            
            //Price of row total, not unit price
            $lineItemPrice = new stdClass();
            $lineItemPrice->decimalValue = $taxableAmount;
            $lineItem->salesAmount = $lineItemPrice;
            
            $lineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $lineItem;
        }
        
        // ----- Other line items ----- //
        //If global store config specifies: "tax_shipping", then create shipping cost line item. Note this is different from "Tax_Shipping" tax class of a product
        $shipingAmount = $mageQuoteAddress->getShippingAmount();
        if(!!Mage::getStoreConfig("speedtax/speedtax/tax_shipping") && $shipingAmount > 0.0){
            $shippingLineItem = $this->_generateLineItemFromShippingCost($mageQuoteAddress, $shipingAmount);
            $shippingLineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $shippingLineItem;
        }
        
        $sptxInvoice->invoiceDate = date('Y-m-d H:i:s');
        return $sptxInvoice;
    }
    
	public function prepareSpeedTaxInvoiceByMageOrderInvoice(Mage_Sales_Model_Order_Invoice $mageOrderInvoice) {
        //Clear the invoice number so that the request is just a query
        $mageOrderAddress = $mageOrderInvoice->getShippingAddress();
        $sptxInvoice = new stdClass();
        $sptxInvoice->lineItems = array();
        //Important to keep unique, invoice should already be attached to the order, count starts from 1
        $sptxInvoice->invoiceNumber = 
        		$mageOrderInvoice->getOrder()->getIncrementId() 
        		. '-INV-' . ($mageOrderInvoice->getOrder()->getInvoiceCollection()->count());
        $sptxInvoice->customerIdentifier = Mage::getStoreConfig ( 'speedtax/speedtax/username' );
        
        foreach ( $mageOrderInvoice->getAllItems() as $mageItem ) {
            if(!$mageItem->getTaxAmount() || $mageItem->getTaxAmount() <= 0.0){
                continue;
            }
            
        	//Respect Magento tax/discount config
        	$taxableAmount = $mageItem->getRowTotal();
        	if(!!Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT, $mageItem->getStoreId())){
        		$taxableAmount = $taxableAmount - $mageItem->getDiscountAmount() + $mageItem->getHiddenTaxAmount();
        	}
            
            $lineItem = new stdClass();
            $lineItem->productCode = $this->_getProductCode($mageItem);
            $lineItem->customReference = $mageItem->getOrderItemId(); //This is during invoice creation, no ID available
            $lineItem->quantity = $mageItem->getQty();
            $lineItem->shipFromAddress = $this->_getShipFromAddress();
            $lineItem->shipToAddress = $this->_getShippingToAddress($mageOrderAddress); //Note, address type is validated at the entry point 'queryQuoteAddress'
            
            //Price of row total, not unit price
            $lineItemPrice = new stdClass();
            $lineItemPrice->decimalValue = $taxableAmount;
            $lineItem->salesAmount = $lineItemPrice;
            
            $lineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $lineItem;
        }
        
        // ----- Other line items ----- //
		//If global store config specifies: "tax_shipping", then create shipping cost line item. Note this is different from "Tax_Shipping" tax class of a product
		if($mageOrderInvoice->getShippingAmount() === null){
        	$mageOrderInvoice->collectTotals();
        }
        $shipingAmount = $mageOrderInvoice->getShippingAmount();
        if(!!Mage::getStoreConfig("speedtax/speedtax/tax_shipping") && $shipingAmount > 0.0){
            $shippingLineItem = $this->_generateLineItemFromShippingCost($mageOrderAddress, $shipingAmount);
            $shippingLineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $shippingLineItem;
        }
        
        $sptxInvoice->invoiceDate = date('Y-m-d H:i:s');
        return $sptxInvoice;
    }
    
	public function prepareSpeedTaxInvoiceByMageOrderCreditmemo(Mage_Sales_Model_Order_Creditmemo $mageOrderCreditmemo) {
        //Clear the invoice number so that the request is just a query
        $mageOrderAddress = $mageOrderCreditmemo->getShippingAddress();
        $sptxInvoice = new stdClass();
        $sptxInvoice->lineItems = array();
        
        //Important to keep unique, credit memo not yet attached to the order, count ++ so that it starts from 1
        $sptxInvoice->invoiceNumber = 
        		$mageOrderCreditmemo->getOrder()->getIncrementId() 
        		. '-CR-' . ($mageOrderCreditmemo->getOrder()->getCreditmemosCollection()->count() + 1);
        $sptxInvoice->customerIdentifier = Mage::getStoreConfig ( 'speedtax/speedtax/username' );
        
        foreach ( $mageOrderCreditmemo->getAllItems() as $mageItem ) {
            if(!$mageItem->getTaxAmount() || $mageItem->getTaxAmount() <= 0.0){
                continue;
            }
            
        	//Respect Magento tax/discount config
        	$taxableAmount = $mageItem->getRowTotal();
        	if(!!Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT, $mageItem->getStoreId())){
        		$taxableAmount = $taxableAmount - $mageItem->getDiscountAmount() + $mageItem->getHiddenTaxAmount();
        	}
            
            $lineItem = new stdClass();
            $lineItem->productCode = $this->_getProductCode($mageItem);
            $lineItem->customReference = $mageItem->getOrderItemId(); //This is during credit memo creation, no ID available
            $lineItem->quantity = $mageItem->getQty();
            $lineItem->shipFromAddress = $this->_getShipFromAddress();
            $lineItem->shipToAddress = $this->_getShippingToAddress($mageOrderAddress); //Note, address type is validated at the entry point 'queryQuoteAddress'
            
            //Price of row total, not unit price
            $lineItemPrice = new stdClass();
            $lineItemPrice->decimalValue = $taxableAmount;
            $lineItem->salesAmount = $lineItemPrice;
            
            $lineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $lineItem;
        }
        
        // ----- Other line items ----- //
		//If global store config specifies: "tax_shipping", then create shipping cost line item. Note this is different from "Tax_Shipping" tax class of a product
		if($mageOrderCreditmemo->getShippingAmount() === null){
        	$mageOrderCreditmemo->collectTotals();
        }
        $shipingAmount = $mageOrderCreditmemo->getShippingAmount();
        if(!!Mage::getStoreConfig("speedtax/speedtax/tax_shipping") && $shipingAmount > 0.0){
            $shippingLineItem = $this->_generateLineItemFromShippingCost($mageOrderAddress, $shipingAmount);
            $shippingLineItem->lineItemNumber = count( $sptxInvoice->lineItems );
            $sptxInvoice->lineItems[] = $shippingLineItem;
        }
        
        $sptxInvoice->invoiceDate = date('Y-m-d H:i:s');
        return $sptxInvoice;
    }
    
    // ========================== Utilities ========================== //
	protected function _generateLineItemFromShippingCost($mageAddress, $shipingAmount) {
        $shippingLineItem = new stdClass();
        $shippingLineItem->productCode = self::TAX_SHIPPING_LINEITEM_TAX_CLASS;
        $shippingLineItem->customReference = self::TAX_SHIPPING_LINEITEM_REFERNCE_NAME;
        $shippingLineItem->quantity = 1;
        $shippingLineItem->shipFromAddress = $this->_getShipFromAddress ();
        $shippingLineItem->shipToAddress = $this->_getShippingToAddress ($mageAddress); //Note, address type is validated at the entry point 'queryQuoteAddress'
        
        $shippingPrice = new stdClass();
        $shippingPrice->decimalValue = $shipingAmount;
        $shippingLineItem->salesAmount = $shippingPrice;

        return $shippingLineItem;
    }
	
    //Shipping Origin Address
	protected function _getShipFromAddress() {
		if($this->_shipFromAddress === null){
	        $this->_shipFromAddress = new stdClass();
	        $countryId = Mage::getStoreConfig ( 'shipping/origin/country_id');
	        $zip = Mage::getStoreConfig ('shipping/origin/postcode');
	        $regionId = Mage::getStoreConfig ( 'shipping/origin/region_id');
	        $state = Mage::getModel('directory/region')->load($regionId)->getName();
	        $city = Mage::getStoreConfig ('shipping/origin/city');
	        $street = Mage::getStoreConfig ('shipping/origin/street');
	            
	        $this->_shipFromAddress->address1 = $street;
	        $this->_shipFromAddress->address2 = $city . ", " . $state . " " . $zip; //. ", " . $countryId;
		}
        return $this->_shipFromAddress;
    }
    
    //Shipping Destination Address
    protected function _getShippingToAddress($address) {
    	if($this->_shipToAddress === null){
			$this->_shipToAddress = new stdClass();
			$country = $address->getCountry();
			$zip = $address->getPostcode(); //$zip = preg_replace('/[^0-9\-]*/', '', $address->getPostcode()); //US zip code clean up
			$state = $address->getRegion(); //No region resolution needed, $this->_getStateCodeByRegionId($address->getState());
			$city = $address->getCity();
			$street = implode(' ', $address->getStreet()); //In case of multiple line address
	            
			$this->_shipToAddress->address1 = $street;
			$this->_shipToAddress->address2 = $city . ", " . $state . " " . $zip; //. ", " . $county;
    	}
        return $this->_shipToAddress;
    }
    
    
    //In a standard setup, tax is calculated by tax class (i.e. product code), if empty use default
    //Advanced calculation by product SKU is also possible. Please contact SpeedTax support to setup advanced service
	protected function _getProductCode($item){
        $useTaxCode = Mage::helper('speedtax')->useTaxClass();
        if(!$useTaxCode){
            return $item->getSku();
        }
        if($taxCode = $this->_getTaxClassByItem($item)){
            return $taxCode;
        }else{
            return $item->getSku();
        }
    }
    
    protected function _getTaxClassByItem($item){
        $storeId = Mage::app()->getStore()->getId();
        $taxClassId = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'tax_class_id', $storeId);
        if($taxClassId){
            $taxClassCode = Mage::getModel('tax/class_source_product')->getOptionText($taxClassId);
        }else{
            $taxClassCode = null;
        }
        return $taxClassCode;
    }
	
}