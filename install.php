<?php

if ( ! defined( 'ABSPATH' ) ) exit;

$create_sms_send = ( "CREATE TABLE IF NOT EXISTS whatsiplus_wc_send_sms_outbox(
	ID int(10) NOT NULL auto_increment,
	date DATETIME DEFAULT CURRENT_TIMESTAMP,
	sender VARCHAR(20) NOT NULL,
	message TEXT NOT NULL,
	recipient TEXT NOT NULL,
    status VARCHAR(255) NOT NULL,
	PRIMARY KEY(ID)) CHARSET=utf8
" );