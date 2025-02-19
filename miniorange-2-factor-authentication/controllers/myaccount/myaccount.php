<?php
/**
 * Description: File contains functions to register, verify and save the information for customer account.
 *
 * @package miniorange-2-factor-authentication/controllers.
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$user                             = wp_get_current_user();
$mo2f_current_registration_status = get_site_option( 'mo_2factor_admin_registration_status' );
$email                            = get_site_option( 'mo2f_email' );
$key                              = get_site_option( 'mo2f_customerKey' );
$api                              = get_site_option( 'mo2f_api_key' );
$token                            = get_site_option( 'mo2f_customer_token' );
$email_transactions               = get_site_option( 'mo2fa_lk' ) ? 'Unlimited' : MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
$email_transactions               = $email_transactions ? $email_transactions : 0;
$sms_transactions                 = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ? get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) : 0;
if ( ! $key ) {
	$skeleton      = array(
		'##crossbutton##'    => '',
		'##miniorangelogo##' => '',
		'##pagetitle##'      => '<h3>' . __( 'Login/Register with miniOrange', 'miniorange-2-factor-authentication' ) . '</h3>',
	);
	$common_helper = new Mo2f_Common_Helper();
	$html          = $common_helper->mo2f_get_miniorange_user_registration_prompt( '', null, null, 'myaccount', $skeleton );
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'myaccount' . DIRECTORY_SEPARATOR . 'login.php';
} else {
	do_action( 'mo2f_get_license_varification_screen' );
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'myaccount' . DIRECTORY_SEPARATOR . 'account.php';
}












