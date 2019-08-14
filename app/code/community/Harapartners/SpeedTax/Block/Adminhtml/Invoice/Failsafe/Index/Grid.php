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

class Harapartners_SpeedTax_Block_Adminhtml_Invoice_Failsafe_Index_Grid extends Harapartners_SpeedTax_Block_Adminhtml_Invoice_All_Index_Grid {

    public function __construct(){
        parent::__construct();
        $this->setId('speedtaxInvoiceFailsafeGrid');
    }
    
    public function setCollection($collection){
    	$collection->addAttributeToFilter('speedtax_invoice_status', Harapartners_SpeedTax_Helper_Processor::SPEEDTAX_INVOICE_STATUS_FAILSAFE);
    	return parent::setCollection($collection);
    }
    
	protected function _prepareColumns(){
        parent::_prepareColumns();
        $this->removeColumn('speedtax_invoice_status');
        return $this;
    }

	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('invoice_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('post_selected', array(
            'label'		=> Mage::helper('sales')->__('Post To SalesTax'),
            'url'  		=> $this->getUrl('*/*/massPostToSpeedtax'),
        ));

        return $this;
    }

    //No row editing
    public function getRowUrl($row){
        return false;
    }
    
}