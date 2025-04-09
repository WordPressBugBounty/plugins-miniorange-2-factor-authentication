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

global $wp_roles,$mo2f_onprem_cloud_obj;
if ( is_multisite() ) {
	$first_role           = array( 'superadmin' => 'Superadmin' );
	$wp_roles->role_names = array_merge( $first_role, $wp_roles->role_names );
}
$two_factor_methods_details    = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard     = array_keys( $two_factor_methods_details );
$mo2f_method_names             = MoWpnsConstants::$mo2f_cap_to_small;
$mo2fa_enable_method_selection = get_site_option( 'mo2f_select_methods_for_users', 1 );
$selected_methods              = (array) get_site_option( 'mo2f_auth_methods_for_users', array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OTP_OVER_TELEGRAM, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_WHATSAPP, MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::SECURITY_QUESTIONS ) );
$selected_roles                = (array) get_site_option( 'mo2f_auth_methods_roles' );
$selected_type                 = get_site_option( 'mo2f_all_users_method', 1 );
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'quicksetup.php';

