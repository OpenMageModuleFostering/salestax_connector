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
$installer = $this;
$installer->startSetup();

// ================= Calculation Table ================= //
$calculationTable = $installer->getConnection()
    ->newTable($installer->getTable('speedtax/failsafe_calculation_rate'))
    ->addColumn('rate_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        	'identity'  => true,
        	'unsigned'  => true,
        	'nullable'  => false,
        	'primary'   => true,
    ), 'Auto Increment Calculation Rate ID')
	->addColumn('country_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
    		'nullable'  => true,
	), 'Country ID')
    ->addColumn('region_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
    		'nullable'  => true,
    ), 'Region ID')
    ->addColumn('postcode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
    		'nullable'  => true,
	), 'Postcode')
	->addColumn('customer_class_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    		'unsigned' 	=> true,
    		'nullable'  => true,
    ), 'Customer Class ID')
    ->addColumn('product_class_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
    		'unsigned' 	=> true,
    		'nullable'  => true,
    ), 'Product Class ID')
    ->addColumn('tax_rate', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
    		'nullable'  => true,
    ), 'Tax Rate')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    		'default'   => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
    ), 'Created At')  
	->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
			'nullable'  => true,
	), 'Updated At')
	->addIndex(
			$installer->getIdxName('speedtax/failsafe_calculation_rate', array('country_id')),
	        array('country_id'),
	        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
	)
	->addIndex(
			$installer->getIdxName('speedtax/failsafe_calculation_rate', array('region_id')),
	        array('region_id'),
	        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
	)
	->addIndex(
			$installer->getIdxName('speedtax/failsafe_calculation_rate', array('postcode')),
	        array('postcode'),
	        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
	)
	->addIndex(
			$installer->getIdxName('speedtax/failsafe_calculation_rate', array('customer_class_id')),
	        array('customer_class_id'),
	        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
	)
	->addIndex(
			$installer->getIdxName('speedtax/failsafe_calculation_rate', array('product_class_id')),
	        array('product_class_id'),
	        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
	)
    ->setComment('SpeedTax Failsafe Calculation Rate');
$installer->getConnection()->createTable( $calculationTable );

$installer->getConnection()->addColumn(
	$installer->getTable('sales/order'),
    "is_speedtax_failsafe_calculation",
	"SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Is SpeedTax Failsafe Calculation'"
);

$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "is_speedtax_failsafe_calculation",
	"SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Is SpeedTax Failsafe Calculation'"
);
$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "base_speedtax_tax_amount",
	"DECIMAL(12,4) DEFAULT NULL COMMENT 'Base SpeedTax Tax Amount'"
);
$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "speedtax_tax_amount",
	"DECIMAL(12,4) DEFAULT NULL COMMENT 'SpeedTax Tax Amount'"
);

$installer->endSetup();