<?php
/**
 * This file is controller for controllers/whitelabelling/loginpopup.php.
 *
 * @package miniorange-2-factor-authentication/controllers/whitelabelling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_popup_css = get_site_option( 'mo2f_custom_2fa_popup_css', array() );
$mo2f_login_popup = array(
	'Background Color:'               => 'mo2f_custom_background_color',
	'Popup Background Color:'         => 'mo2f_custom_popup_bg_color',
	'Button  Color:'                  => 'mo2f_custom_button_color',
	'Popup Message Text Color:'       => 'mo2f_custom_notif_text_color',
	'Popup Message Background Color:' => 'mo2f_custom_notif_bg_color',
	'OTP Token Background Color:'      => 'mo2f_custom_otp_bg_color',
	'OTP Token Text Color:'           => 'mo2f_custom_otp_text_color',
	'Header Text Color:'               => 'mo2f_custom_header_text_color',
	'Middle Text Color:'              => 'mo2f_custom_middle_text_color',
	'Footer Text Color:'              => 'mo2f_custom_footer_text_color',

);
$emails_popup_setting = get_site_option( 'mo2f_email_verification_settings' );
$accept_text_color    = isset( $emails_popup_setting['mo2f_accept_text_color'] ) ? $emails_popup_setting['mo2f_accept_text_color'] : '#008000';
$deny_text_color      = isset( $emails_popup_setting['mo2f_deny_text_color'] ) ? $emails_popup_setting['mo2f_deny_text_color'] : '#FF0000';
$bg_color             = isset( $emails_popup_setting['mo2f_accept_deny_bg_color'] ) ? $emails_popup_setting['mo2f_accept_deny_bg_color'] : '#FFFFFF';
$custom_img_url       = isset( $emails_popup_setting['mo2f_custom_accept_deny_img'] ) ? $emails_popup_setting['mo2f_custom_accept_deny_img'] : '';
require dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'whitelabelling' . DIRECTORY_SEPARATOR . 'loginpopup.php';

