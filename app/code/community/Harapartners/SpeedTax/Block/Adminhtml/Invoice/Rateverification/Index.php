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

class Harapartners_SpeedTax_Block_Adminhtml_Invoice_Rateverification_Index extends Harapartners_SpeedTax_Block_Adminhtml_Invoice_All_Index {

	public function __construct() {
		parent::__construct ();
		$this->_blockGroup = 'speedtax';
		$this->_controller = 'adminhtml_invoice_rateverification_index';
		$this->_headerText = Mage::helper ( 'speedtax' )->__ ( 'Verify Invoices with Failsafe Tax Rates' );
		$this->_removeButton('add');
	}
	
    public function getHeaderInfoHtml(){
    	$headerInfoHtml = <<< INFO_HTML
<div class="grid-top-info" style="background: none repeat scroll 0 0 #E7EFEF; border: 1px solid #CDDDDD; padding: 10px 20px 10px 20px; margin-bottom: 20px;"> 
	<p>Invoices in this panel are paid using failsafe rate. For some invoices failsafe rate may be different from the SalesTax rate. In such cases, please reconciliate the difference in SalesTax management panel if necessary.</p>
</div>
INFO_HTML;
		return $headerInfoHtml;
    }
	
}