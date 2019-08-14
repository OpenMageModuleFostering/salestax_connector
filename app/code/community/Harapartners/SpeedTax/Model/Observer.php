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

class Harapartners_SpeedTax_Model_Observer extends Mage_Core_Model_Abstract {
    
    public function saleOrderInvoicePay(Varien_Event_Observer $observer) {
        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return;
		}
        $invoice = $observer->getEvent()->getInvoice();
        try {
            $processor = Mage::helper('speedtax/processor');
			$responseResult = $processor->postOrderInvoice($invoice);
        } catch( Exception $e ) {
            //Suppress exception so that the transaction is not reverted (payment already processed)
            Mage::logException($e);
			$maskedErrorMessage = 'There is an error processing tax information.';
			Mage::getSingleton('core/session')->addError($maskedErrorMessage);
        }
    }
    
    public function salesOrderCreditmemoRefund(Varien_Event_Observer $observer) {
        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return;
		}
        $creditmemo = $observer->getEvent()->getCreditmemo();
        try {
        	$processor = Mage::helper('speedtax/processor');
			$responseResult = $processor->postOrderCreditmemo($creditmemo);
        } catch( Exception $e ) {
            //Suppress exception so that the transaction is not reverted (payment already processed)
            Mage::logException($e);
			$maskedErrorMessage = 'There is an error processing tax information.';
			Mage::getSingleton('core/session')->addError($maskedErrorMessage);
        }
    }
    
    //For manual cancel or order edit related cancellation
	public function orderCancelAfter(Varien_Event_Observer $observer) {
        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return;
		}
        $order = $observer->getEvent()->getOrder();
        try {
        	$processor = Mage::helper('speedtax/processor');
			$responseResult = $processor->cancelAllOrderTransactions($order);
        } catch( Exception $e ) {
            //Suppress exception so that the transaction is not reverted (payment already processed)
            Mage::logException($e);
			$maskedErrorMessage = 'There is an error processing tax information.';
			Mage::getSingleton('core/session')->addError($maskedErrorMessage);
        }
    }
    
    
}