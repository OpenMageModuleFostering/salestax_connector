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

class Harapartners_SpeedTax_Adminhtml_InvoiceController extends Mage_Adminhtml_Controller_Action {
	
	public function indexAction() {
		$this->loadLayout ();
		$this->_setActiveMenu ( 'speedtax/invoice' );
		$this->_addContent ( $this->getLayout ()->createBlock ( 'speedtax/adminhtml_invoice_all_index' ) );
		$this->renderLayout ();
	}
	
	public function failsafeIndexAction() {
		$this->loadLayout ();
		$this->_setActiveMenu ( 'speedtax/invoice' );
		$this->_addContent ( $this->getLayout ()->createBlock ( 'speedtax/adminhtml_invoice_failsafe_index' ) );
		$this->renderLayout ();
	}
	
	public function rateVerificationIndexAction() {
		$this->loadLayout ();
		$this->_setActiveMenu ( 'speedtax/invoice' );
		$this->_addContent ( $this->getLayout ()->createBlock ( 'speedtax/adminhtml_invoice_rateverification_index' ) );
		$this->renderLayout ();
	}
	
	public function massPostToSpeedtaxAction(){
		$invoiceIds = $this->getRequest()->getPost('invoice_ids', array());
		$sptxProcessor = Mage::helper('speedtax/processor');
		$successInvoiceIds = array();
		$noticeInvoiceIds = array();
		$errorInvoiceIds = array();
		$allowedInovicePostStatusArray = array(
				Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_PENDING,
				Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_FAILSAFE
		);
        foreach ($invoiceIds as $invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            if(!in_array($invoice->getData('speedtax_invoice_status'), $allowedInovicePostStatusArray)){
            	$noticeInvoiceIds[] = $invoice->getData('increment_id');
            	continue;
            }
            try{
            	$responseResult = $sptxProcessor->postOrderInvoice($invoice);
	            if(isset($responseResult->totalTax->decimalValue)){
					$baseSpeedTaxTaxAmount = $responseResult->totalTax->decimalValue;
					$invoice->setData('base_speedtax_tax_amount', $baseSpeedTaxTaxAmount);
					$invoice->setData('speedtax_tax_amount', Mage::app()->getStore()->convertPrice($baseSpeedTaxTaxAmount, false));
					$invoice->save();
				}
				$successInvoiceIds[] = $invoice->getData('increment_id');
            }catch (Exception $ex){
            	$errorInvoiceIds[] = $invoice->getData('increment_id');
            }
            unset($invoice);
        }
        
        if(count($successInvoiceIds)){
        	Mage::getSingleton('adminhtml/session')->addSuccess("Invoice posted to SalesTax. Invoice #" . implode(", #", $successInvoiceIds));
        }
		if(count($noticeInvoiceIds)){
        	Mage::getSingleton('adminhtml/session')->addNotice("Only inovices with Failsafe or Pending status can be posted. Invoice #" . implode(", #", $noticeInvoiceIds));
        }
		if(count($errorInvoiceIds)){
        	Mage::getSingleton('adminhtml/session')->addError("Cannot post invoices to SalesTax. Invoice: #" . implode(", #", $errorInvoiceIds));
        }
            
		$this->_redirectReferer();
	}
	
}
