<?php
/**
 * This file is contains functions related to Telegram method.
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
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Helper\MocURL;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_TELEGRAM_Handler' ) ) {
	/**
	 * Class Mo2f_TELEGRAM_Handler
	 */
	class Mo2f_TELEGRAM_Handler {

		use Instance;

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * Class Mo2f_TELEGRAM_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::OTP_OVER_TELEGRAM;
		}



		/**
		 * Process Inline data for SMS.
		 *
		 * @param string $session_id Sessiong ID.
		 * @param string $redirect_to Redirection url.
		 * @param object $current_user_id Current user ID.
		 * @param string $mo2fa_login_message Login message.
		 * @return void
		 */
		public function mo2f_prompt_2fa_setup_inline( $session_id, $redirect_to, $current_user_id, $mo2fa_login_message ) {
			$interface     = new MO2f_Cloud_Onprem_Interface();
			$current_user  = get_user_by( 'id', $current_user_id );
			$content       = $interface->mo2f_set_user_two_fa( $current_user, $this->mo2f_current_method );
			$inline_helper = new Mo2f_Inline_Popup();
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_inline_css_and_js();
			$prev_screen = $common_helper->mo2f_get_previous_screen_for_inline( $current_user_id );
			$skeleton    = $common_helper->mo2f_telegram_common_skeleton( $current_user_id );
			$html        = '<div class="mo2f_modal" tabindex="-1" role="dialog">
			<div class="mo2f-modal-backdrop"></div>
			<div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">';
			$html       .= $common_helper->mo2f_otp_based_methods_configuration_screen( $skeleton, $this->mo2f_current_method, $content['mo2fa_login_message'], $current_user_id, $redirect_to, $session_id, $prev_screen );
			$html       .= '</div></div>';
			$html       .= $inline_helper->mo2f_get_inline_hidden_forms( $redirect_to, $session_id, $current_user->ID );
			$html       .= $common_helper->mo2f_get_script_for_otp_based_methods( 'inline' );
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped the necessary in the definition.
			exit;
		}

		/**
		 * Sends OTP to users phone.
		 *
		 * @param string $telegram_id Phone number.
		 * @param string $session_id_encrypt Sessiond Id.
		 * @param object $user Uesr.
		 * @param object $message message.
		 * @return void
		 */
		public function mo2f_send_otp( $telegram_id, $session_id_encrypt, $user, $message ) {
			$mocurl      = new MocURL();
			$telegram_id = $telegram_id ?? get_user_meta( $user->ID, 'mo2f_chat_id', true );
			TwoFAMoSessions::add_session_var( 'mo2f_temp_chatID', $telegram_id );
			$content = $mocurl->mo2f_send_telegram_otp( $telegram_id );
			$this->mo2f_process_inline_send_otp( $content, $user, $session_id_encrypt, $message );
		}

		/**
		 * Processes send otp at inline.
		 *
		 * @param array  $content Content.
		 * @param object $user User.
		 * @param string $session_id_encrypt Sessiond Id.
		 * @param string $message Message.
		 * @return void
		 */
		public function mo2f_process_inline_send_otp( $content, $user, $session_id_encrypt, $message ) {
			global $mo2fdb_queries;
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'ERROR' === $content['status'] ) {
					wp_send_json_error( $content['message'] );
				} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $content['txId'] );
					TwoFAMoSessions::add_session_var( 'mo2f_otp_send_true', true );
					wp_send_json_success( $message . 'your telegram number. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF ) );
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
				}
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ) );
			}
		}

		/**
		 * Validates OTP for SMS.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $session_id_encrypt Transaction id.
		 * @param object $user Current user.
		 * @param object $prev_chat_id Previous input.
		 * @param array  $post Post data.
		 * @return void
		 */
		public function mo2f_validate_otp( $otp_token, $session_id_encrypt, $user, $prev_chat_id, $post ) {
			global $mo2fdb_queries;
			$current_chat_id = TwoFAMoSessions::get_session_var( 'mo2f_temp_chatID' );
			$this->mo2f_mismatch_input_check( $current_chat_id, $prev_chat_id );
			$mo2f_transaction_id = TwoFAMoSessions::get_session_var( 'mo2f_transactionId' );
			$mocurl              = new MocURL();
			$content             = $mocurl->mo2f_validate_telegram_code( $otp_token, $mo2f_transaction_id );
			$this->mo2f_process_inline_validate_otp( $content, $user );
		}

		/**
		 * Checks input mismatch.
		 *
		 * @param string $current_chat_id Current chat id.
		 * @param string $prev_chat_id Previous chat id.
		 * @return void
		 */
		public function mo2f_mismatch_input_check( $current_chat_id, $prev_chat_id ) {
			if ( $current_chat_id !== $prev_chat_id ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( 'The current chat ID doesn\'t match the one used to send the OTP.' ) );
			}
		}

		/**
		 * Process inline validate otp.
		 *
		 * @param array  $content Content.
		 * @param object $user User.
		 * @return void
		 */
		public function mo2f_process_inline_validate_otp( $content, $user ) {
			if ( 'ERROR' === $content['status'] ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( $content['message'] ) );
			} elseif ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
				if ( isset( $user->ID ) ) {
					$update_details = new Miniorange_Password_2Factor_Login();
					$update_details->mo2fa_update_user_details( $user->ID, true, $this->mo2f_current_method, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $user->user_email, null );
				}
				update_user_meta( $user->ID, 'mo2f_chat_id', TwoFAMoSessions::get_session_var( 'mo2f_temp_chatID' ) );
				TwoFAMoSessions::unset_session( 'mo2f_temp_chatID' );
				TwoFAMoSessions::unset_session( 'mo2f_otp_token' );
				TwoFAMoSessions::unset_session( 'mo2f_telegram_time' );
				TwoFAMoSessions::unset_session( 'mo2f_otp_send_true' );
				wp_send_json_success( 'Your 2FA method has been set successfully.' );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) );
			}
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
			global $mo_wpns_utility;
			$telegram_id = get_user_meta( $currentuser->ID, 'mo2f_chat_id', true );
			$mocurl      = new MocURL();
			$content     = $mocurl->mo2f_send_telegram_otp( $telegram_id );
			if ( json_last_error() === JSON_ERROR_NONE && MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					$mo2fa_login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::OTP_SENT ) . 'your telegram number. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF );
					$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM;
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $content['txId'] );
					MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
			} else {
				$mo2fa_login_status  = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
				$mo2fa_login_message = $this->mo2f_get_error_message();
			}
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt );
			exit;
		}

		/**
		 * Show login popup for Telegram.
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
		 * Returns error message.
		 *
		 * @return string
		 */
		public function mo2f_get_error_message() {
			return MoWpnsMessages::ERROR_DURING_PROCESS;
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
			global $mo_wpns_utility;
			$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$mo2f_transaction_id = TwoFAMoSessions::get_session_var( 'mo2f_transactionId' );
			$attempts            = TwoFAMoSessions::get_session_var( 'mo2f_attempts_before_redirect' );
			$mocurl              = new MocURL();
			$content             = $mocurl->mo2f_validate_telegram_code( $otp_token, $mo2f_transaction_id );
			if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
				TwoFAMoSessions::add_session_var( 'mo2f_attempts_before_redirect', 3 );
				wp_send_json_success( 'VALIDATED_SUCCESS' );
			} else {
				$mo_wpns_utility->mo2f_handle_attempt_validation( $attempts, 'INVALID_OTP' );
			}
		}

		/**
		 * Show Telegram configuration prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard() {
			global $mo2fdb_queries;
			$current_user = wp_get_current_user();
			$common_helper = new Mo2f_Common_Helper();
			$skeleton      = $common_helper->mo2f_telegram_common_skeleton( $current_user->ID );
			$html          = $common_helper->mo2f_otp_based_methods_configuration_screen( $skeleton, $this->mo2f_current_method, '', $current_user->ID, '', '', 'dashboard' );
			$html         .= $this->mo2f_get_hidden_forms_dashboard( $common_helper );
			$html         .= $common_helper->mo2f_get_script_for_otp_based_methods( 'dashboard' );
			wp_send_json_success( $html );
		}

		/**
		 * Show SMS configuration prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard() {
			global $mo_wpns_utility;
			$currentuser = wp_get_current_user();
			$telegram_id = get_user_meta( $currentuser->ID, 'mo2f_chat_id', true );
			$mocurl      = new MocURL();
			$content     = $mocurl->mo2f_send_telegram_otp( $telegram_id );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					$mo2fa_login_message = 'Please enter the one time passcode sent on your<b> Telegram</b> app.';
					$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_TELEGRAM;
					TwoFAMoSessions::add_session_var( 'mo2f_transactionId', $content['txId'] );
					MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$login_popup     = new Mo2f_Login_Popup();
					$common_helper   = new Mo2f_Common_Helper();
					$skeleton_values = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $currentuser->ID, 'test_2fa', '' );
					$html            = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
					$html           .= $login_popup->mo2f_get_validation_popup_script( 'test_2fa', $this->mo2f_current_method, '', '' );
					$html           .= $common_helper->mo2f_get_test_script();
					wp_send_json_success( $html );
				}
			}
			$mo2fa_login_message = $this->mo2f_get_error_message();
			wp_send_json_error( $mo2fa_login_message );
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
}
