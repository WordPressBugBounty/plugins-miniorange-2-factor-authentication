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

global $mo2f_mo_wpns_utility, $mo2f_dir_name, $mo2f_wpns_db_queries;
$mo2f_mo_wpns_handler   = new MoWpnsHandler();
$mo2f_logintranscations = $mo2f_wpns_db_queries->mo2f_get_login_transaction_report();
if ( get_site_option( 'mo2f_network_transactions_data' ) ) {
	$mo2f_old_transcations  = $mo2f_wpns_db_queries->mo2f_get_old_login_transaction_report();
	$mo2f_logintranscations = array_merge( $mo2f_logintranscations, $mo2f_old_transcations );
}
$mo2f_common_helper = new Mo2f_Common_Helper();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'loginreport.php';
