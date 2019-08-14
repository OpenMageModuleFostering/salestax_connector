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
    
	// ============================== Checkout process, failsafe calculation can be enabled ============================== //
	public function saleOrderSaveBefore(Varien_Event_Observer $observer) {
		//Flag orders with speedtax failsafe calculation
        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return;
		}
        $order = $observer->getEvent()->getOrder();
    	if (Mage::helper('speedtax')->isFailsafeEnabled()){
    		if(Mage::registry('is_speedtax_failsafe_calculation')){
				$order->setData('is_speedtax_failsafe_calculation', 1);
				Mage::unregister('is_speedtax_failsafe_calculation');
    		}
		}
    }
	
    // ============================== Post invoices to SpeedTax (failsafe can be enabled) ============================== //
    public function saleOrderInvoicePay(Varien_Event_Observer $observer) {
        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
			return;
		}
        $invoice = $observer->getEvent()->getInvoice();
    	if (Mage::helper('speedtax')->isFailsafeEnabled()){
    		if(!!$invoice->getOrder()){
    			$invoice->setData('is_speedtax_failsafe_calculation', $invoice->getOrder()->getData('is_speedtax_failsafe_calculation'));
    		}
    	}
        try {
            $processor = Mage::helper('speedtax/processor');
			$responseResult = $processor->postOrderInvoice($invoice);
			if(isset($responseResult->totalTax->decimalValue)){
				$baseSpeedTaxTaxAmount = $responseResult->totalTax->decimalValue;
				$invoice->setData('base_speedtax_tax_amount', $baseSpeedTaxTaxAmount);
				$invoice->setData('speedtax_tax_amount', Mage::app()->getStore()->convertPrice($baseSpeedTaxTaxAmount, false));
			}
        } catch( Exception $e ) {
            //Suppress exception so that the transaction is not reverted (payment already processed)
            //Mage::logException($e);
            Mage::log("Cannot post order invoice: {$e->getMessage()}\r\n{$e->getTraceAsString()}", null, Harapartners_SpeedTax_Helper_Data::ERROR_LOG_FILE, true);
            if (Mage::helper('speedtax')->isFailsafeEnabled()) {
				//Failsafe logic: mark invoice for "post invoice failsafe"
				$invoice->setData('speedtax_invoice_status', Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_FAILSAFE);
			}else{
				$maskedErrorMessage = 'There is an error processing tax information.';
				if (Mage::app()->getStore()->isAdmin()) {
					Mage::getSingleton('adminhtml/session')->addError($maskedErrorMessage);
				}else{
					Mage::getSingleton('core/session')->addError($maskedErrorMessage);
				}
			}
        }
    }
    
    // ============================== Cancel invoices in SpeedTax ============================== //
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
            //Mage::logException($e);
            Mage::log("Cannot post order credit memo: {$e->getMessage()}\r\n{$e->getTraceAsString()}", null, Harapartners_SpeedTax_Helper_Data::ERROR_LOG_FILE, true);
			$maskedErrorMessage = 'There is an error processing tax information for order credit memo.';
			
			//This is backend only activity, do NOT suppress exceptions
			//Mage::getSingleton('core/session')->addError($maskedErrorMessage);
			Mage::throwException($maskedErrorMessage);
        }
    }
    
    //Upon order cancel/edit, credit memo must be created manually for existing invoices, SpeedTax invoices will NOT be voided
	public function orderCancelAfter(Varien_Event_Observer $observer) {
		return;
//        if (!Mage::helper('speedtax')->isSpeedTaxEnabled()) {
//			return;
//		}
//        $order = $observer->getEvent()->getOrder();
//        try {
//        	$processor = Mage::helper('speedtax/processor');
//			$responseResult = $processor->cancelAllOrderTransactions($order);
//        } catch( Exception $e ) {
//            //Suppress exception so that the transaction is not reverted (payment already processed)
//            //Mage::logException($e);
//            Mage::log("Cannot cancel all order transactions: {$e->getMessage()}\r\n{$e->getTraceAsString()}", null, Harapartners_SpeedTax_Helper_Data::ERROR_LOG_FILE, true);
//			$maskedErrorMessage = 'There is an error processing tax information for all order transactions.';
//			
//			//This is backend only activity, do NOT suppress exceptions
//			//Mage::getSingleton('core/session')->addError($maskedErrorMessage);
//			Mage::throwException($maskedErrorMessage);
//        }
    }
    
    // ============================================================================================ //
	// ---------------------------------------- Admin only ---------------------------------------- //
	/**
	 * Prepare important admin panel messages, set data in session
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function adminhtmlOnlyLayoutGenerateBlocksAfter(Varien_Event_Observer $observer){
        $controllerAction = $observer->getEvent()->getData('action');
        $layout = $observer->getEvent()->getData('layout');
        
        //Only add message for administrator who already logged in
        if(!!Mage::getSingleton('admin/session')->getUser() && !!Mage::getSingleton('admin/session')->getUser()->getId()){
	        $notificationsBlock = $layout->getBlock('notifications');
	        if(!!$notificationsBlock && !!($notificationsBlock instanceof Mage_Core_Block_Abstract)){
	        	$nsNotificationBlock = $layout->createBlock('speedtax/adminhtml_notification');
        		$notificationsBlock->append($nsNotificationBlock, 'speedtax_notification');
	        }
        }
        return;
	}
	
	// ============================================================================================ //
	// ---------------------------------------- CRON jobs ---------------------------------------- //
	public function batchPostFailsafeInvoice(){
		if(!Mage::getStoreConfig ( 'speedtax/failsafe/is_auto_post_failsafe_invoice' )){
			return;
		}
		$sptxProcessor = Mage::helper('speedtax/processor');
		$invoicCollection = Mage::getModel('sales/order_invoice')->getCollection();
		$invoicCollection->addAttributeToFilter('speedtax_invoice_status', Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_FAILSAFE);
		$invoicCollection->setPageSize(Harapartners_SpeedTax_Helper_Data::DEFAULT_MAGENTO_COLLECTION_PAGE_SIZE);
		$currentPage = 1;
		$totalNumPages = $invoicCollection->getLastPageNumber();
		do {
		    $invoicCollection->setCurPage($currentPage);
		    foreach ($invoicCollection as $invoice) {
	            try{
	            	$responseResult = $sptxProcessor->postOrderInvoice($invoice);
		            if(isset($responseResult->totalTax->decimalValue)){
						$baseSpeedTaxTaxAmount = $responseResult->totalTax->decimalValue;
						$invoice->setData('base_speedtax_tax_amount', $baseSpeedTaxTaxAmount);
						$invoice->setData('speedtax_tax_amount', Mage::app()->getStore()->convertPrice($baseSpeedTaxTaxAmount, false));
						$invoice->save();
					}
	            }catch (Exception $ex){
	            	//Suppress errors in cronjob
	            }
	            unset($invoice);
	        }
		    // Pagination Loop Control
		    $currentPage ++;
		    $invoicCollection->clear();
		} while ($currentPage <= $totalNumPages);
	}
	
}