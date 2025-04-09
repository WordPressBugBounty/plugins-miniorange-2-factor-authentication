<?php
/**
 * This file is contains functions related to KBA method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler\TwofaMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_EMAIL_Handler' ) ) {
	/**
	 * Class Mo2f_EMAIL_Handler
	 */
	class Mo2f_EMAIL_Handler {

		use Instance;

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * Class Mo2f_EMAIL_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::OTP_OVER_EMAIL;
		}

		/**
		 * Process Inline data for EMAIL.
		 *
		 * @param string $session_id Sessiong ID.
		 * @param string $redirect_to Redirection url.
		 * @param object $current_user_id Current user ID.
		 * @param string $mo2fa_login_message Login message.
		 * @return void
		 */
		public function mo2f_prompt_2fa_setup_inline( $session_id, $redirect_to, $current_user_id, $mo2fa_login_message ) {
			global $mo2f_onprem_cloud_obj;
			$current_user = get_user_by( 'id', $current_user_id );
			if ( ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_email_change', 'site_option' ) ) {
				$this->mo2f_prompt_2fa_inline( $current_user, $session_id, $redirect_to );
			} else {
				$content       = $mo2f_onprem_cloud_obj->mo2f_set_user_two_fa( $current_user, $this->mo2f_current_method );
				$inline_helper = new Mo2f_Inline_Popup();
				$common_helper = new Mo2f_Common_Helper();
				$common_helper->mo2f_inline_css_and_js();
				$prev_screen = $common_helper->mo2f_get_previous_screen_for_inline( $current_user->ID );
				$skeleton    = $common_helper->mo2f_email_common_skeleton( $current_user_id );
				$html        = '<div class="mo2f_modal" tabindex="-1" role="dialog">
				<div class="mo2f-modal-backdrop"></div>
				<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">';
				$html       .= $common_helper->mo2f_otp_based_methods_configuration_screen( $skeleton, $this->mo2f_current_method, $content['mo2fa_login_message'], $current_user_id, $redirect_to, $session_id, $prev_screen );
				$html       .= '</div></div>';
				$html       .= $inline_helper->mo2f_get_inline_hidden_forms( $redirect_to, $session_id, $current_user->ID );
				$html       .= $common_helper->mo2f_get_script_for_otp_based_methods( 'inline' );
				echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped the necessary in the definition.
			}
			exit;
		}

		/**
		 * Show E Testing prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard() {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj, $mo_wpns_utility;
			$current_user    = wp_get_current_user();
			$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );
			$response        = json_decode( $mo2f_onprem_cloud_obj->send_otp_token( null, $mo2f_user_email, $this->mo2f_current_method, $current_user ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					MO2f_Utility::mo2f_debug_file( ' OTP has been sent successfully over email. User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
					$mo2f_hidden_email   = MO2f_Utility::mo2f_get_hidden_email( $mo2f_user_email );
					$mo2fa_login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . ' ' . $mo2f_hidden_email . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF );
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $response['txId'] );
					$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL;
					$login_popup        = new Mo2f_Login_Popup();
					$common_helper      = new Mo2f_Common_Helper();
					$skeleton_values    = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, 'test_2fa', '' );
					$html               = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
					$html              .= $login_popup->mo2f_get_validation_popup_script( 'test_2fa', $this->mo2f_current_method, '', '' );
					$html              .= $common_helper->mo2f_get_test_script();
					wp_send_json_success( $html );
				}
			}
			$mo2fa_login_message = $this->mo2f_get_error_message( $current_user );
			wp_send_json_error( $mo2fa_login_message );
		}

		/**
		 * Process login data for KBA.
		 *
		 * @param object $currentuser current user.
		 * @param string $session_id_encrypt Session ID.
		 * @param object $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_prompt_2fa_login( $currentuser, $session_id_encrypt, $redirect_to ) {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $currentuser->ID );
			$content         = json_decode( $mo2f_onprem_cloud_obj->send_otp_token( null, $mo2f_user_email, $this->mo2f_current_method, $currentuser ), true );
			if ( json_last_error() === JSON_ERROR_NONE && MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					$content             = $this->mo2f_handle_success_login( $mo2f_user_email, $currentuser, $content );
					$mo2fa_login_message = $content['login_message'];
					$mo2fa_login_status  = $content['login_status'];
			} else {
				$mo2fa_login_status  = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
				$mo2fa_login_message = $this->mo2f_get_error_message( $currentuser );
			}
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt );
			exit;
		}

		/**
		 * Prompt inline for OTP Over Email.
		 *
		 * @param object $currentuser current user.
		 * @param string $session_id_encrypt Session ID.
		 * @param object $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_prompt_2fa_inline( $currentuser, $session_id_encrypt, $redirect_to ) {
			global $mo2f_onprem_cloud_obj;
			$mo2f_user_email = $currentuser->user_email;
			$content         = json_decode( $mo2f_onprem_cloud_obj->send_otp_token( null, $mo2f_user_email, $this->mo2f_current_method, $currentuser ), true );
			if ( json_last_error() === JSON_ERROR_NONE && MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
				$content             = $this->mo2f_handle_success_login( $mo2f_user_email, $currentuser, $content );
				$mo2fa_login_message = $content['login_message'];
				$mo2fa_login_status  = $content['login_status'];
			} else {
				$mo2fa_login_message = MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL;
				$mo2fa_login_status  = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
			}
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt );
			exit;
		}

		/**
		 * Handles success at login.
		 *
		 * @param string $mo2f_user_email User email.
		 * @param object $current_user User.
		 * @param array  $content Content.
		 * @return array
		 */
		public function mo2f_handle_success_login( $mo2f_user_email, $current_user, $content ) {
			global $mo_wpns_utility;
			$mo2f_hidden_email   = MO2f_Utility::mo2f_get_hidden_email( $mo2f_user_email );
			$mo2fa_login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . ' ' . $mo2f_hidden_email . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF );
			TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $content['txId'] );
			$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL;
			MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
			return array(
				'login_status'  => $mo2fa_login_status,
				'login_message' => $mo2fa_login_message,
			);
		}

		/**
		 * Handles error at login.
		 *
		 * @param int    $id user id.
		 * @param string $mo2f_user_email User email.
		 * @param string $session_id Session id.
		 * @param string $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_handle_error_login( $id, $mo2f_user_email, $session_id, $redirect_to ) {
			$inline_popup = new Mo2f_Inline_Popup();
			MO2f_Utility::mo2f_debug_file( 'An error occured while sending the OTP- Email-' . $mo2f_user_email );
			$mo2fa_login_message = user_can( $id, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
			$inline_popup->prompt_user_to_select_2factor_mthod_inline( $id, $mo2fa_login_message, $redirect_to, $session_id );

		}

		/**
		 * Validate otp at login.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_login_validate( $otp_token, $redirect_to, $session_id_encrypt ) {
			global $mo2f_onprem_cloud_obj;
			$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$current_user        = get_user_by( 'id', $user_id );
			$mo2f_transaction_id = TwoFAMoSessions::get_session_var( 'mo2f_transactionId' );
			$attempts            = TwoFAMoSessions::get_session_var( 'mo2f_attempts_before_redirect' );
			$content             = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( $this->mo2f_current_method, null, $mo2f_transaction_id, $otp_token, $current_user ), true );
			if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
				TwoFAMoSessions::add_session_var( 'mo2f_attempts_before_redirect', 3 );
				$common_helper = new Mo2f_Common_Helper();
				if ( ! $common_helper->mo2f_is_2fa_set( $current_user->ID ) ) {
					$this->mo2f_update_user_details( $current_user, $current_user->user_email );
				}
				wp_send_json_success( 'VALIDATED_SUCCESS' );
			} else {
				if ( $attempts > 1 || 'disabled' === $attempts ) {
					TwoFAMoSessions::add_session_var( 'mo2f_attempts_before_redirect', $attempts - 1 );
					wp_send_json_error( 'INVALID_OTP' );
				} else {
					TwoFAMoSessions::unset_session( 'mo2f_attempts_before_redirect' );
					wp_send_json_error( 'LIMIT_EXCEEDED' );
				}
			}
		}

		/**
		 * Returns error message.
		 *
		 * @param object $currentuser Current user.
		 * @return string
		 */
		public function mo2f_get_error_message( $currentuser ) {
			if ( user_can( $currentuser->ID, 'administrator' ) ) {
				return MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL;
			} else {
				return MoWpnsMessages::ERROR_DURING_PROCESS;
			}
		}

		/**
		 * Sends otp on email.
		 *
		 * @param string $email Email ID.
		 * @param string $session_id Session id.
		 * @param object $current_user Current user.
		 * @param string $message Current user.
		 * @return mixed
		 */
		public function mo2f_send_otp( $email, $session_id, $current_user, $message ) {
			global $mo2f_onprem_cloud_obj, $mo2fdb_queries;
			$email   = $email ?? $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );
			$content = json_decode( $mo2f_onprem_cloud_obj->send_otp_token( null, $email, $this->mo2f_current_method, $current_user ), true );
			$this->mo2f_process_send_otp_content( $content, $current_user, $message, $email );
		}

		/**
		 * Processes send otp.
		 *
		 * @param array  $content Content.
		 * @param object $current_user User.
		 * @param string $message message.
		 * @param string $email Entered email.
		 * @return void
		 */
		public function mo2f_process_send_otp_content( $content, $current_user, $message, $email ) {
			if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate otp token */
				if ( MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					MO2f_Utility::mo2f_debug_file( 'OTP has been sent successfully over Email' );
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $content['txId'] );
					TwoFAMoSessions::add_session_var( 'mo2f_otp_send_true', true );
					wp_send_json_success( $message . ' ' . MO2f_Utility::mo2f_get_hidden_email( $email ) . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF ) );
				}
			}
			$mo2fa_login_message = user_can( $current_user->ID, 'manage_options' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
			wp_send_json_error( $mo2fa_login_message );
		}

		/**
		 * Validates OTP for SMS.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $session_id Session id.
		 * @param object $user Current user.
		 * @param object $prev_input Previous input.
		 * @param array  $post Post data.
		 * @return void
		 */
		public function mo2f_validate_otp( $otp_token, $session_id, $user, $prev_input, $post ) {
			global $mo2f_onprem_cloud_obj;
			$user_email = TwoFAMoSessions::get_session_var( 'tempRegEmail' );
			$this->mo2f_mismatch_input_check( $user_email, $prev_input );
			$mo2f_transaction_id = TwoFAMoSessions::get_session_var( 'mo2f_transactionId' );
			$content             = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( $this->mo2f_current_method, null, $mo2f_transaction_id, $otp_token, $user ), true );
			$this->mo2f_process_validate_otp( $content, $user, $user_email );
		}

		/**
		 * Processes validate OTP.
		 *
		 * @param array  $content Content.
		 * @param object $user user.
		 * @param string $user_email User email.
		 * @return void
		 */
		public function mo2f_process_validate_otp( $content, $user, $user_email ) {
			if ( 'ERROR' === $content['status'] ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( $content['message'] ) );
			} elseif ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
				$response = $this->mo2f_update_user_details( $user, $user_email );
				$this->mo2f_process_update_details_response( $response, $user );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) );
			}
		}

		/**
		 * Processes updates user details response.
		 *
		 * @param array  $response Response.
		 * @param object $user User.
		 * @return void
		 */
		public function mo2f_process_update_details_response( $response, $user ) {
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'ERROR' === $response['status'] ) {
					wp_send_json_error( MoWpnsMessages::lang_translate( $response['message'] ) );
				} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
					TwoFAMoSessions::unset_session( 'mo2f_otp_send_true' );
					wp_send_json_success( 'Your 2FA method has been set successfully.' );
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ) );
				}
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
			}

		}

		/**
		 * Checks input mismatch.
		 *
		 * @param string $temp_email Temp email.
		 * @param string $prev_input previously entered email.
		 * @return void
		 */
		public function mo2f_mismatch_input_check( $temp_email, $prev_input ) {
			if ( $temp_email !== $prev_input ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'The current email ID doesn\'t match the one used to send the OTP.' ) );
			}
		}

		/**
		 * Show login prompt for email.
		 *
		 * @param array  $response Send otp response.
		 * @param object $current_user Current user.
		 * @param string $session_id Session ID.
		 * @param string $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_process_inline_send_otp( $response, $current_user, $session_id, $redirect_to ) {
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					$mo2fa_login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . ' ' . MO2f_Utility::mo2f_get_hidden_email( $current_user->user_email ) . '. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::SET_THE_2FA );
					$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL;
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $response['txId'] );
					$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id );
				} else {
					$mo2fa_login_message = user_can( $current_user->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
					$inline_popup        = new Mo2f_Inline_Popup();
					$inline_popup->prompt_user_to_select_2factor_mthod_inline( $current_user->ID, $mo2fa_login_message, $redirect_to, $session_id );
				}
			}
		}

		/**
		 * Show login popup for email.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt ) {
			$login_popup = new Mo2f_Login_Popup();
			$login_popup->mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt, $this->mo2f_current_method );
			exit;
		}

		/**
		 * Updates Email details in database.
		 *
		 * @param object $user Current user.
		 * @param string $email Email.
		 * @return array
		 */
		public function mo2f_update_user_details( $user, $email ) {
			global $mo2f_onprem_cloud_obj;
			delete_user_meta( $user->ID, 'mo2f_user_profile_set' );
			return json_decode( $mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, $this->mo2f_current_method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
		}

		/**
		 * Show Email configuration prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard() {
			global $mo2fdb_queries;
			$current_user = wp_get_current_user();
			$common_helper = new Mo2f_Common_Helper();
			$skeleton      = $common_helper->mo2f_email_common_skeleton( $current_user->ID );
			$html          = $common_helper->mo2f_otp_based_methods_configuration_screen( $skeleton, $this->mo2f_current_method, '', $current_user->ID, '', '', 'dashboard' );
			$html         .= $this->mo2f_get_hidden_forms_dashboard( $common_helper );
			$html         .= $common_helper->mo2f_get_script_for_otp_based_methods( 'dashboard' );
			wp_send_json_success( $html );
		}

		/**
		 * Get hidden forms for dashboard.
		 *
		 * @param object $common_helper Common helper object.
		 * @return string
		 */
		public function mo2f_get_hidden_forms_dashboard( $common_helper ) {
			return $common_helper->mo2f_get_dashboard_hidden_forms();
		}

	}
	new Mo2f_EMAIL_Handler();
}
