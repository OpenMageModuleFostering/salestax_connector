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

class Harapartners_SpeedTax_Block_Adminhtml_Notification extends Mage_Adminhtml_Block_Template {
	
    protected function _toHtml() {
    	$htmlContent = "";
    	$notificationDisabled = json_decode(Mage::getStoreConfig(Harapartners_SpeedTax_Helper_Data::XML_PATH_NOTIFICATION_DISABLED_COMPRESS), 1);
    	
    	//TODO: add ACL restriction to screen some messages
        $speedtaxMessages = array();
    	
    	// ---------------- Tax on Discount ---------------- //
    	if(!Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT)
    			&& empty($notificationDisabled[Harapartners_SpeedTax_Helper_Data::NOTIFICATION_KEY_TAX_ON_DISCOUNT])
    	){
    		$speedtaxMessages[Harapartners_SpeedTax_Helper_Data::NOTIFICATION_KEY_TAX_ON_DISCOUNT] = '
Your tax configuration "Calculation Setting >> Apply Customer Tax" is set to <strong class="label">Before Discount</strong>, which is the default value. 
If your business logic requires tax <strong class="label">After Discount</strong>, please click <a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array("section" => "tax")). '">HERE</a> to update the setting. 
<i><a href="' . Mage::helper("adminhtml")->getUrl("speedtax_adminhtml/notification/disable", array("notification_key" => Harapartners_SpeedTax_Helper_Data::NOTIFICATION_KEY_TAX_ON_DISCOUNT)) . '">Dismiss this message</a></i>';
    	}
    	
    	// ---------------- Output ---------------- //
	    foreach($speedtaxMessages as $messageKey => $messageContent){
		$htmlContent .= <<< HTML_CONTENT
<div class="notification-global">
	<strong class="label">SalesTax notification:</strong> $messageContent
</div>
HTML_CONTENT;
	    }
    	return $htmlContent;
    }

}
