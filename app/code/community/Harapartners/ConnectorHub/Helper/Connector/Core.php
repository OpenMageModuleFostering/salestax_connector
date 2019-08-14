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
	
	protected $_serviceType					= null;
	
	protected function _processRequest($request){
		// -------------- Prepare additional request meta -------------- //
		$adminEmail = "";
		if(!!Mage::getSingleton('admin/session')->getUser()){
			$adminEmail = Mage::getSingleton('admin/session')->getUser()->getEmail();
		}
		$request['meta'] = array_merge($request['meta'], array(
				'service_type' 		=> $this->getServiceType(),
				'service_mode' 		=> $this->getServiceMode(),
				'auth_token' 		=> $this->getToken('auth_token'),
				'site_url' 			=> Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
				'account_name' 		=> $adminEmail,
				'platform' 			=> 'magento',
				'version' 			=> Mage::getVersion()
		));
		
		if(isset($request['data'])){
			$request['enc_data'] = $this->encryptRequestData($request['data']);
			unset($request['data']);
		}
		
		// -------------- Make request -------------- //
		$requestUrl = $this->_getConnectorHubUrl();
		$requestPostData = array('params' => base64_encode(json_encode($request)));
		$requestCh = curl_init();
		curl_setopt($requestCh, CURLOPT_URL, $requestUrl);
		curl_setopt($requestCh, CURLOPT_POST, 1);
		curl_setopt($requestCh, CURLOPT_POSTFIELDS, $requestPostData);
		curl_setopt($requestCh, CURLOPT_SSL_VERIFYPEER, $this->_getCurlSslVerifyPeer());
		curl_setopt($requestCh, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($requestCh, CURLOPT_TIMEOUT, self::DEFAULT_CURLOPT_TIMEOUT);
		$curlRawResponse = curl_exec($requestCh);
		$response = json_decode(base64_decode($curlRawResponse));
		curl_close($requestCh);
		
		// -------------- Process response -------------- //
		if(!$response){
			if($curlRawResponse){
				throw new Exception('Connector Hub: There is an error processing the request.');
			}
			throw new Exception('Connector Hub: Connection failed.');
		}
		//For now treat reauth the same as fail, the notification will prompt the admin user to update their credentials
		switch($response->meta->status){
			case self::RESPONSE_STATUS_FAIL:
			case self::RESPONSE_STATUS_REAUTH:
				throw new Exception("Connector Hub ({$this->getServiceType()}): " . $response->meta->message);
				break;
			case self::RESPONSE_STATUS_SUCCESS:
				break;
			default:
				break;
		}
		if(!!$response->enc_data){
			$dataToken = $this->getToken('data_token');
			$decData = Mage::helper('connectorhub/mcrypt')->init($dataToken)->decrypt(base64_decode($response->enc_data));
			$response->data = json_decode($decData);
		}
		
		return $response;
	}
	
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
			throw new Exception("Connector Hub ({$this->getServiceType()}): Authentication failed.");
		}
		
		$this->saveTokens($response);
        return $response;
    }
    
    
    // ======================= Essential overrides ======================= //
    abstract public function getServiceMode();
    
	abstract protected function _getConnectorHubUrl();
	
	abstract protected function _getConfigDataBasePath($key);
	
	abstract protected function _prepareCredentials();
    
    
    // ====================== Utilities/Helpers ====================== //
	public function getServiceType(){
    	return $this->_serviceType;
    }
	
    public function getToken($tokenName, $serviceMode = null){
    	if(!$serviceMode){
    		$serviceMode = $this->getServiceMode();
    	}
    	
    	//Load from DB, no cache
    	$coreConfigData = Mage::getModel('core/config_data')->load($this->_getConfigDataBasePath('tokens'), 'path');
    	$existingToken = json_decode($coreConfigData->getValue(), 1);
    	if(isset($existingToken[$serviceMode][$tokenName])){
    		return $existingToken[$serviceMode][$tokenName];
    	}
    	return null;
    }
    
	public function saveTokens($response){
    	//Save to DB, no cache
    	$coreConfigData = Mage::getModel('core/config_data')->load($this->_getConfigDataBasePath('tokens'), 'path');
    	//In case the first time save
    	$coreConfigData->setpath($this->_getConfigDataBasePath('tokens'));
    	
    	$existingToken = json_decode($coreConfigData->getValue(), 1);
    	$existingToken[$response->meta->service_mode] = array(
    			'auth_token' => $response->meta->auth_token,
    			'data_token' => $response->meta->data_token
    	);
    	$coreConfigData->setValue(json_encode($existingToken));
    	$coreConfigData->save();
    	return;
    }
    
	public function encryptRequestData($requestData){
    	//For authentication request, directly send the data, rather than encrypted data with token
    	$authToken = $this->getToken('auth_token');
    	$dataToken = $this->getToken('data_token');
    	$encData = Mage::helper('connectorhub/mcrypt')->init($dataToken)->encrypt(json_encode($requestData));
        return base64_encode($encData);
    }

    protected function _getCurlSslVerifyPeer(){
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
    
}