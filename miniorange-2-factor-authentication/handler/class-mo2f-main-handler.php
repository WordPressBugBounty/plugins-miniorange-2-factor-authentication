<?php
/**
 * This includes functions regarding the login flow.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Twofa\Miniorange_Mobile_Login;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Helper\MocURL;
use WP_Error;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Handler\Mo2f_Reconfigure_Link;
use TwoFA\Handler\Mo2f_Backup_Codes;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Mo2f_Main_Handler' ) ) {
	/**
	 * Class for log login transactions
	 */
	class Mo2f_Main_Handler {

		use Instance;

		/**
		 * For user id variable
		 *
		 * @var string
		 */
		private $mo2f_user_id;

		/**
		 * First 2FA
		 *
		 * @var string .
		 */
		private $fstfactor;

		/**
		 * Class Mo2f_Cloud_Onprem_Interface object
		 *
		 * @var object
		 */
		private $mo2f_onprem_cloud_obj;

		/**
		 * Cunstructor for Mo2f_Main_Handler
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
			add_action( 'init', array( $this, 'miniorange_pass2login_redirect' ) );
			add_action( 'wp_ajax_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
			add_filter( 'login_errors', array( $this, 'mo2f_show_error_on_wp_login_form' ) );
			add_filter( 'login_message', array( $this, 'mo2f_show_on_wp_login_form' ) );
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
				case 'mo2f_send_otp_for_configuration':
					$this->mo2f_send_otp_for_configuration( $_POST );
					break;
				case 'mo2f_validate_otp_for_configuration':
					$this->mo2f_validate_otp_for_configuration( $_POST );
					break;
				case 'mo2f_start_setup_2fa_dashboard':
					$this->mo2f_start_setup_2fa_dashboard( $_POST );
					break;
				case 'mo2f_set_kba':
					$this->mo2f_set_kba( $_POST );
					break;
				case 'mo2f_validate_user_for_login':
					$this->mo2f_validate_user_for_login( $_POST );
					break;
				case 'mo2f_validate_backup_codes':
					$this->mo2f_validate_backup_codes( $_POST );
					break;
				case 'mo2f_skiptwofactor_wizard':
					$this->mo2f_skiptwofactor_wizard( $_POST );
					break;
				case 'mo2f_resend_otp_login':
					$this->mo2f_resend_otp_login( $_POST );
					break;
			}
		}

		/**
		 * Handles form data in login flow.
		 *
		 * @return void
		 */
		public function miniorange_pass2login_redirect() {
			if ( isset( $_GET['Txid'] ) && isset( $_GET['accessToken'] ) ) {
				$inline_popup    = new Mo2f_Common_Helper();
				$out_of_band_obj = $inline_popup->mo2f_get_object( MoWpnsConstants::OUT_OF_BAND_EMAIL );
				$useridget       = isset( $_GET['userID'] ) ? sanitize_text_field( wp_unslash( $_GET['userID'] ) ) : '';
				$txidget         = isset( $_GET['Txid'] ) ? sanitize_text_field( wp_unslash( $_GET['Txid'] ) ) : '';
				$accesstokenget  = isset( $_GET['accessToken'] ) ? sanitize_text_field( wp_unslash( $_GET['accessToken'] ) ) : '';
				$out_of_band_obj->mo2f_process_link_validation( $useridget, $txidget, $accesstokenget );
			} if ( isset( $_POST['mo2f_out_of_band_email'] ) ) {
				$inline_popup    = new Mo2f_Common_Helper();
				$out_of_band_obj = $inline_popup->mo2f_get_object( MoWpnsConstants::OUT_OF_BAND_EMAIL );
				$txidpost        = TwoFAMoSessions::get_session_var( 'mo2f_transactionId' );
				$out_of_band_obj->mo2f_handle_polling( $txidpost );
			}
			$nonce = isset( $_POST['miniorange_inline_save_2factor_method_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['miniorange_inline_save_2factor_method_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) {
				return;
			}
			$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'miniorange_inline_save_2factor_method':
					$this->save_inline_2fa_method( $_POST );
					break;
				case 'mo2f_process_validation_success':
					$this->mo2f_process_validation_success( $_POST );
					break;
				case 'mo2f_backup_code_validation_success':
					$this->mo2f_backup_code_validation_success( $_POST );
					break;
				case 'mo2f_send_reconfig_link':
					$this->mo2f_send_reconfig_link( $_POST );
					break;
				case 'mo2f_email_verification_success':
					$this->mo2f_email_verification_success( $_POST );
					break;
				case 'mo2f_email_verification_failed':
					$this->mo2f_email_verification_failed( $_POST );
					break;
				case 'mo2f_use_backup_codes':
					$this->mo2f_use_backup_codes( $_POST );
					break;
				case 'mo2f_send_backup_codes':
					$this->mo2f_send_backup_codes( $_POST );
					break;
				case 'miniorange2f_back_to_inline_registration':
					$this->miniorange2f_back_to_inline_registration( $_POST );
					exit;
				case 'mo2f_back_to_mfa_screen':
					$this->mo2f_back_to_mfa_screen( $_POST );
					exit;
				case 'miniorange_mfactor_method':
					$this->mo2f_select_mfa_method( $_POST );
					break;
				case 'mo2f_back_to_2fa_validation_screen':
					$this->mo2f_twofa_validation_screen( $_POST );
					break;
				case 'mo2f_skip_2fa_setup':
					$this->mo2f_skip_2fa_setup( $_POST );
					break;
				case 'mo2f_download_backup_codes_inline':
					$this->mo2f_download_backup_codes_inline( $_POST );
					break;
				case 'mo2f_finish_inline_and_login':
					$this->mo2f_finish_inline_and_login( $_POST );
					break;
				case 'mo2f_show_backup_method':
					$this->mo2f_show_backup_method( $_POST );
					break;
				case 'mo2f_remeber_device_true':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_remeber_device_true', $_POST );
					break;
				case 'mo2f_remeber_device_false':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_remeber_device_false', $_POST );
					break;
				case 'mo2f_remember_ip_true':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_remember_ip_true', $_POST );
					break;
				case 'mo2f_remember_ip_false':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_remember_ip_false', $_POST );
					break;
			}
		}

		/**
		 * Calls to validate backup codes function.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_validate_backup_codes( $post ) {
			$backup_code_handler = new Mo2f_Backup_Codes();
			$backup_code_handler->mo2f_validate_backup_codes( $post );
		}

		/**
		 * Calls to validate backup codes function.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_show_backup_method( $post ) {
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$mo2f_second_factor = MoWpnsConstants::SECURITY_QUESTIONS;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$this->mo2f_select_method_for_login( get_user_by( 'id', $user_id ), $mo2f_second_factor, $session_id_encrypt, $redirect_to );
		}

		/**
		 * Shows error message on login form.
		 *
		 * @param object $error Error.
		 * @return mixed
		 */
		public function mo2f_show_error_on_wp_login_form( $error ) {
			if ( isset( $_SESSION['email_transaction_denied_error'] ) ) {
				$error = $_SESSION['email_transaction_denied_error'];
				unset( $_SESSION['email_transaction_denied_error'] );
			}
			if ( TwoFAMoSessions::get_session_var( 'mo2f_change_error_message' ) ) {
				$error = TwoFAMoSessions::get_session_var( 'mo2f_change_error_message' );
				TwoFAMoSessions::unset_session( 'mo2f_change_error_message' );
			}
			return $error;
		}

		/**
		 * Shows error message on login form.
		 *
		 * @param object $error Error.
		 * @return mixed
		 */
		public function mo2f_show_on_wp_login_form( $error ) {
			if ( TwoFAMoSessions::get_session_var( 'mo2f_show_error_message' ) ) {
				$error = "<div id='login_error1'> <p>" . TwoFAMoSessions::get_session_var( 'mo2f_show_error_message' ) . '</p></div>';
				TwoFAMoSessions::unset_session( 'mo2f_show_error_message' );
			}
			return $error;
		}

		/**
		 * Processes the flow after successful backup code validation.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_backup_code_validation_success( $post ) {
			$backup_code_handler = new Mo2f_Backup_Codes();
			$backup_code_handler->mo2f_backup_code_validation_success( $post );
		}

		/**
		 * Process the flow when click on use backup codes link.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_use_backup_codes( $post ) {
			$backup_code_handler = new Mo2f_Backup_Codes();
			$backup_code_handler->mo2f_use_backup_codes( $post );
		}

		/**
		 * Sends the backup codes on email.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_send_backup_codes( $post ) {
			$backup_code_handler = new Mo2f_Backup_Codes();
			$backup_code_handler->mo2f_send_backup_codes( $post );
		}

		/**
		 * Sends the reconfiguration link on email.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_send_reconfig_link( $post ) {
			$backup_code_handler = new Mo2f_Reconfigure_Link();
			$backup_code_handler->mo2f_send_reconfig_link( $post );
		}

		/**
		 * Resend the OTP for login.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_resend_otp_login( $post ) {
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$method_name        = isset( $post['auth_method'] ) ? sanitize_text_field( wp_unslash( $post['auth_method'] ) ) : null;
			$current_user       = get_user_by( 'id', $user_id );
			$inline_popup       = new Mo2f_Common_Helper();
			$process_login      = $inline_popup->mo2f_get_object( $method_name );
			$message            = MoWpnsMessages::lang_translate( MoWpnsMessages::NEW_OTP_SENT );
			$process_login->mo2f_send_otp( null, $session_id_encrypt, $current_user, $message );
		}

		/**
		 * Sets the status for skip button in setupwizard.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_skiptwofactor_wizard( $post ) {
			$skip_wizard_2fa_stage = isset( $post['twofactorskippedon'] ) ? sanitize_text_field( wp_unslash( $post['twofactorskippedon'] ) ) : null;
			update_site_option( 'mo2f_wizard_skipped', $skip_wizard_2fa_stage );
			wp_send_json_success();
		}

		/**
		 * Shows inline selected method.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function save_inline_2fa_method( $post ) {
			global $mo2fdb_queries;
			MO2f_Utility::mo2f_start_session();
			$mo2fa_login_message               = '';
			$session_id_encrypt                = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$user_id                           = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$redirect_to                       = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : null;
			$current_user                      = get_user_by( 'id', $user_id );
			$user_registration_with_miniorange = $mo2fdb_queries->mo2f_get_user_detail( 'user_registration_with_miniorange', $current_user->ID );
			if ( 'SUCCESS' === $user_registration_with_miniorange ) {
				$selected_method = isset( $post['mo2f_selected_2factor_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_selected_2factor_method'] ) ) : 'NONE';
				$inline_popup    = new Mo2f_Common_Helper();
				$show_method     = $inline_popup->mo2f_get_object( $selected_method );
				$show_method->mo2f_prompt_2fa_setup_inline( $session_id_encrypt, $redirect_to, $user_id, $mo2fa_login_message );
			} else {
				$inline_popup = new Mo2f_Inline_Popup();
				$inline_popup->prompt_user_to_select_2factor_mthod_inline( $user_id, '', $redirect_to, $session_id_encrypt );
			}
			exit;
		}

		/**
		 * This function will help to redirect back to inline form
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function miniorange2f_back_to_inline_registration( $post ) {
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to        = esc_url_raw( $post['redirect_to'] );
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$inline_popup       = new Mo2f_Inline_Popup();
			$inline_popup->prompt_user_to_select_2factor_mthod_inline( $user_id, '', $redirect_to, $session_id_encrypt );
			exit;
		}
		/**
		 * This function will help to redirect back to mfa form
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_back_to_mfa_screen( $post ) {
			$session_id_encrypt     = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to            = esc_url_raw( $post['redirect_to'] );
			$user_id                = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			$common_helper          = new Mo2f_Common_Helper();
			$configure_array_method = $common_helper->mo2fa_return_methods_value( $user_id );
			$login_popup            = new Mo2f_Login_Popup();
			$login_popup->mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to );
			exit;
		}

		/**
		 * Calls to send otp funciton of twofa method.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_send_otp_for_configuration( $post ) {
			$twofa_method       = isset( $post['mo2f_otp_based_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_otp_based_method'] ) ) : '';
			$otp_input          = isset( $post['mo2f_phone_email_telegram'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_phone_email_telegram'] ) ) : null;
			$session_id_encrypt = isset( $post['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_session_id'] ) ) : null;
			if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_input ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ) );
			}
			$otp_input = str_replace( ' ', '', $otp_input );
			$user      = wp_get_current_user();
			if ( ! $user->ID ) {
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$user    = get_user_by( 'id', $user_id );
			}
			$inline_popup = new Mo2f_Common_Helper();
			$send_otp     = $inline_popup->mo2f_get_object( $twofa_method );
			$message      = MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT );
			$send_otp->mo2f_send_otp( $otp_input, $session_id_encrypt, $user, $message );
		}

		/**
		 * This function will invoke for back up code validation
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_twofa_validation_screen( $post ) {
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$session_id         = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$mo2f_second_factor = isset( $post['twofa_method'] ) ? sanitize_text_field( wp_unslash( $post['twofa_method'] ) ) : '';
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
			$this->mo2f_select_method_for_login( get_user_by( 'id', $user_id ), $mo2f_second_factor, $session_id, $redirect_to );
			exit;
		}

		/**
		 * Calls to validate otp function of a twofa method.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_validate_otp_for_configuration( $post ) {
			$otp_token          = isset( $post['otp_token'] ) ? sanitize_text_field( wp_unslash( $post['otp_token'] ) ) : '';
			$session_id_encrypt = isset( $post['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_session_id'] ) ) : null;
			$user               = wp_get_current_user();
			if ( ! $user->ID ) {
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$user    = get_user_by( 'id', $user_id );
			}
			if ( MO2f_Utility::mo2f_check_empty_or_null( $otp_token ) ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_ENTRY ) );
			}
			$twofa_method = isset( $post['mo2f_otp_based_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_otp_based_method'] ) ) : '';
			$prev_input   = isset( $post['mo2f_phone_email_telegram'] ) ? str_replace( ' ', '', sanitize_text_field( wp_unslash( $post['mo2f_phone_email_telegram'] ) ) ) : '';
			$inline_popup = new Mo2f_Common_Helper();
			$validate_otp = $inline_popup->mo2f_get_object( $twofa_method );
			$validate_otp->mo2f_validate_otp( $otp_token, $session_id_encrypt, $user, $prev_input, $post );
		}

		/**
		 * Choose method for 2FA validation.
		 *
		 * @param array $post Post data.
		 * @return mixed
		 */
		public function mo2f_validate_user_for_login( $post ) {
			$twofa_method         = isset( $post['mo2f_login_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_login_method'] ) ) : null;
			$session_id_encrypt   = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to          = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : null;
			$otp_token            = isset( $post['mo2fa_softtoken'] ) ? sanitize_text_field( wp_unslash( $post['mo2fa_softtoken'] ) ) : '';
			$inline_common_helper = new Mo2f_Common_Helper();
			$method_handler       = $inline_common_helper->mo2f_get_object( $twofa_method );
			$method_handler->mo2f_login_validate( $otp_token, $redirect_to, $session_id_encrypt );
		}

		/**
		 * Handles flow after successful otp validation in Inline.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_process_validation_success( $post ) {
			global $mo2fdb_queries;
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$mo2fa_login_status = isset( $post['twofa_status'] ) ? sanitize_text_field( wp_unslash( $post['twofa_status'] ) ) : '';
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS !== $mo2fa_login_status || TwoFAMoSessions::get_session_var( 'mo2f_is_kba_backup_configured' . $user_id ) ) {
				$inline_helper = new Mo2f_Common_Helper();
				$inline_helper->mo2f_inline_setup_success( $user_id, $redirect_to, $session_id_encrypt );
			} else {
				if ( get_site_option( 'mo2f_remember_device' ) ) {
					do_action(
						'mo2f_enterprise_plan_settings_action',
						'mo2f_remember_device_details',
						array(
							'user_id'     => $user_id,
							'session_id'  => $session_id_encrypt,
							'redirect_to' => $redirect_to,
						)
					);
				}
				$remember_ip_configurations = (array) get_site_option( 'mo2f_remember_ip_configurations' );
				if ( isset( $remember_ip_configurations['mo2f_remember_ip_feature'] ) && $remember_ip_configurations['mo2f_remember_ip_feature'] ) {
					do_action(
						'mo2f_all_inclusive_plan_settings_action',
						'mo2f_remember_ip_details',
						array(
							'user_id'       => $user_id,
							'session_id'    => $session_id_encrypt,
							'redirect_to'   => $redirect_to,
							'rem_ip_choice' => isset( $remember_ip_configurations['mo2f_give_rem_ip_choice'] ) ? $remember_ip_configurations['mo2f_give_rem_ip_choice'] : 0,
						)
					);
				}
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}

		/**
		 * Pass2 login method
		 *
		 * @param string $redirect_to It will carry the redirect url.
		 * @param string $session_id_encrypted It will carry the session id.
		 * @return void
		 */
		public function mo2fa_pass2login( $redirect_to = null, $session_id_encrypted = null ) {
			global $mo_wpns_utility;
			$common_helper = new Mo2f_Common_Helper();
			if ( empty( $this->mo2f_user_id ) && empty( $this->fstfactor ) ) {
				$user_id               = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_current_user_id' );
				$mo2f_1stfactor_status = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_1stfactor_status' );
			} else {
				$user_id               = $this->mo2f_user_id;
				$mo2f_1stfactor_status = $this->fstfactor;
			}
			$is_page_protection_flow = get_site_transient( 'mo2f_page_protection_flow_' . $user_id );
			if ( ! empty( $is_page_protection_flow ) ) {
				$page_protection_addon = apply_filters( 'mo2f_page_protection_addon_filter', false, 'mo2f_page_protection_update_details', array( 'user_id'=> $user_id ) );
				$redirect_url = $mo_wpns_utility->get_current_url();
				wp_safe_redirect( $redirect_url );
				exit;
			}
			if ( $user_id && $mo2f_1stfactor_status && ( 'VALIDATE_SUCCESS' === $mo2f_1stfactor_status ) ) {
				$currentuser = get_user_by( 'id', $user_id );
				wp_set_current_user( $user_id, $currentuser->user_login );
				$common_helper->mo2f_remove_current_activity( $session_id_encrypted );
				delete_expired_transients( true );
				delete_site_option( $session_id_encrypted );
				wp_set_auth_cookie( $user_id, true );
				do_action( 'wp_login', $currentuser->user_login, $currentuser );
				$common_helper->mo2f_redirect_user_to( $currentuser, $redirect_to );
				exit;
			} else {
				$common_helper->mo2f_remove_current_activity( $session_id_encrypted );
			}
		}

		/**
		 * Calls to validate kba in inline.
		 *
		 * @param array $post Post value.
		 * @return void
		 */
		public function mo2f_set_kba( $post ) {
			$inline_popup = new Mo2f_Common_Helper();
			$validate_kba = $inline_popup->mo2f_get_object( MoWpnsConstants::SECURITY_QUESTIONS );
			$validate_kba->mo2f_set_kba( $post );
		}

		/**
		 * It will call at the time of authentication .
		 *
		 * @param object $user It will carry the user detail.
		 * @param string $username It will carry the username .
		 * @param string $password It will carry the password .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2f_check_username_password( $user, $username, $password, $redirect_to = null ) {
			global $mo_wpns_utility;
			$is_ajax_request = MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' );
			if ( is_a( $user, 'WP_Error' ) && ! empty( $user ) ) {
				if ( $is_ajax_request ) {
					$data = MO2f_Utility::mo2f_show_error_on_login( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) );
					wp_send_json_success( $data );
				} else {
					return $user;
				}
			}
			$currentuser = wp_authenticate_username_password( $user, $username, $password );
			if ( is_wp_error( $currentuser ) ) {
				if ( $is_ajax_request ) {
					$data = MO2f_Utility::mo2f_show_error_on_login( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) );
					wp_send_json_success( $data );
				} else {
					MO2f_Utility::mo2f_debug_file( 'Invalid username and password. User_IP-' . $mo_wpns_utility->get_client_ip() );
					$currentuser->add( 'invalid_username_password', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_CREDS ) ) );
					return $currentuser;
				}
			} else {
				$session_limit_reached = apply_filters( 'mo2f_enterprise_plan_settings_filter', false, 'mo2f_check_session_details', array( 'user_id' => $currentuser->ID ) );
				if ( $session_limit_reached ) {
					return new WP_Error( 'session_limit_exceeded', MoWpnsMessages::lang_translate( MoWpnsMessages::SESSION_LIMIT_REACHED ) );
				}
				$pass2login = new Miniorange_Password_2Factor_Login();
				MO2f_Utility::mo2f_debug_file( 'Username and password  validate successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
				$session_id  = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : $pass2login->create_session();//phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Wordpres login form.
				$redirect_to = $pass2login->mo2f_get_redirect_url();
				$error       = $this->miniorange_initiate_2nd_factor( $currentuser, $redirect_to, $session_id );
				return $error;
			}
		}

		/**
		 * It will initiate 2nd factor
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @return string
		 */
		public function miniorange_initiate_2nd_factor( $currentuser, $redirect_to = '', $session_id_encrypt = null ) {
			global $mo2fdb_queries, $mo_wpns_utility, $mo2f_onprem_cloud_obj;
			$pass2login = new Miniorange_Password_2Factor_Login();
			MO2f_Utility::mo2f_debug_file( 'MO initiate 2nd factor User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
			MO2f_Utility::mo2f_start_session();
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $pass2login->create_session();
			}
			$redirect_to = class_exists( 'UM_Functions' ) ? $pass2login->mo2f_redirect_url_for_um( $currentuser ) : $redirect_to;
			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_current_user_id', $currentuser->ID, 600 );
			MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_1stfactor_status', 'VALIDATE_SUCCESS', 600 );
			$this->mo2f_user_id = $currentuser->ID;
			$this->fstfactor    = 'VALIDATE_SUCCESS';
			if ( $this->mo2f_check_if_twofa_is_enabled( $currentuser ) ) {
				$common_helper = new Mo2f_Common_Helper();
				if ( apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $currentuser->ID ), 'is_user_limit_exceeded', array() ) ) {
					return $currentuser;
				}
				if ( false === get_site_option( 'mo2f_disable_inline_registration' ) ) {
					update_site_option( 'mo2f_disable_inline_registration', get_site_option( 'mo2f_inline_registration', 1 ) ? null : 1 );
				}
				if ( $common_helper->mo2f_is_2fa_set( $currentuser->ID ) ) { // checking if user has configured any 2nd factor method.
					$attributes = isset( $_POST['miniorange_rba_attribures'] ) ? sanitize_text_field( $_POST['miniorange_rba_attribures'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Fetching browser attributes, doesn't require nonce verification.
					do_action(
						'mo2f_enterprise_plan_settings_action',
						'mo2f_check_rba_details',
						array(
							'user_id'     => $currentuser->ID,
							'attributes'  => $attributes,
							'session_id'  => $session_id_encrypt,
							'redirect_to' => $redirect_to,
						)
					);
					do_action(
						'mo2f_all_inclusive_plan_settings_action',
						'mo2f_check_remember_ip_details',
						array(
							'user_id'     => $currentuser->ID,
							'session_id'  => $session_id_encrypt,
							'redirect_to' => $redirect_to,
						)
					);
					$this->mo2f_remove_miniorange_auth_entries( $currentuser->ID ); // To do- remove this in next to next release.
					$mo2f_second_factor     = $mo2f_onprem_cloud_obj->mo2f_get_user_2ndfactor( $currentuser );
					$configure_array_method = $common_helper->mo2fa_return_methods_value( $currentuser->ID );
					if ( $common_helper->mo2f_check_mfa_details( $configure_array_method ) ) {
						$login_popup = new Mo2f_Login_Popup();
						update_site_option( 'mo2f_login_with_mfa_use', '1' );
						$login_popup->mo2fa_prompt_mfa_form_for_user( $configure_array_method, $session_id_encrypt, $redirect_to );
						exit;
					} else {
						$user = $this->mo2f_select_method_for_login( $currentuser, $mo2f_second_factor, $session_id_encrypt, $redirect_to );
						return $user;
					}
				} elseif ( ! get_site_option( 'mo2f_disable_inline_registration' ) ) {
					if ( get_site_option( 'mo2f_grace_period' ) && $common_helper->mo2f_is_grace_period_expired( $currentuser ) && 'block_user_login' === get_site_option( 'mo2f_graceperiod_action' ) ) {
						$mo2fa_login_message = 'Your grace period to setup the 2FA has expired. Please contact site admin to unblock yourself.';
						$mo2fa_login_status  = MoWpnsConstants::MO2F_USER_BLOCKED_PROMPT;
						$login_popup         = new Mo2f_Login_Popup();
						$login_popup->mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt, '' );
						exit;
					} else {
						$this->mo2f_start_inline_2fa( $currentuser, $redirect_to, $session_id_encrypt );
					}
				} else {
					if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
						$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
					} else {
						return $currentuser;
					}
				}
			} else {
				return $currentuser;
			}
		}

		/**
		 * Checks if the 2FA is enabled for this user.
		 *
		 * @param object $currentuser Current user.
		 * @return bool
		 */
		public function mo2f_check_if_twofa_is_enabled( $currentuser ) {
			$roles             = (array) $currentuser->roles;
			$twofactor_enabled = 0;
			foreach ( $roles as $role ) {
				if ( (int) get_site_option( 'mo2fa_' . $role ) ) {
					$twofactor_enabled = 1;
					break;
				}
			}
			if ( 1 !== $twofactor_enabled && is_super_admin( $currentuser->ID ) && (int) get_site_option( 'mo2fa_superadmin' ) === 1 ) {
				$twofactor_enabled = 1;
			}
			return apply_filters( 'mo2f_basic_plan_settings_filter', $twofactor_enabled, 'is_twofa_enabled', array( 'user' => $currentuser ) );
		}

		/**
		 * Removes miniOrange authenticator entries from the database.
		 *
		 * @param int $user_id User id.
		 * @return void
		 */
		public function mo2f_remove_miniorange_auth_entries( $user_id ) {
			global $mo2fdb_queries;
			if ( $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_miniOrangeSoftToken_config_status', $user_id ) || $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_miniOrangeQRCodeAuthentication_config_status', $user_id ) || $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_miniOrangePushNotification_config_status', $user_id ) ) {
				$mo2fdb_queries->mo2f_delete_user_details( $user_id );
			}
		}
		/**
		 * Starts inline flow.
		 *
		 * @param object $currentuser Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @return void
		 */
		public function mo2f_start_inline_2fa( $currentuser, $redirect_to, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$common_helper = new Mo2f_Common_Helper();
			if ( get_user_meta( $currentuser->ID, 'mo2f_user_profile_set', true ) ) {
				$selected_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $currentuser->ID );
				$inline_popup    = new Mo2f_Common_Helper();
				$show_method     = $inline_popup->mo2f_get_object( $selected_method );
				$show_method->mo2f_prompt_2fa_setup_inline( $session_id_encrypt, $redirect_to, $currentuser->ID, '' );
			} else {
				$common_helper->mo2fa_inline( $currentuser, $redirect_to, $session_id_encrypt );
			}
			exit;
		}

		/**
		 * Calls to google authentication validation function in inline.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_start_setup_2fa_dashboard( $post ) {
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_ilvn();
			$auth_method      = isset( $post['auth_method'] ) ? sanitize_text_field( wp_unslash( $post['auth_method'] ) ) : '';
			$request_type     = isset( $post['requesttype'] ) ? sanitize_text_field( wp_unslash( $post['requesttype'] ) ) : '';
			$auth_method      = MoWpnsConstants::mo2f_convert_method_name( $auth_method, 'pascal_to_cap' );
			$method_handler   = $common_helper->mo2f_get_object( $auth_method );
			$call_to_function = array( $method_handler, 'mo2f_prompt_2fa_' . $request_type . '_dashboard' );
			if ( ! empty( $call_to_function ) ) {
				call_user_func( $call_to_function );
			}
		}

		/**
		 * Selects the method for login.
		 *
		 * @param object $currentuser Current user.
		 * @param string $mo2f_second_factor Twofa method.
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_select_method_for_login( $currentuser, $mo2f_second_factor, $session_id_encrypt, $redirect_to ) {
			$inline_popup  = new Mo2f_Common_Helper();
			$process_login = $inline_popup->mo2f_get_object( $mo2f_second_factor );
			$process_login->mo2f_prompt_2fa_login( $currentuser, $session_id_encrypt, $redirect_to );
		}

		/**
		 * Selects the mfa method.
		 *
		 * @param array $post Post value.
		 * @return void
		 */
		public function mo2f_select_mfa_method( $post ) {
			$session_id                   = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$mo2f_selected_mfactor_method = isset( $post['mo2f_selected_mfactor_method'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_selected_mfactor_method'] ) ) : '';
			$redirect_to                  = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : null;
			$user_id                      = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
			$currentuser                  = get_user_by( 'id', $user_id );
			$this->mo2f_select_method_for_login( $currentuser, $mo2f_selected_mfactor_method, $session_id, $redirect_to );
			exit;

		}

		/**
		 * This will invoke on mobile validation
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_email_verification_success( $post ) {
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( $post['session_id'] ) : null;
			$twofa_status       = isset( $post['twofa_status'] ) ? sanitize_text_field( $post['twofa_status'] ) : '';
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( $post['redirect_to'] ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS !== $twofa_status || TwoFAMoSessions::get_session_var( 'mo2f_is_kba_backup_configured' . $user_id ) ) {
				$inline_helper = new Mo2f_Common_Helper();
				$inline_helper->mo2f_inline_setup_success( $user_id, $redirect_to, $session_id_encrypt );
			} else {
				if ( get_site_option( 'mo2f_remember_device' ) ) {
					do_action(
						'mo2f_enterprise_plan_settings_action',
						'mo2f_remember_device_details',
						array(
							'user_id'     => $user_id,
							'session_id'  => $session_id_encrypt,
							'redirect_to' => $redirect_to,
						)
					);
				}
				$remember_ip_configurations = (array) get_site_option( 'mo2f_remember_ip_configurations' );
				if ( isset( $remember_ip_configurations['mo2f_remember_ip_feature'] ) && $remember_ip_configurations['mo2f_remember_ip_feature'] ) {
					do_action(
						'mo2f_all_inclusive_plan_settings_action',
						'mo2f_remember_ip_details',
						array(
							'user_id'       => $user_id,
							'session_id'    => $session_id_encrypt,
							'redirect_to'   => $redirect_to,
							'rem_ip_choice' => isset( $remember_ip_configurations['mo2f_give_rem_ip_choice'] ) ? $remember_ip_configurations['mo2f_give_rem_ip_choice'] : 0,
						)
					);
				}
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
			exit;
		}

		/**
		 * Handles Skip 2 Factor flow.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_skip_2fa_setup( $post ) {
			global $mo2fdb_queries;
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$session_id_encrypt = sanitize_text_field( $session_id_encrypt );
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! get_user_meta( $user_id, 'mo2f_grace_period_start_time', true ) ) {
				update_user_meta( $user_id, 'mo2f_grace_period_start_time', strtotime( current_datetime()->format( 'h:ia M d Y' ) ) );
			}
			$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
		}

		/**
		 * This will invoke email verification failed.
		 *
		 * @param string $post It will carry the post data .
		 * @return void
		 */
		public function mo2f_email_verification_failed( $post ) {
			MO2f_Utility::mo2f_debug_file( 'MO QR-code/push notification auth denied.' );
			$_SESSION['email_transaction_denied_error'] = 'Your transaction has been denied!';
		}

		/**
		 * Signs in miniOrange user.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_download_backup_codes_inline( $post ) {
			$backups    = isset( $post['mo2f_inline_backup_codes'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_inline_backup_codes'] ) ) : '';
			$codes      = explode( ',', $backups );
			$session_id = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : '';
			$id         = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
			MO2f_Utility::mo2f_download_backup_codes( $id, $codes );
		}

		/**
		 * Finishe inline and logs in.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_finish_inline_and_login( $post ) {
			$pass2fa            = new Miniorange_Password_2Factor_Login();
			$session_id_encrypt = isset( $post['session_id'] ) ? sanitize_text_field( wp_unslash( $post['session_id'] ) ) : null;
			$redirect_to        = isset( $post['redirect_to'] ) ? esc_url_raw( wp_unslash( $post['redirect_to'] ) ) : '';
			$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			exit;
		}
	}
	new Mo2f_Main_Handler();
}
