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

class Harapartners_SpeedTax_Block_Adminhtml_Invoice_All_Index extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		parent::__construct ();
		$this->_blockGroup = 'speedtax';
		$this->_controller = 'adminhtml_invoice_all_index';
		$this->_headerText = Mage::helper ( 'speedtax' )->__ ( 'SalesTax Invoices' );
		$this->_removeButton('add');
	}

	public function getGridHtml() {
        return $this->getHeaderInfoHtml() . parent::getGridHtml();
    }
    
    public function getHeaderInfoHtml(){
    	$headerInfoHtml = <<< INFO_HTML
<div class="grid-top-info" style="background: none repeat scroll 0 0 #E7EFEF; border: 1px solid #CDDDDD; padding: 10px 20px 10px 20px; margin-bottom: 20px;"> 
	<p>By default Magento invoices will be posted to SalesTax when paid; and most orders should be in the <b>Posted</b> status.</p>
	<p>The <b>Failsafe</b> status is for invoices that are paid but cannot be posted to SalesTax. These invoices can be processed in batch by either 1) system cronjobs or 2) the <b><i>Post To SalesTax</i></b> action.</p>
	<p>The <b>Pending</b> status is for invoices that are paid, and by default not to be posted to SalesTax. These invoices are probably from historical orders before the SalesTax integration.</p>
</div>
INFO_HTML;
		return $headerInfoHtml;
    }
	
}