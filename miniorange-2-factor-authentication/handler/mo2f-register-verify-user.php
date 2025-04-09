<?php
/**
 * Description: File contains functions to register, verify and save the information for customer account.
 *
 * @package miniorange-2-factor-authentication/twofactor/myaccount/handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MocURL;

require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-register-verify-user.php';

global $mo_wpns_utility,$mo2f_dir_name,$mo2fdb_queries;
$nonce = isset( $_POST['mo2f_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_general_nonce'] ) ) : '';
if ( wp_verify_nonce( $nonce, 'miniOrange_2fa_nonce' ) ) {
	if ( isset( $_POST['option'] ) ) {
		$option = trim( isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : null );
		switch ( $option ) {
			case 'mo_wpns_register_customer':
				mo2fa_register_customer( $_POST );
				break;
			case 'mo_wpns_verify_customer':
				mo2fa_verify_customer( $_POST );
				break;
			case 'mo_wpns_cancel':
				mo2f_revert_back_registration();
				break;
			case 'mo_wpns_reset_password':
				mo2f_reset_password();
				break;
			case 'mo2f_goto_verifycustomer':
				mo2f_goto_sign_in_page();
				break;
		}
	}
}


/**
 * Description: Function to register the customer in miniOrange.
 *
 * @param array $post array of customer details .
 * @return void
 */
function mo2fa_register_customer( $post ) {
	global $mo2fdb_queries;
	$user             = wp_get_current_user();
	$email            = sanitize_email( $post['email'] );
	$company          = isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null;
	$show_message     = new MoWpnsMessages();
	$password         = $post['password'];
	$confirm_password = $post['confirmPassword'];

	if ( strlen( $password ) < 6 || strlen( $confirm_password ) < 6 ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_LENGTH ), 'ERROR' );
		return;
	}

	if ( $password !== $confirm_password ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_MISMATCH ), 'ERROR' );
		return;
	}
	if ( MoWpnsUtility::check_empty_or_null( $email ) || MoWpnsUtility::check_empty_or_null( $password )
		|| MoWpnsUtility::check_empty_or_null( $confirm_password ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ), 'ERROR' );
		return;
	}
	update_option( 'mo2f_email', $email );
	update_option( 'mo_wpns_company', $company );
	update_option( 'mo_wpns_password', $password );
	$customer = new MocURL();
	$content  = json_decode( $customer->check_customer( $email ), true );
	switch ( $content['status'] ) {
		case 'CUSTOMER_NOT_FOUND':
			$customer_key = json_decode( $customer->create_customer( $email, $company, $password ), true );
			$mo2f_message = isset( $customer_key['message'] ) ? $customer_key['message'] : __( 'Error occured while creating an account.', 'miniorange-2-factor-authentication' );
			if ( strcasecmp( $customer_key['status'], 'SUCCESS' ) === 0 ) {
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not used for obfuscation
					update_option( 'mo2f_email', $email );
					$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					mo2fa_save_success_customer_config( $email, $id, $api_key, $token, $app_secret );
					mo2fa_get_current_customer( $email, $password );
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $mo2f_message ), 'SUCCESS' );
					return;
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $mo2f_message ), 'ERROR' );
				return;
			}
			break;
		case 'SUCCESS':
			update_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
			update_option( 'mo_wpns_verify_customer', 'true' );
			delete_option( 'mo_wpns_new_registration' );
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS ), 'ERROR' );
			return;
		case 'ERROR':
			$show_message->mo2f_show_message( __( $content['message'], 'miniorange-2-factor-authentication' ), 'ERROR' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
			return;
		default:
			mo2fa_get_current_customer( $email, $password );
			return;
	}
	$message = __( 'Error Occured while registration', 'miniorange-2-factor-authentication' );
	$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $message ), 'ERROR' );
}

/**
 * Description: Function for verifying the customer.
 *
 * @param array $post Post variable array of customer details.
 * @return void
 */
function mo2fa_verify_customer( $post ) {
	global $mo_wpns_utility;
	$email        = sanitize_email( $post['email'] );
	$password     = $post['password'];
	$show_message = new MoWpnsMessages();
	if ( $mo_wpns_utility->check_empty_or_null( $email ) || $mo_wpns_utility->check_empty_or_null( $password ) ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ), 'ERROR' );
		return;
	}
	mo2fa_get_current_customer( $email, $password );
}

/**
 * Description: Function to redirect the user back to registration page.
 *
 * @return void
 */
function mo2f_revert_back_registration() {
	delete_option( 'mo2f_email' );
	delete_option( 'mo_wpns_registration_status' );
	delete_option( 'mo_wpns_verify_customer' );
	update_option( 'mo_2factor_admin_registration_status', '' );
}

/**
 * Description: Function to reset password of account
 *
 * @return void
 */
function mo2f_reset_password() {
	$customer                 = new MocURL();
	$show_message             = new MoWpnsMessages();
	$forgot_password_response = json_decode( $customer->mo_wpns_forgot_password() );
	if ( 'SUCCESS' === $forgot_password_response->status ) {
		$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::RESET_PASS ), 'SUCCESS' );
	}
}

/**
 * Description: Function redirects the user to signin page after verification.
 *
 * @return void
 */
function mo2f_goto_sign_in_page() {
	update_option( 'mo_wpns_verify_customer', 'true' );
	update_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_VERIFY_CUSTOMER' );
}
