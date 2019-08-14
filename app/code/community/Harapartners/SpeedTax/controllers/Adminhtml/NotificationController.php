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

class Harapartners_SpeedTax_Adminhtml_NotificationController extends Mage_Adminhtml_Controller_Action {
	
	public function disableAction() {
		try{
			$notificationkey = $this->getRequest()->getParam('notification_key');
			$coreConfigData = Mage::getModel('core/config_data')->load(Harapartners_SpeedTax_Helper_Data::XML_PATH_NOTIFICATION_DISABLED_COMPRESS, 'path');
	    	//In case the first time save
	    	$coreConfigData->setpath(Harapartners_SpeedTax_Helper_Data::XML_PATH_NOTIFICATION_DISABLED_COMPRESS);
	    	$notificationDisabled = json_decode($coreConfigData->getValue(), 1);
	    	$notificationDisabled[$notificationkey] = 1;
	    	$coreConfigData->setValue(json_encode($notificationDisabled));
    		$coreConfigData->save();
		}catch (Exception $ex){
			//Silence
		}
		$this->_redirectReferer();
	}
	
	
}
