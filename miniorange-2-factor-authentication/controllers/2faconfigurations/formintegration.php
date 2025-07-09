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
$form_configurations          = get_site_option( 'mo2f_custom_login_form_configurations', array() );
$form_url                     = isset( $form_configurations['mo2f_login_form_url'] ) ? $form_configurations['mo2f_login_form_url'] : '';
$submit_selector              = isset( $form_configurations['mo2f_login_submit_selector'] ) ? $form_configurations['mo2f_login_submit_selector'] : '';
$email_selector               = isset( $form_configurations['mo2f_login_email_selector'] ) ? $form_configurations['mo2f_login_email_selector'] : '';
$pass_selector                = isset( $form_configurations['mo2f_login_pass_selector'] ) ? $form_configurations['mo2f_login_pass_selector'] : '';
$form_selector                = isset( $form_configurations['mo2f_login_form_selector'] ) ? $form_configurations['mo2f_login_form_selector'] : '';
$pass_label_selector          = isset( $form_configurations['mo2f_login_passlabel_selector'] ) ? $form_configurations['mo2f_login_passlabel_selector'] : '';
$reg_form_configurations      = get_site_option( 'mo2f_custom_registration_form_configurations', array() );
$registration_submit_selector = isset( $reg_form_configurations['custom_submit_selector'] ) ? $reg_form_configurations['custom_submit_selector'] : '';
$registration_form_submit     = isset( $reg_form_configurations['form_submit_after_validation'] ) ? $reg_form_configurations['form_submit_after_validation'] : '';
$registration_form_name       = isset( $reg_form_configurations['form_name'] ) ? $reg_form_configurations['form_name'] : '';
$registration_form_selector   = isset( $reg_form_configurations['custom_form_name'] ) ? $reg_form_configurations['custom_form_name'] : '';
$registration_email_field     = isset( $reg_form_configurations['custom_email_selector'] ) ? $reg_form_configurations['custom_email_selector'] : '';
$registration_auth_type       = isset( $reg_form_configurations['custom_auth_type'] ) ? $reg_form_configurations['custom_auth_type'] : MoWpnsConstants::OTP_OVER_EMAIL;
$registration_phone_selector  = isset( $reg_form_configurations['custom_phone_selector'] ) ? $reg_form_configurations['custom_phone_selector'] : '';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'link-tracer.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '2faconfigurations' . DIRECTORY_SEPARATOR . 'formintegration.php';
