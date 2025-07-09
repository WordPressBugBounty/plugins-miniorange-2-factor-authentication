<?php
/**
 * This file contains code related to admin actions.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Common_Helper;
use WP_REST_Request;
use TwoFA\Helper\MocURL;
use TwoFA\Traits\Instance;
use TwoFA\Handler\Twofa\Miniorange_Authentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Admin_Action_Handler' ) ) {
	/**
	 * Class Mo2f_Admin_Action_Handler
	 */
	class Mo2f_Admin_Action_Handler {

		use Instance;

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
				case 'mo2f_show_confirmation_popup':
					$this->mo2f_show_confirmation_popup( $_POST );
					break;
				case 'mo2f_unblock_user':
					$this->mo2f_unblock_user( $_POST );
					break;
				case 'mo2f_delete_log_file':
					$this->mo2f_delete_log_file();
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
			update_site_option( 'mo2f_email', $email );
			update_site_option( 'mo_wpns_company', $company );
			update_site_option( 'mo_wpns_password', $password );
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
			if ( ! $mo_wpns_utility->check_empty_or_null( get_site_option( 'mo_wpns_registration_status' ) ) ) {
				delete_site_option( 'mo2f_email' );
			}
			do_action( 'mo2f_rld' );
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_remove_account_details();
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
			global $mo_wpns_utility;
			$mocurl  = new MocURL();
			$content = json_decode( $mocurl->get_customer_transactions( 'otp_recharge_plan', 'WP_OTP_VERIFICATION_PLUGIN' ), true );
			if ( 'SUCCESS' === $content['status'] ) {
				update_site_option( 'mo2f_license_type', 'PREMIUM' );
			} else {
				update_site_option( 'mo2f_license_type', 'DEMO' );
				$content = json_decode( $mocurl->get_customer_transactions( '-1', 'DEMO' ), true );
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
			$remaining_transaction = $mo_wpns_utility->mo2f_check_remaining_transactions();
			wp_send_json_success(
				array(
					'sms_remaining'   => $remaining_transaction['sms_transactions'],
					'email_remaining' => $remaining_transaction['email_transactions'],
				)
			);
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
			if ( 'true' === $send_configuration ) {
				$query = $query . MoWpnsUtility::mo_2fa_send_configuration();
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

		/**
		 * Unblocks the user.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_unblock_user( $post ) {
			$user_id = isset( $post['user_id'] ) ? intval( $post['user_id'] ) : 0;
			if ( is_null( $user_id ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'Invalid user ID.' ) );
			}
			delete_user_meta( $user_id, 'mo2f_grace_period_start_time' );
			wp_send_json_success( MoWpnsMessages::lang_translate( 'User unblocked successfully!' ) );
		}
		/**
		 * Returns the HTML of the confirmation popup.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_show_confirmation_popup( $post ) {
			if ( ! current_user_can( 'edit_users' ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'You do not have permission to perform this action.' ) );
			}
			if ( ! isset( $post['user_id'] ) || ! is_numeric( $post['user_id'] ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'Invalid user ID.' ) );
			}
			$user_id             = intval( $post['user_id'] );
			$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_SHOW_CONFIRMATION_BLOCK;
			$mo2fa_login_message = MoWpnsMessages::UNBLOCK_CONFIRMATION;
			$login_popup         = new Mo2f_Login_Popup();
			ob_start();
			$skeleton_values = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, get_user_by( 'id', $user_id ), 'test_2fa', '' );
			$html            = $login_popup->mo2f_twofa_authentication_login_prompt( $mo2fa_login_status, $mo2fa_login_message, null, null, $skeleton_values, '', 'test_2fa' );
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			$popup_html = ob_get_clean();
			wp_send_json_success( array( 'popup_html' => $popup_html ) );
		}

		/**
		 * Deletes the log file.
		 *
		 * @return void
		 */
		public function mo2f_delete_log_file() {
			$debug_log_path = wp_upload_dir();
			$debug_log_path = $debug_log_path['basedir'];
			$file_name      = 'miniorange_2FA_plugin_debug_log.txt';
			$status         = file_exists( $debug_log_path . DIRECTORY_SEPARATOR . $file_name );
			if ( $status ) {
				wp_delete_file( $debug_log_path . DIRECTORY_SEPARATOR . $file_name );
				wp_send_json_success( MoWpnsMessages::lang_translate( 'Log file deleted.' ) );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'Log file is not available.' ) );
			}
		}
	}
	new Mo2f_Admin_Action_Handler();
}
