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
abstract class Harapartners_ConnectorHub_Helper_Connector_Core {

	const REQUEST_ACTION_AUTHENTICATION = 'authentication';
	
	const REQUEST_SERVICE_MODE_TEST 		= 0;
	const REQUEST_SERVICE_MODE_SANDBOX 		= 10;
	const REQUEST_SERVICE_MODE_BETA 		= 50;
	const REQUEST_SERVICE_MODE_PRODUCTION 	= 100;
	
	const RESPONSE_STATUS_FAIL 				= 0;
	const RESPONSE_STATUS_SUCCESS 			= 100;
	const RESPONSE_STATUS_REAUTH 			= 200;
	
	const DEFAULT_CURLOPT_TIMEOUT 			= 60;
	
	const ERROR_LOG_FILE					= 'connectorhub_error.log';
	
	protected $_serviceType					= null;
	
	// ======================= API Core Functions ======================= //
	protected function _coreMakeRequest($requestUrl, $requestPostData){
		$rawResponse = null;
		try{
			$requestCh = curl_init();
			curl_setopt($requestCh, CURLOPT_URL, $requestUrl);
			curl_setopt($requestCh, CURLOPT_POST, 1);
			curl_setopt($requestCh, CURLOPT_POSTFIELDS, $requestPostData);
			curl_setopt($requestCh, CURLOPT_SSL_VERIFYHOST, $this->_getCurlSslVerifyHost());
			curl_setopt($requestCh, CURLOPT_SSL_VERIFYPEER, $this->_getCurlSslVerifyPeer());
			curl_setopt($requestCh, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($requestCh, CURLOPT_TIMEOUT, self::DEFAULT_CURLOPT_TIMEOUT);
			$rawResponse = curl_exec($requestCh);
			$curlError = curl_error($requestCh);
			if(!!$curlError){
				Mage::log($curlError, null, self::ERROR_LOG_FILE, true);
			}
			curl_close($requestCh);
		}catch (Exception $ex){
			Mage::log($ex->getMessage(), null, self::ERROR_LOG_FILE, true);
		}
		return $rawResponse;
	}
	
	protected function _coreMakeRequestBefore($request){
		return null;
	}
	
	protected function _coreMakeRequestAfter($request){
		return null;
	}
	
	protected function _processRequest($request){
		// -------------- Prepare additional request meta -------------- //
		$request['meta'] = $this->_mergeInCreds($request);
		
		// -------------- Preparation: log request in debug mode -------------- //
		if($this->getIsDebugMode() && !empty($request['data'])){
			$requestLogData = $request['data'];
			unset($requestLogData['credentials']);
			Mage::log("Request Data:" . serialize($requestLogData), null, "{$this->getServiceType()}_transaction.log", true);
		}
		
		// -------------- Preparation: Check API user, add usage lock if necessary -------------- //
		$this->_coreMakeRequestBefore($request);
		
		// -------------- Make request -------------- //
		$dataToken = $this->getToken('data_token', $request); //Data token must pair with 'data_token', provided within $request
		if(!!$dataToken && isset($request['data'])){
			$request['enc_data'] = $this->encryptRequestData($dataToken, $request['data']);
			unset($request['data']);
		}
		$requestUrl = $this->_getConnectorHubUrl();
		$requestPostData = array('params' => $this->_compressRequestParams($request));
		$rawResponse = $this->_coreMakeRequest($requestUrl, $requestPostData);
		$response = $this->_uncompressResponseParams($rawResponse);
		
		// -------------- Preparation: Check API user, release usage lock if necessary -------------- //
		$this->_coreMakeRequestAfter($request);
		
		// -------------- Process response -------------- //
		if(!$response){
			if($rawResponse){
				Mage::throwException('Connector Hub: There is an error processing the request.');
			}
			Mage::throwException('Connector Hub: Connection failed.');
		}
		//For now treat reauth the same as fail, the notification will prompt the admin user to update their credentials
		switch($response->meta->status){
			case self::RESPONSE_STATUS_FAIL:
			case self::RESPONSE_STATUS_REAUTH:
				Mage::throwException("Connector Hub ({$this->getServiceType()}): " . $response->meta->message);
				break;
			case self::RESPONSE_STATUS_SUCCESS:
				break;
			default:
				break;
		}
		if(!!$dataToken && !empty($response->enc_data)){
			$response->data = $this->decryptResponseData($dataToken, $response->enc_data);
			unset($response->enc_data);
		}
		
		if($this->getIsDebugMode() && !empty($response->data)){
			$responseLogData = json_decode(json_encode($response->data), true);
			unset($responseLogData['credentials']);
			Mage::log("Response Data:" . serialize($responseLogData), null, "{$this->getServiceType()}_transaction.log", true);
		}
		
		return $response;
	}
	
	protected function _mergeInCreds($request){
		$adminEmail = "";
		if(!!Mage::getSingleton('admin/session')->getUser()){
			$adminEmail = Mage::getSingleton('admin/session')->getUser()->getEmail();
		}
		return array_merge($request['meta'], array(
				'service_type' 		=> $this->getServiceType(),
				'service_mode' 		=> $this->getServiceMode($request),
				'auth_token' 		=> $this->getToken('auth_token', $request),
				'site_url' 			=> Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
				'account_name' 		=> $adminEmail,
				'platform' 			=> 'magento',
				'version' 			=> Mage::getVersion(),
				'debug_mode'		=> $this->getIsDebugMode()
		));
	}
	
	// ======================= API Action Functions ======================= //
    public function authenticationRequest($credentials){
    	//For authentication request, directly send the data, rather than encrypted data with token
    	$request = array(
    			'meta' => array(
    					'action' => self::REQUEST_ACTION_AUTHENTICATION, 
    					'credentials' => $credentials
    			)
    	);
    	$response = $this->_processRequest($request);
    	
    	//service_mode can be '0'
    	if(is_null($response->meta->service_mode) || !$response->meta->auth_token || !$response->meta->data_token){
			Mage::throwException("Connector Hub ({$this->getServiceType()}): Authentication failed.");
		}
		
		$this->saveResponseToken($response);
        return $response;
    }
    
    
    // ======================= Essential overrides ======================= //
    abstract public function getServiceMode($request = null);
    
    public function getIsDebugMode(){
    	return false;
    }
    
	abstract protected function _getConnectorHubUrl();
	
	abstract protected function _getConfigDataBasePath($key);
	
	abstract protected function _prepareCredentials();
    
    
    // ====================== Utilities/Helpers ====================== //
	public function getServiceType(){
    	return $this->_serviceType;
    }
	
    /**
     * Get token by name, support mutliple service mode, does NOT support multiple API user accounts
     *
     * @param string $tokenName Typically 'auth_token' or 'data_token'
     * @param unknown_type $serviceMode For multiple service modes, typically: test/beta/sandbox/live
     * @param unknown_type $userId For multiple users on the same API account, can be string value (user email)
     * @return unknown
     */
    public function getToken($tokenName, $request){
		$serviceMode = $this->getServiceMode($request);
    	//Load from DB, no cache
    	$coreConfigData = Mage::getModel('core/config_data')->load($this->_getConfigDataBasePath('tokens'), 'path');
    	$existingToken = json_decode($coreConfigData->getValue(), 1);
    	if(isset($existingToken[$serviceMode][$tokenName])){
    		return $existingToken[$serviceMode][$tokenName];
    	}
    	return null;
    }
    
	public function saveResponseToken($response){
    	//Save to DB, no cache
    	$coreConfigData = Mage::getModel('core/config_data')->load($this->_getConfigDataBasePath('tokens'), 'path');
    	//Global config
    	$coreConfigData->setScope('default');
    	$coreConfigData->setScopeId(0);
    	$coreConfigData->setpath($this->_getConfigDataBasePath('tokens')); //In case the first time save
    	
    	$existingToken = json_decode($coreConfigData->getValue(), 1);
    	$existingToken[$response->meta->service_mode] = array(
    			'auth_token' => $response->meta->auth_token,
    			'data_token' => $response->meta->data_token
    	);
    	$coreConfigData->setValue(json_encode($existingToken));
    	$coreConfigData->save();
    	return;
    }
    
	public function encryptRequestData($dataToken, $requestData){
    	$encData = Mage::helper('connectorhub/mcrypt')->init($dataToken)->encrypt(json_encode($requestData));
        return base64_encode($encData);
    }
    
	public function decryptResponseData($dataToken, $responseData){
		$decData = Mage::helper('connectorhub/mcrypt')->init($dataToken)->decrypt(base64_decode($responseData));
		return json_decode($decData);
    }

	protected function _getCurlSslVerifyHost(){
    	// Global override
    	if(!!Mage::getStoreConfig('connectorhub/general/disable_verify_host')){
    		return 0;
    	}
    	return 2;
    }
    
    protected function _getCurlSslVerifyPeer(){
    	// Global override
    	if(!!Mage::getStoreConfig('connectorhub/general/disable_verify_peer')){
    		return 0;
    	}
    	
    	$sslVerifyPeer = 0;
    	try{
    		$secureUrlScheme = parse_url(Mage::getUrl('', array('_secure'=>true)), PHP_URL_SCHEME);
    		if(strcasecmp($secureUrlScheme, "https") == 0){
    			$sslVerifyPeer = 1;
    		}
    	}catch(Exception $e){
    		$sslVerifyPeer = 0;
    	}
    	return $sslVerifyPeer;
    }
    
	protected function _getConnectorHubRootUrl(){
	    return Mage::helper('connectorhub/connector_config')->getConnectorHubRootUrl();
	}
	
	protected function _compressRequestParams($request){
		return base64_encode(gzcompress(json_encode($request)));
	}
	
	protected function _uncompressResponseParams($rawResponse){
		$response = json_decode(gzuncompress(base64_decode($rawResponse)));
		if(!$response){
			//Fall back to non-compressed logic
			$response = json_decode(base64_decode($rawResponse));
		}
		return $response;
	}
    
}