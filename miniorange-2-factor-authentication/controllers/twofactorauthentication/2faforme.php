<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/controllers/twofactorauthentication
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Miniorange_Authentication;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
$user                         = wp_get_current_user();
$user_id                      = $user->ID;
$selected_method              = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id );
$is_customer_admin_registered = get_site_option( 'mo_2factor_admin_registration_status' );

update_site_option( 'mo2f_show_sms_transaction_message', MoWpnsConstants::OTP_OVER_SMS === $selected_method );

$can_display_admin_features = current_user_can( 'manage_options' );
$two_factor_methods_details = $mo2f_onprem_cloud_obj->mo2f_plan_methods();
$mo2f_methods_on_dashboard  = array_keys( $two_factor_methods_details );// get free plan methods.

if ( ! $can_display_admin_features && ! Miniorange_Authentication::mo2f_is_customer_registered() ) { // hiding cloud methods for users if admin is not registered.
	$mo2f_methods_on_dashboard = array_filter(
		$mo2f_methods_on_dashboard,
		function( $method ) {
			return MoWpnsConstants::OTP_OVER_SMS !== $method;
		}
	);
}
if ( MO2F_IS_ONPREM ) {
	$selected_method = ! empty( $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id ) ) ? $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user_id ) : 'NONE';// to do: shift the implementation above and avoid redefining same var.
}
$common_helper = new Mo2f_Common_Helper();
$common_helper->mo2f_echo_js_css_files();

require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofactorauthentication' . DIRECTORY_SEPARATOR . '2faforme.php';

