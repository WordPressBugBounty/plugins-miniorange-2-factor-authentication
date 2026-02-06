<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/controllers/2faconfigurations
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
$mo2f_user                         = wp_get_current_user();
$mo2f_user_id                      = $mo2f_user->ID;
$mo2f_selected_method              = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $mo2f_user_id );
$mo2f_is_customer_admin_registered = get_site_option( 'mo_2factor_admin_registration_status' );

update_site_option( 'mo2f_show_sms_transaction_message', MoWpnsConstants::OTP_OVER_SMS === $mo2f_selected_method );

$mo2f_can_display_admin_features = current_user_can( 'manage_options' );
$mo2f_two_factor_methods_details = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard       = array_keys( $mo2f_two_factor_methods_details );// get free plan methods.

if ( ! $mo2f_can_display_admin_features && ! Miniorange_Authentication::mo2f_is_customer_registered() ) { // hiding cloud methods for users if admin is not registered.
	$mo2f_methods_on_dashboard = array_filter(
		$mo2f_methods_on_dashboard,
		function ( $method ) {
			return MoWpnsConstants::OTP_OVER_SMS !== $method;
		}
	);
}
if ( MO2F_IS_ONPREM ) {
	$mo2f_selected_method = ! empty( $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $mo2f_user_id ) ) ? $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $mo2f_user_id ) : 'NONE';// to do: shift the implementation above and avoid redefining same var.
}
$mo2f_common_helper = new Mo2f_Common_Helper();
$mo2f_common_helper->mo2f_echo_js_css_files();

require dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'myaccount' . DIRECTORY_SEPARATOR . 'setupyour2fa.php';
