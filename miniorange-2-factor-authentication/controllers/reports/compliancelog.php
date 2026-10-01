<?php
/**
 * This file is controller for views/reports/compliancelog.php.
 *
 * @package miniorange-2-factor-authentication/reports/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TwoFA\Helper\Mo2f_Common_Helper;

global $mo2f_dir_name, $mo2f_wpns_db_queries;

$mo2f_compliance_events = $mo2f_wpns_db_queries->mo2f_get_compliance_log_report( 30 );
$mo2f_common_helper      = new Mo2f_Common_Helper();
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'compliancelog.php';
