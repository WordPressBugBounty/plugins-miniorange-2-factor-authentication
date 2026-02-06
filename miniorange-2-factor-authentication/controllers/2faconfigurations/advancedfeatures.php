<?php
/**
 * Description: This file is used to show the user details.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$mo2f_configurations = array(
	'mo2f_remember_ip_feature'   => 0,
	'mo2f_give_rem_ip_choice'    => 1,
	'mo2f_enable_ip_list'        => 1,
	'mo2f_enable_ip_range'       => 0,
	'mo2f_2fa_whitelist_ip_list' => '',
	'mo2f_rem_ip_range'          => array( array() ),

);
$mo2f_configurations = (array) get_site_option( 'mo2f_remember_ip_configurations', $mo2f_configurations );
$mo2f_rem_ip_ranges  = isset( $mo2f_configurations['mo2f_rem_ip_range'] ) ? $mo2f_configurations['mo2f_rem_ip_range'] : array( array() );
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'rememberdevice.php';
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'rememberip.php';
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'sessionmanagement.php';
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'advancedfeatures' . DIRECTORY_SEPARATOR . 'passwordlesslogin.php';
