<?php
/**
 * Description: File contains functions to register, verify and save the information for customer account.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

// Both onprem and cloud code.

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$user                             = wp_get_current_user();
$mo2f_current_registration_status = get_option( 'mo_2factor_admin_registration_status' );
$email                            = get_option( 'mo2f_email' );
$key                              = get_option( 'mo2f_customerKey' );
$api                              = get_option( 'mo2f_api_key' );
$token                            = get_option( 'mo2f_customer_token' );
$email_transactions               = MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
$email_transactions               = $email_transactions ? $email_transactions : 0;
$sms_transactions                 = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ? get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) : 0;
require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'my-account.php';













