<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 */

class Harapartners_SpeedTax_Adminhtml_System_Config_AjaxController extends Mage_Adminhtml_Controller_Action {

    public function authenticationAction() {
    	     
		try{
			$username = $this->getRequest()->getParam('username');
    		$password = $this->getRequest()->getParam('password');
    		$companyCode = $this->getRequest()->getParam('company_code');
    		$isTestMode = $this->getRequest()->getParam('is_test_mode');
			
    		//Check encrypted config
			if (preg_match('/^\*+$/', $password)) {
	            $password = Mage::helper('core')->decrypt(Mage::getStoreConfig('speedtax/speedtax/password'));
	        }
			
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
	        
	        //auth_token and data_token are handled within
			$result = Mage::helper ('speedtax/connector_speedtax')->authenticationRequest($credentials);
			
		}catch(Exception $e){
			$errorMessage = $e->getMessage();
			if(!$errorMessage){
				$errorMessage = 'Connection failed.';
			}
			echo json_encode(array(
					'status' => 0,
					'message' => $errorMessage
			)); //Json error
			exit;
		}
		//Need to send $websiteResultJson, the store config is still the cache value, not our new value 
		echo json_encode(array(
				'status' => 1,
				'message' => 'Validation successful!'
		)); //Json success
		exit;
    }
    
}