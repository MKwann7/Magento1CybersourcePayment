<?php

$installer = $this;
$installer->startSetup();
$strInstallationScript = "ALTER TABLE `".$installer->getTable('sales/quote_payment')."` 
ADD `datix_cybersource_credit_card_type` VARCHAR( 50 ) NOT NULL,
ADD `datix_cybersource_credit_card_number` VARCHAR( 255 ) NOT NULL,
ADD `datix_cybersource_expiration_date_month` VARCHAR( 2 ) NOT NULL,
ADD `datix_cybersource_expiration_date_year` VARCHAR( 4 ) NOT NULL,
ADD `datix_cybersource_card_verification_number` VARCHAR( 5 ) NOT NULL;";
Mage::log($strInstallationScript,null,"mwz_datix_payment_tracing.log");
$installer->run($strInstallationScript);

$strInstallationScript = "ALTER TABLE `".$installer->getTable('sales/order_payment')."` 
ADD `datix_cybersource_credit_card_type` VARCHAR( 50 ) NOT NULL,
ADD `datix_cybersource_credit_card_number` VARCHAR( 255 ) NOT NULL,
ADD `datix_cybersource_expiration_date_month` VARCHAR( 2 ) NOT NULL,
ADD `datix_cybersource_expiration_date_year` VARCHAR( 4 ) NOT NULL,
ADD `datix_cybersource_card_verification_number` VARCHAR( 5 ) NOT NULL;";
Mage::log($strInstallationScript,null,"mwz_datix_payment_tracing.log");
$installer->run($strInstallationScript);
$installer->endSetup();

// To get these from the database directly:
// SELECT * FROM  `sales_flat_quote_payment` WHERE method =  'datixcybersourcesoap'
