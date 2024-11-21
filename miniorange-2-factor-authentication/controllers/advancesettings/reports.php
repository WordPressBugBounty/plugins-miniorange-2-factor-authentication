<?php
/**
 * This file is controller for views/twofa/two-fa-rba.php.
 *
 * @package miniorange-2-factor-authentication/reports/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsHandler;

global $mo_wpns_utility,$mo2f_dir_name;
$mo_wpns_handler   = new MoWpnsHandler();
$logintranscations = $mo_wpns_handler->get_login_transaction_report();
$errortranscations = $mo_wpns_handler->get_error_transaction_report();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'advancesettings' . DIRECTORY_SEPARATOR . 'reports.php';
