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
    
    // Notification settings
    const XML_PATH_NOTIFICATION_DISABLED_COMPRESS	 	= 'speedtax/notification/disabled_compress';
    const NOTIFICATION_KEY_TAX_ON_DISCOUNT				= 'tax_on_discount';
    const ERROR_LOG_FILE								= 'speedtax_error.log';
    const DEFAULT_MAGENTO_COLLECTION_PAGE_SIZE			= 50;
	
	protected $_xmlPathPrefix = 'speedtax/speedtax/';
    
    // =========================== config and essential flags =========================== //
	public function isSpeedTaxEnabled(){
		return Mage::getStoreConfig ( $this->_xmlPathPrefix . 'is_enabled' );
	}
	
	public function isUseProductTaxClass(){
	    return Mage::getStoreConfig ( $this->_xmlPathPrefix . 'is_use_product_tax_class' );
	}
	
	public function isAddressValidationOn($address, $storeId) {
        return Mage::getStoreConfig( $this->_xmlPathPrefix . 'validate_address', $storeId);
    }
    
    public function isFailsafeEnabled(){
    	return Mage::getStoreConfig ( 'speedtax/failsafe/is_enabled' );
    }
	
	public function getSpeedtaxInvoiceStatusValues() {
        return array(
            Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_PENDING 	=> Mage::helper('speedtax')->__('Pending'),
            Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_POSTED 		=> Mage::helper('speedtax')->__('Posted'),
            Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_VOID 		=> Mage::helper('speedtax')->__('Void'),
            Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_ERROR 		=> Mage::helper('speedtax')->__('Error'),
            Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_FAILSAFE 	=> Mage::helper('speedtax')->__('Failsafe'),
        );
    }
    
}