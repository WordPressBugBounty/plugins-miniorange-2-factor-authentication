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
$mo2f_access_token              = get_site_option( 'mo2f_wa_access_token' );
$mo2f_phone_number_id           = get_site_option( 'mo2f_wa_phone_id' );
$mo2f_template_name             = get_site_option( 'mo2f_wa_template_id' );
$mo2f_language                  = get_site_option( 'mo2f_wa_template_language' );
$mo2f_otp_length                = get_site_option( 'mo2f_wa_otp_length' );
$mo2f_mo_otp_length             = $mo2f_otp_length ? $mo2f_otp_length : 5;
$mo2f_manual_report_clear_nonce = wp_create_nonce( 'mo2f-advance-settings-nonce' );

require $mo2f_dir_name . 'views' . DIRECTORY_SEPARATOR . 'mo2fawhatsapp.php';
