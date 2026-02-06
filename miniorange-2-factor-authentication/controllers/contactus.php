<?php
/**
 * Used to send the support query if user face any issue.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $mo2f_dir_name;

$mo2f_current_user_info = wp_get_current_user();
$mo2f_email             = get_site_option( 'mo2f_email' );
$mo2f_phone             = get_site_option( 'mo_wpns_admin_phone' );

if ( empty( $mo2f_email ) ) {
	$mo2f_email = $mo2f_current_user_info->user_email;
}
$mo2f_support_form_nonce = wp_create_nonce( 'mo2f-support-form-nonce' );
$mo2f_query_submitted    = get_transient( 'mo2f_query_sent' ) ? 'true' : 'false';
require dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'contactus.php';
