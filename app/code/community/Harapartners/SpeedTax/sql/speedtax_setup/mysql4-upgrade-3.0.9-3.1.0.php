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

$speedTaxTransactionIdColumnName = 'speedtax_transaction_id';
$speedtaxInvoiceNumberColumnName = 'speedtax_invoice_number';
$speedtaxInvoiceStatusColumnName = 'speedtax_invoice_status';
$connectionConfig = $installer->getConnection()->getConfig();

// ================ Invoice ================ //
$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedtaxInvoiceNumberColumnName'
		AND table_name='{$this->getTable('sales/invoice')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	DROP COLUMN `$speedtaxInvoiceNumberColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	ADD COLUMN `$speedtaxInvoiceNumberColumnName` varchar(255);
    ");
	
$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedtaxInvoiceStatusColumnName'
		AND table_name='{$this->getTable('sales/invoice')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	DROP COLUMN `$speedtaxInvoiceStatusColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	ADD COLUMN `$speedtaxInvoiceStatusColumnName` SMALLINT(5) DEFAULT 0;
    ");
	
$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedTaxTransactionIdColumnName'
		AND table_name='{$this->getTable('sales/invoice')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	DROP COLUMN `$speedTaxTransactionIdColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/invoice')}` 
	ADD COLUMN `$speedTaxTransactionIdColumnName` varchar(255);
    ");

	
// ================ Credit Memo ================ //
$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedtaxInvoiceNumberColumnName'
		AND table_name='{$this->getTable('sales/creditmemo')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	DROP COLUMN `$speedtaxInvoiceNumberColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	ADD COLUMN `$speedtaxInvoiceNumberColumnName` varchar(255);
    ");
	
$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedtaxInvoiceStatusColumnName'
		AND table_name='{$this->getTable('sales/creditmemo')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	DROP COLUMN `$speedtaxInvoiceStatusColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	ADD COLUMN `$speedtaxInvoiceStatusColumnName` SMALLINT(5) DEFAULT 0;
    ");

$testQuery = "
SELECT * FROM information_schema.COLUMNS
	WHERE column_name='$speedTaxTransactionIdColumnName'
		AND table_name='{$this->getTable('sales/creditmemo')}'
		AND table_schema='{$connectionConfig['dbname']}'
";
if(!!$installer->getConnection()->fetchAll($testQuery)){
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	DROP COLUMN `$speedTaxTransactionIdColumnName`;
    ");
}
$installer->run("
ALTER TABLE `{$this->getTable('sales/creditmemo')}` 
	ADD COLUMN `$speedTaxTransactionIdColumnName` varchar(255);
    ");

$installer->endSetup();
