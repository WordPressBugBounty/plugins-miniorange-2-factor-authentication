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
$mo2f_user                        = wp_get_current_user();
$mo2f_current_registration_status = get_site_option( 'mo_2factor_admin_registration_status' );
$mo2f_email                       = get_site_option( 'mo2f_email' );
$mo2f_key                         = get_site_option( 'mo2f_customerKey' );
$mo2f_api                         = get_site_option( 'mo2f_api_key' );
$mo2f_token                       = get_site_option( 'mo2f_customer_token' );
if ( ! $mo2f_key ) {
	$mo2f_skeleton      = array(
		'##crossbutton##'    => '',
		'##miniorangelogo##' => '',
		'##pagetitle##'      => '<h3>' . __( 'Login/Register with miniOrange', 'miniorange-2-factor-authentication' ) . '</h3>',
	);
	$mo2f_common_helper = new Mo2f_Common_Helper();
	$mo2f_html          = $mo2f_common_helper->mo2f_get_miniorange_user_registration_prompt( '', null, null, 'myaccount', $mo2f_skeleton );
	require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'myaccount' . DIRECTORY_SEPARATOR . 'login.php';
} else {
	do_action( 'mo2f_get_license_varification_screen' );
	require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'myaccount' . DIRECTORY_SEPARATOR . 'account.php';
}
