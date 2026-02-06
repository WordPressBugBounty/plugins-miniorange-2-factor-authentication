<?php
/**
 * This file is controller for views/two-fa-custom-form.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mo2f_form_configurations          = get_site_option( 'mo2f_custom_login_form_configurations', array() );
$mo2f_form_url                     = isset( $mo2f_form_configurations['mo2f_login_form_url'] ) ? $mo2f_form_configurations['mo2f_login_form_url'] : '';
$mo2f_submit_selector              = isset( $mo2f_form_configurations['mo2f_login_submit_selector'] ) ? $mo2f_form_configurations['mo2f_login_submit_selector'] : '';
$mo2f_email_selector               = isset( $mo2f_form_configurations['mo2f_login_email_selector'] ) ? $mo2f_form_configurations['mo2f_login_email_selector'] : '';
$mo2f_pass_selector                = isset( $mo2f_form_configurations['mo2f_login_pass_selector'] ) ? $mo2f_form_configurations['mo2f_login_pass_selector'] : '';
$mo2f_form_selector                = isset( $mo2f_form_configurations['mo2f_login_form_selector'] ) ? $mo2f_form_configurations['mo2f_login_form_selector'] : '';
$mo2f_pass_label_selector          = isset( $mo2f_form_configurations['mo2f_login_passlabel_selector'] ) ? $mo2f_form_configurations['mo2f_login_passlabel_selector'] : '';
$mo2f_reg_form_configurations      = get_site_option( 'mo2f_custom_registration_form_configurations', array() );
$mo2f_registration_submit_selector = isset( $mo2f_reg_form_configurations['custom_submit_selector'] ) ? $mo2f_reg_form_configurations['custom_submit_selector'] : '';
$mo2f_registration_form_submit     = isset( $mo2f_reg_form_configurations['form_submit_after_validation'] ) ? $mo2f_reg_form_configurations['form_submit_after_validation'] : '';
$mo2f_registration_form_name       = isset( $mo2f_reg_form_configurations['form_name'] ) ? $mo2f_reg_form_configurations['form_name'] : '';
$mo2f_registration_form_selector   = isset( $mo2f_reg_form_configurations['custom_form_name'] ) ? $mo2f_reg_form_configurations['custom_form_name'] : '';
$mo2f_registration_email_field     = isset( $mo2f_reg_form_configurations['custom_email_selector'] ) ? $mo2f_reg_form_configurations['custom_email_selector'] : '';
$mo2f_registration_auth_type       = isset( $mo2f_reg_form_configurations['custom_auth_type'] ) ? $mo2f_reg_form_configurations['custom_auth_type'] : MoWpnsConstants::OTP_OVER_EMAIL;
$mo2f_registration_phone_selector  = isset( $mo2f_reg_form_configurations['custom_phone_selector'] ) ? $mo2f_reg_form_configurations['custom_phone_selector'] : '';
require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'formintegration.php';
