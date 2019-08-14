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
class Harapartners_SpeedTax_Helper_Connector_Speedtax extends Harapartners_ConnectorHub_Helper_Connector_Core {
	
	const REQUEST_ACTION_CALCULATE_INVOICE 			= 'CalculateInvoice';
	const REQUEST_ACTION_POST_INVOICE 				= 'PostInvoice';
	const REQUEST_ACTION_POST_CREDITMEMO 			= 'PostCreditmemo';
	const REQUEST_ACTION_VOID_INVOICE				= 'VoidInvoice';
	const REQUEST_ACTION_BATCH_VOID_INVOICES		= 'BatchVoidInvoices';
	
	const RESPONSE_TYPE_SUCCESS 					= 'SUCCESS';
	const RESPONSE_TYPE_FAILED_WITH_ERRORS 			= 'FAILED_WITH_ERRORS';
	const RESPONSE_TYPE_FAILED_INVOICE_NUMBER		= 'FAILED_INVOICE_NUMBER';
	
	protected $_serviceType							= 'speedtax';
	
	// ======================= Essential overrides ======================= //
    public function getServiceMode($request = null){
    	//Always default to production for safety
    	$serviceMode = Harapartners_ConnectorHub_Helper_Connector_Core::REQUEST_SERVICE_MODE_PRODUCTION;
    	
    	//Check from request first (in case it's a new authorization), default to last saved config value
    	if(!!Mage::getStoreConfig($this->_getConfigDataBasePath('is_test_mode'))){
    		$serviceMode = Harapartners_ConnectorHub_Helper_Connector_Core::REQUEST_SERVICE_MODE_TEST;
    	}
    	if(isset($request['meta']['credentials']['service_mode'])){
			$serviceMode = $request['meta']['credentials']['service_mode']; //Allow 0 and other empty values
		}
    	
    	return $serviceMode;
    }
    
    public function getIsDebugMode(){
    	return Mage::getStoreConfig($this->_getConfigDataBasePath('is_debug_transaction'));
    }
    
	protected function _getConnectorHubUrl(){
		return $this->_getConnectorHubRootUrl() . 'SpeedTax.php';
	}
	
	protected function _getConfigDataBasePath($key){
		return 'speedtax/speedtax/' . $key;
	}
	
	protected function _prepareCredentials(){
		$username = Mage::getStoreConfig($this->_getConfigDataBasePath('username'));
    	$password = Mage::helper('core')->decrypt(Mage::getStoreConfig($this->_getConfigDataBasePath('password')));
    	$companyCode = Mage::getStoreConfig($this->_getConfigDataBasePath('company_code'));
    	$isTestMode = Mage::getStoreConfig($this->_getConfigDataBasePath('is_test_mode'));
		if(!!$isTestMode){
			$serviceMode = Harapartners_ConnectorHub_Helper_Connector_Core::REQUEST_SERVICE_MODE_TEST;
		}else{
			$serviceMode = Harapartners_ConnectorHub_Helper_Connector_Core::REQUEST_SERVICE_MODE_PRODUCTION;
		}

		$credentials = array(
				'username' => $username,
				'password' => $password,
				'company_code' => $companyCode,
				'service_mode' => $serviceMode
		);
		return $credentials;
	}
	
	// ====================== Requests ====================== //
	public function calculateInvoiceRequest($sptxInvoice){
		$response = $this->_doInvoiceRequest($sptxInvoice, self::REQUEST_ACTION_CALCULATE_INVOICE);
        return $response->data->result;
	}
	
	public function postInvoiceRequest($sptxInvoice){
		$response = $this->_doInvoiceRequest($sptxInvoice, self::REQUEST_ACTION_POST_INVOICE);
		return $response->data->result;
	}
	
	public function postCreditmemoRequest($sptxInvoice){
		$response = $this->_doInvoiceRequest($sptxInvoice, self::REQUEST_ACTION_POST_CREDITMEMO);
		return $response->data->result;
	}
	
	public function batchVoidInvoices($invoiceNumbers){
		$credentials = $this->_prepareCredentials();
		$request = array(
    			'meta' => array(
    					'action' => self::REQUEST_ACTION_BATCH_VOID_INVOICES
    			),
    			'data' => array(
    					'credentials' => $credentials,
    					'invoice_numbers' => $invoiceNumbers
    			)
    	);
		$response = $this->_processRequest($request);
		$this->_validateResponse($response);
		return $response;
	}
	
	protected function _doInvoiceRequest($sptxInvoice, $actionType){
		$credentials = $this->_prepareCredentials();
		$request = array(
    			'meta' => array(
    					'action' => $actionType
    			),
    			'data' => array(
    					'credentials' => $credentials,
    					'invoice' => $sptxInvoice
    			)
    	);
    	
    	$response = $this->_loadCachedInvoiceResponse($sptxInvoice, $actionType);
    	if(!$response){
			$response = $this->_processRequest($request);
			//Save cache upon successful transactions only
			if(isset($response->meta->status) && $response->meta->status == Harapartners_ConnectorHub_Helper_Connector_Core::RESPONSE_STATUS_SUCCESS){
				$this->_saveCachedInvoiceResponse($response, $sptxInvoice, $actionType);
			}
    	}
		$this->_validateResponse($response);
		return $response;
	}
	
	protected function _loadCachedInvoiceResponse($sptxInvoice, $actionType){
    	if(!$this->_isCacheRequestAllowed($actionType)){
    		return false;
    	}
    	$sptxInvoiceCacheKey = $this->_generateInvoiceCacheKey($sptxInvoice);
    	$response = Mage::getSingleton('speedtax/session')->loadCachedResponse($sptxInvoiceCacheKey);
    	return $response;
	}
	
	protected function _saveCachedInvoiceResponse($response, $sptxInvoice, $actionType){
		if(!$this->_isCacheRequestAllowed($actionType)){
    		return false;
    	}
    	$sptxInvoiceCacheKey = $this->_generateInvoiceCacheKey($sptxInvoice);
    	$response = Mage::getSingleton('speedtax/session')->saveCachedResponse($sptxInvoiceCacheKey, $response);
    	return true;
	}
	
	protected function _validateResponse($response){
		//Essential validation for SpeedTax response data structure
		if(empty($response->data->result->resultType)){
    		Mage::throwException('Invalid tax response');
    	}
        switch ($response->data->result->resultType) {
            case self::RESPONSE_TYPE_SUCCESS:
                break;
            case self::RESPONSE_TYPE_FAILED_WITH_ERRORS:
            case self::RESPONSE_TYPE_FAILED_INVOICE_NUMBER:
            default :
            	Mage::throwException('Tax request failed');
                break;
        }
        return $this;
	}
	
	protected function _isCacheRequestAllowed($actionType){
		$allRequestCache = false;
    	switch($actionType){
    		case self::REQUEST_ACTION_CALCULATE_INVOICE:
    			$allRequestCache = true;
    			break;
    		default:
    			$allRequestCache = false;
    			break;
    	}
    	return $allRequestCache;
	}
	
	protected function _generateInvoiceCacheKey($sptxInvoice){
		$sptxInvoice = clone $sptxInvoice;
		$sptxInvoice->invoiceDate = null;
		return md5(json_encode($sptxInvoice));
	}
	
}