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

$current_user_info = wp_get_current_user();
$email             = get_option( 'mo2f_email' );
$phone             = get_option( 'mo_wpns_admin_phone' );

if ( empty( $email ) ) {
	$email = $current_user_info->user_email;
}
$support_form_nonce = wp_create_nonce( 'mo2f-support-form-nonce' );
$query_submitted    = get_transient( 'mo2f_query_sent' ) ? 'true' : 'false';
require dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'contactus.php';
