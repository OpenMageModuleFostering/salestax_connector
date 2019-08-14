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
class Harapartners_ConnectorHub_Helper_Connector_Config
{

    public function getConnectorHubRootUrl()
    {
        //return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "ConnectorHub/";
        return "https://connectorhub.harapartners.com/";
    }
}