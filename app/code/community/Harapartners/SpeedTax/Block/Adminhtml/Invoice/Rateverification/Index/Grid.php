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

class Harapartners_SpeedTax_Block_Adminhtml_Invoice_Rateverification_Index_Grid extends Harapartners_SpeedTax_Block_Adminhtml_Invoice_All_Index_Grid {

    public function __construct(){
        parent::__construct();
        $this->setId('speedtaxInvoiceRateVerificationGrid');
    }
    
    public function setCollection($collection){
    	$collection->addAttributeToFilter('is_speedtax_failsafe_calculation', 1);
    	$collection->getSelect()->columns(array(
    			'tax_amount_difference' => new Zend_Db_Expr("IFNULL(`main_table`.`speedtax_tax_amount`, 0) - IFNULL(`main_table`.`tax_amount`, 0)"),
    			'has_tax_amount_difference' => new Zend_Db_Expr("IF(IFNULL(`main_table`.`speedtax_tax_amount`, 0) - IFNULL(`main_table`.`tax_amount`, 0) = 0, 0, 1)"),
    	));
    	
    	//Re-wrap the collection to support generic form sort and filter
    	$outerCollection = Mage::getModel('sales/order_invoice')->getCollection();
        $outerCollection->getSelect()->reset();
        $outerCollection->getSelect()->from(array('main_table' => $collection->getSelect()));
    	return parent::setCollection($outerCollection);
    }
    
	protected function _prepareColumns(){
        parent::_prepareColumns();
        $this->addColumn('tax_amount_difference', array(
            'header'    => Mage::helper('speedtax')->__('Tax Difference (SalesTax - Magento)'),
            'index'     => 'tax_amount_difference',
            'type'      => 'currency',
            'align'     => 'right',
            'currency'  => 'order_currency_code',
        ));
        $this->addColumn('has_tax_amount_difference', array(
            'header'    => Mage::helper('speedtax')->__('Has Tax Difference'),
            'index'     => 'has_tax_amount_difference',
            'type'      => 'options',
        	'align'     => 'right',
            'options'   => Mage::getSingleton('adminhtml/system_config_source_yesno')->toArray(),
        ));
        return $this;
    }

	protected function _prepareMassaction(){
          return $this;
    }

    //No row editing
    public function getRowUrl($row){
        return false;
    }
    
}