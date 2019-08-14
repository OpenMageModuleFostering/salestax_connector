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

class Harapartners_SpeedTax_Block_Adminhtml_Invoice_All_Index_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct(){
        parent::__construct();
        $this->setId('speedtaxInvoiceAllGrid');
    }
    
    protected function _prepareCollection(){
        $collection = Mage::getModel('sales/order_invoice')->getCollection();
		$this->setCollection($collection);
		$this->setDefaultSort('created_at');
		$this->setDefaultDir('desc');
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('speedtax')->__('Magento Invoice #'),
            'index'     => 'increment_id',
            'type'      => 'text',
        	'align'     => 'right',
        ));
        
        $this->addColumn('action',
            array(
                'header'    => Mage::helper('sales')->__('Invoice Details'),
                'width'     => '100px',
                'type'      => 'action',
            	'align'     => 'right',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => array('base'=>'adminhtml/sales_invoice/view'),
                        'field'   => 'invoice_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
        ));
        
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('speedtax')->__('Magento Invoice Date'),
            'index'     => 'created_at',
            'type'      => 'datetime',
        	'align'     => 'right',
        ));
        
        $this->addColumn('speedtax_invoice_number', array(
            'header'    => Mage::helper('speedtax')->__('SalesTax Invoice #'),
            'index'     => 'speedtax_invoice_number',
            'type'      => 'text',
        	'align'     => 'right',
        ));
                
        $this->addColumn('speedtax_transaction_id', array(
            'header'    => Mage::helper('speedtax')->__('SalesTax Transaction ID'),
            'index'     => 'speedtax_transaction_id',
            'type'      => 'text',
        	'align'     => 'right',
        ));
        
        $this->addColumn('tax_amount', array(
            'header'    => Mage::helper('speedtax')->__('Magento Tax Amount'),
            'index'     => 'tax_amount',
            'type'      => 'currency',
            'align'     => 'right',
            'currency'  => 'order_currency_code',
        ));
        
        $this->addColumn('speedtax_tax_amount', array(
            'header'    => Mage::helper('speedtax')->__('SalesTax Tax Amount'),
            'index'     => 'speedtax_tax_amount',
            'type'      => 'currency',
            'align'     => 'right',
            'currency'  => 'order_currency_code',
        ));
        
        $this->addColumn('speedtax_invoice_status', array(
            'header'    => Mage::helper('speedtax')->__('SalesTax Invoice Status'),
        	'width'		=> '100px',
            'index'     => 'speedtax_invoice_status',
            'type'      => 'options',
        	'align'     => 'right',
            'options'   => Mage::helper('speedtax')->getSpeedtaxInvoiceStatusValues(),
        ));
        
        return parent::_prepareColumns();
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