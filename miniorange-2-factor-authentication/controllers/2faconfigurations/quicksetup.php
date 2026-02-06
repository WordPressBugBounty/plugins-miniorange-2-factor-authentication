<?php
/**
 * This file is controller for twofactor/quicksetup/controllers/login-settings.php.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

use TwoFA\Helper\MoWpnsConstants;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Including the file for frontend.
 */
global $mo2f_addon_dir;

global $wp_roles, $mo2f_onprem_cloud_obj;
$mo2f_lv_needed = apply_filters( 'mo2f_is_lv_needed', false );
if ( is_multisite() ) {
	$mo2f_first_role      = array( 'superadmin' => 'Superadmin' );
	$wp_roles->role_names = array_merge( $mo2f_first_role, $wp_roles->role_names );
}
$mo2f_two_factor_methods_details = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard       = array_keys( $mo2f_two_factor_methods_details );
$mo2f_method_names               = MoWpnsConstants::$mo2f_cap_to_small;
$mo2f_method_icons               = MoWpnsConstants::$mo2f_method_icons;
$mo2f_method_hints               = MoWpnsConstants::$mo2f_method_hints;
$mo2fa_enable_method_selection   = get_site_option( 'mo2f_select_methods_for_users', 1 );
$mo2f_selected_methods           = (array) get_site_option( 'mo2f_auth_methods_for_users', array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OTP_OVER_TELEGRAM, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_WHATSAPP, MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::SECURITY_QUESTIONS ) );
$mo2f_selected_roles             = (array) get_site_option( 'mo2f_auth_methods_roles' );
$mo2f_selected_type              = get_site_option( 'mo2f_all_users_method', 1 );
$mo2f_pages                      = get_pages();
$mo2f_posts                      = get_posts();
$mo2f_pages                      = array_merge( $mo2f_pages, $mo2f_posts );
$mo2f_counter                    = 0;
$mo2f_select_all_checked         = true;
$mo2f_page_protection_settings   = get_site_option( 'mo2f_page_protection_addon_settings' );
$mo2f_settings_status            = isset( $mo2f_page_protection_settings['enable_settings'] ) ? $mo2f_page_protection_settings['enable_settings'] : 0;
$mo2f_enabled_pages              = isset( $mo2f_page_protection_settings['enabled_pages'] ) ? $mo2f_page_protection_settings['enabled_pages'] : array();
$mo2f_session_time               = isset( $mo2f_page_protection_settings['session_time'] ) ? $mo2f_page_protection_settings['session_time'] : 24;
$mo2f_pp_addon_installed         = isset( $mo2f_page_protection_settings['addon_installed'] ) ? true : false;
$mo2f_page_protection_addon_css  = $mo2f_pp_addon_installed ? '' : 'mo2f-disable-div';
?>
<div>
	<?php
	require dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'quicksetup' . DIRECTORY_SEPARATOR . 'quicksetup.php';
	require dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'quicksetup' . DIRECTORY_SEPARATOR . 'pageprotectionaddon.php';
	?>
