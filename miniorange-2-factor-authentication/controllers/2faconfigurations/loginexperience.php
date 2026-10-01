<?php
/**
 * This file is controller for twofactor/quicksetup/views/2faconfigurations/loginexperience.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $wp_roles, $mo2f_dir_name;
$mo2f_enable_custom_redirect  = get_site_option( 'mo2f_enable_custom_redirect' );
$mo2f_custom_login_urls       = (array) get_option( 'mo2f_custom_login_urls', array( wp_get_current_user()->roles[0] => home_url() ) );
$mo2f_enable_magic_link_login = get_site_option( 'mo2f_enable_magic_link_login' );
$mo2f_magic_link_login_mode   = get_site_option( 'mo2f_magic_link_login_mode', 'optional' );

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'loginexperience.php';
