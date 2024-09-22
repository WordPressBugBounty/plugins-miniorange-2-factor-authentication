<?php
/**
 * This file is controller for twofactor/loginsettings/controllers/login-settings.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofactorauthentication
 */

use TwoFA\Helper\MoWpnsConstants;
// Needed in both.
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
$two_factor_methods_details = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard  = array_keys( $two_factor_methods_details );
$mo2f_methods_on_dashboard  = array_filter(
	$mo2f_methods_on_dashboard,
	function( $method ) {
		return MoWpnsConstants::OTP_OVER_WHATSAPP !== $method;
	}
);
$mo2f_method_names          = MoWpnsConstants::$mo2f_cap_to_small;
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofactorauthentication' . DIRECTORY_SEPARATOR . 'loginsettings.php';

