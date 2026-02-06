<?php
/**
 * This file is controller for views/reports/remembereddevices.php.
 *
 * @package miniorange-2-factor-authentication/reports/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mo2f_remembered_devices = apply_filters( 'mo2f_enterprise_plan_settings_filter', array(), 'mo2f_get_all_users_rba_details', array() );
require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'remembereddevices.php';
