<?php
/**
 * This file contains code related to admin actions.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Onprem;

use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Common_Helper;
use WP_REST_Request;
use TwoFA\Helper\MocURL;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Admin_Action_Handler' ) ) {
	/**
	 * Class Mo2f_Admin_Action_Handler
	 */
	class Mo2f_Admin_Action_Handler {

		/**
		 * Cunstructor for Mo2f_Admin_Action_Handler
		 */
		public function __construct() {
			add_action( 'wp_ajax_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
		}

		/**
		 * Handles ajax calls.
		 *
		 * @return void
		 */
		public function mo_two_factor_ajax() {
			$GLOBALS['mo2f_is_ajax_request'] = true;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-mo2f-ajax' );
			}
			$option = isset( $_POST['mo_2f_two_factor_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2f_two_factor_ajax'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_miniorange_sign_in':
					$this->mo2f_miniorange_sign_in( $_POST );
					break;
				case 'mo2f_miniorange_sign_up':
					$this->mo2f_miniorange_sign_up( $_POST );
					break;
				case 'mo2f_remove_miniorange_account':
					$this->mo2f_remove_miniorange_account();
					break;
				case 'mo2f_check_transactions':
					$this->mo2f_check_transactions();
					break;
				case 'mo2f_handle_support_form':
					$this->mo2f_handle_support_form( $_POST );
					break;
			}
		}

		/**
		 * Signs in miniOrange user.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_miniorange_sign_in( $post ) {
			global $mo_wpns_utility;
			$email    = isset( $post['email'] ) ? sanitize_email( wp_unslash( $post['email'] ) ) : '';
			$password = isset( $post['password'] ) ? wp_unslash( $post['password'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			if ( $mo_wpns_utility->check_empty_or_null( $email ) || $mo_wpns_utility->check_empty_or_null( $password ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ) );
			}
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_get_miniorange_customer( $email, $password );
		}

		/**
		 * Sings up to miniOrange.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_miniorange_sign_up( $post ) {
			$email            = isset( $post['email'] ) ? sanitize_email( wp_unslash( $post['email'] ) ) : '';
			$company          = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
			$password         = isset( $post['password'] ) ? wp_unslash( $post['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$confirm_password = isset( $post['confirmPassword'] ) ? wp_unslash( $post['confirmPassword'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			if ( strlen( $password ) < 6 || strlen( $confirm_password ) < 6 ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_LENGTH ) );
			}
			if ( $password !== $confirm_password ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::PASS_MISMATCH ) );
			}
			if ( MoWpnsUtility::check_empty_or_null( $email ) || MoWpnsUtility::check_empty_or_null( $password ) || MoWpnsUtility::check_empty_or_null( $confirm_password ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS ) );
			}
			update_option( 'mo2f_email', $email );
			update_option( 'mo_wpns_company', $company );
			update_option( 'mo_wpns_password', $password );
			$customer      = new MocURL();
			$content       = json_decode( $customer->check_customer( $email ), true );
			$common_helper = new Mo2f_Common_Helper();
			switch ( $content['status'] ) {
				case 'CUSTOMER_NOT_FOUND':
					$customer_key  = json_decode( $customer->create_customer( $email, $company, $password ), true );
					$login_message = isset( $customer_key['message'] ) ? $customer_key['message'] : __( 'Error occured while creating an account.', 'miniorange-2-factor-authentication' );
					if ( strcasecmp( $customer_key['status'], 'SUCCESS' ) === 0 ) {
						$common_helper->mo2f_get_miniorange_customer( $email, $password );
					} else {
						wp_send_json_error( MoWpnsMessages::lang_translate( $login_message ) );
					}
					break;
				case 'SUCCESS':
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ALREADY_ACCOUNT_EXISTS ) );
					break;
				case 'ERROR':
					wp_send_json_error( MoWpnsMessages::lang_translate( $content['message'] ) );
					break;
				default:
					$common_helper->mo2f_get_miniorange_customer( $email, $password );
					return;
			}
			wp_send_json_error( MoWpnsMessages::lang_translate( 'Error Occured while registration. Please try again.' ) );
		}

		/**
		 * Handles logout form.
		 *
		 * @return void
		 */
		public function mo2f_remove_miniorange_account() {
			global $mo_wpns_utility, $mo2fdb_queries;
			if ( ! $mo_wpns_utility->check_empty_or_null( get_option( 'mo_wpns_registration_status' ) ) ) {
				delete_option( 'mo2f_email' );
			}
			delete_option( 'mo2f_customerKey' );
			delete_option( 'mo2f_api_key' );
			delete_option( 'mo2f_customer_token' );
			delete_option( 'mo_wpns_transactionId' );
			delete_option( 'mo_wpns_registration_status' );
			delete_site_option( 'mo_2factor_admin_registration_status' );
			delete_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
			if ( ! MO2F_IS_ONPREM ) {
				$mo2fdb_queries->mo2f_delete_cloud_meta_on_account_remove();

			}
			$two_fa_settings = new Miniorange_Authentication();
			$two_fa_settings->mo2f_auth_deactivate();
			wp_send_json_success( MoWpnsMessages::lang_translate( 'Account removed successfully.' ) );
		}

		/**
		 * Checks customer transactions and updates the same in options table.
		 *
		 * @return void
		 */
		public function mo2f_check_transactions() {
			$mocurl  = new MocURL();
			$content = json_decode( $mocurl->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'WP_OTP_VERIFICATION_PLUGIN)' ), true );
			if ( 'SUCCESS' === $content['status'] ) {
				update_site_option( 'mo2f_license_type', 'PREMIUM' );
			} else {
				update_site_option( 'mo2f_license_type', 'DEMO' );
				$content = json_decode( $mocurl->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'DEMO' ), true );
			}
			if ( isset( $content['smsRemaining'] ) ) {
				update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $content['smsRemaining'] );
			} elseif ( 'SUCCESS' === $content['status'] ) {
				update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', 0 );
			}

			if ( isset( $content['emailRemaining'] ) ) {
				if ( MO2F_IS_ONPREM ) {
					$available_transaction = get_site_option( 'EmailTransactionCurrent', 30 );
					if ( $content['emailRemaining'] > $available_transaction && $content['emailRemaining'] > 10 ) {
						$current_transaction = $content['emailRemaining'] + get_site_option( 'cmVtYWluaW5nT1RQ' );
						update_site_option( 'bGltaXRSZWFjaGVk', 0 );
						if ( $available_transaction > 30 ) {
							$current_transaction = $current_transaction - $available_transaction;
						}

						update_site_option( 'cmVtYWluaW5nT1RQ', $current_transaction );
						update_site_option( 'EmailTransactionCurrent', $content['emailRemaining'] );
					}
				} else {
					update_site_option( 'cmVtYWluaW5nT1RQ', $content['emailRemaining'] );
					if ( $content['emailRemaining'] > 0 ) {
						update_site_option( 'bGltaXRSZWFjaGVk', 0 );
					}
				}
			}
			wp_send_json_success( MoWpnsMessages::lang_translate( 'Transactions updated successfully.' ) );

		}

		/**
		 * Handles support form.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_handle_support_form( $post ) {
			$query              = isset( $post['mo2f_query'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_query'] ) ) : '';
			$phone              = isset( $post['mo2f_query_phone'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_query_phone'] ) ) : '';
			$email              = isset( $post['mo2f_query_email'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_query_email'] ) ) : '';
			$send_configuration = ( isset( $post['mo2f_send_configuration'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_send_configuration'] ) ) : 0 );
			$submited           = array();
			if ( empty( $email ) || empty( $query ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_VALUES ) );
			}
			$contact_us = new MocURL();
			if ( $send_configuration ) {
				$query = $query . MoWpnsUtility::mo_2fa_send_configuration( true );
			}
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ) );
			} elseif ( get_transient( 'mo2f_query_sent' ) ) {
				wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::QUERY_SUBMITTED ) );
			} else {
				$submited = json_decode( $contact_us->submit_contact_us( $email, $phone, $query ), true );
			}
			if ( json_last_error() === JSON_ERROR_NONE && $submited ) {
				wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_SENT ) );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::SUPPORT_FORM_ERROR ) );
			}
		}

	}
	new Mo2f_Admin_Action_Handler();
}
