<?php
/**
 * This file is controller for views/reports/loginreports.php.
 *
 * @package miniorange-2-factor-authentication/reports/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\Mo2f_Common_Helper;

global $mo_wpns_utility,$mo2f_dir_name,$wpns_db_queries;
$mo_wpns_handler   = new MoWpnsHandler();
$logintranscations = $wpns_db_queries->mo2f_get_login_transaction_report();
if ( get_site_option( 'mo2f_network_transactions_data' ) ){
	$old_transcations  = $wpns_db_queries->mo2f_get_old_login_transaction_report();
	$logintranscations = array_merge($logintranscations,	$old_transcations);
}
$common_helper     = new Mo2f_Common_Helper();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'loginreport.php';
