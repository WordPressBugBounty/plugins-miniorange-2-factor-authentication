<?php
/**
 * Description: This file is to show the additional login settings.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $wp_roles;
$mo2f_enable_custom_redirect = get_site_option( 'mo2f_enable_custom_redirect' );
$mo2f_custom_login_urls      = (array) get_option( 'mo2f_custom_login_urls', array( wp_get_current_user()->roles[0] => home_url() ) );
$mo2f_enable_debug_log       = get_site_option( 'mo2f_enable_debug_log' );
require_once $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'settings.php';
