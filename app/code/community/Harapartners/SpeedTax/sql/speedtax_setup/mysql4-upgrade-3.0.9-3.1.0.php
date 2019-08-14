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

// ================ Invoice ================ //
$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "speedtax_invoice_number",
	"VARCHAR(255) COMMENT 'SpeedTax Invoice Number'"
);	
$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "speedtax_invoice_status",
	"SMALLINT(5) DEFAULT '0' COMMENT 'SpeedTax Invoice Status'"
);
$installer->getConnection()->addColumn(
	$installer->getTable('sales/invoice'),
    "speedtax_transaction_id",
	"VARCHAR(255) COMMENT 'SpeedTax Transaction ID'"
);

// ================ Credit Memo ================ //
$installer->getConnection()->addColumn(
	$installer->getTable('sales/creditmemo'),
    "speedtax_invoice_number",
	"VARCHAR(255) COMMENT 'SpeedTax Invoice Number'"
);	
$installer->getConnection()->addColumn(
	$installer->getTable('sales/creditmemo'),
    "speedtax_invoice_status",
	"SMALLINT(5) DEFAULT '0' COMMENT 'SpeedTax Invoice Status'"
);
$installer->getConnection()->addColumn(
	$installer->getTable('sales/creditmemo'),
    "speedtax_transaction_id",
	"VARCHAR(255) COMMENT 'SpeedTax Transaction ID'"
);

$installer->endSetup();