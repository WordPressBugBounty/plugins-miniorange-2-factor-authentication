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

global $mo_wpns_utility,$mo2f_dir_name;
$mo_wpns_handler   = new MoWpnsHandler();
$logintranscations = $mo_wpns_handler->get_login_transaction_report();
$common_helper     = new Mo2f_Common_Helper();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'loginreport.php';
