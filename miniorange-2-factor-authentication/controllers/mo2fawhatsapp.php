<?php
/**
 * SMS form.
 *
 * @package mowhatsapp
 */

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$access_token              = get_site_option( 'mo2f_wa_access_token' );
$phone_number_id           = get_site_option( 'mo2f_wa_phone_id' );
$template_name             = get_site_option( 'mo2f_wa_template_id' );
$language                  = get_site_option( 'mo2f_wa_template_language' );
$otp_length                = get_site_option( 'mo2f_wa_otp_length' );
$mo_otp_length             = $otp_length ? $otp_length : 5;
$manual_report_clear_nonce = wp_create_nonce( 'mo2f-advance-settings-nonce' );

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'mo2fawhatsapp.php';
