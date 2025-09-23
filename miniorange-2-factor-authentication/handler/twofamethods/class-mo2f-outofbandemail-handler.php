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
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Helper\MocURL;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_OUTOFBANDEMAIL_Handler' ) ) {
	/**
	 * Class Mo2f_OUTOFBANDEMAIL_Handler
	 */
	class Mo2f_OUTOFBANDEMAIL_Handler {

		use Instance;

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * It will strore the transaction id
		 *
		 * @var string .
		 */
		private $mo2f_transactionid;

		/**
		 * Class Mo2f_OUTOFBANDEMAIL_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::OUT_OF_BAND_EMAIL;
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
			$current_user  = get_user_by( 'id', $current_user_id );
			$common_helper = new Mo2f_Common_Helper();
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
				$html       .= $mo2f_onprem_cloud_obj->mo2f_oobe_get_login_script( 'Inline', $transaction_id );
				$html       .= $common_helper->mo2f_get_hidden_forms_for_ooba( $redirect_to, $session_id, $current_user->ID );
				echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped the necessary in the definition.
			}
			exit;
		}

		/**
		 * Preprocessing before prompting email verification on dashboard.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard( $session_id_encrypt ) {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$current_user = wp_get_current_user();
			$email        = $current_user->user_email;
			$json_string = stripslashes( $mo2f_onprem_cloud_obj->mo2f_send_link( $current_user, $this->mo2f_current_method, $email, $session_id_encrypt ) );
			$content     = json_decode( $json_string, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $content['status'] ) {
					$this->mo2f_handle_success_dashboard( $email, $current_user->ID, $content, 'configure_2fa', $session_id_encrypt );
				}
			}
			$this->mo2f_handle_error_dashboard( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL );
		}

		/**
		 * Show OOB Testing prompt on dashboard.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard( $session_id_encrypt ) {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$current_user    = wp_get_current_user();
			$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );
			$response        = json_decode( $mo2f_onprem_cloud_obj->mo2f_send_link( $current_user, $this->mo2f_current_method, $mo2f_user_email, $session_id_encrypt ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					$this->mo2f_handle_success_dashboard( $mo2f_user_email, $current_user->ID, $response, 'test_2fa', $session_id_encrypt );
				}
			} else {
				$this->mo2f_handle_error_dashboard( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL );
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
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $currentuser->ID );
			$response        = json_decode( $mo2f_onprem_cloud_obj->mo2f_send_link( $currentuser, $this->mo2f_current_method, $mo2f_user_email, $session_id_encrypt ), true );
			$transaction_id  = isset( $response['txId'] ) ? $response['txId'] : '';
			if ( json_last_error() === JSON_ERROR_NONE && MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
					$content             = $this->mo2f_handle_success_login( $mo2f_user_email, $currentuser, $response, $session_id_encrypt );
					$mo2fa_login_message = $content['login_message'];
					$mo2fa_login_status  = $content['login_status'];
			} else {
				$mo2fa_login_status  = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
				$mo2fa_login_message = $this->mo2f_get_error_message( $currentuser );
			}
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt, $transaction_id );
			exit;
		}

		/**
		 * Prompt Inline for OOBE.
		 *
		 * @param object $currentuser current user.
		 * @param string $session_id_encrypt Session ID.
		 * @param object $redirect_to Redirection url.
		 * @return void
		 */
		public function mo2f_prompt_2fa_inline( $currentuser, $session_id_encrypt, $redirect_to ) {
			global $mo2f_onprem_cloud_obj;
			$mo2f_user_email     = $currentuser->user_email;
			$response            = json_decode( $mo2f_onprem_cloud_obj->mo2f_send_link( $currentuser, $this->mo2f_current_method, $mo2f_user_email, $session_id_encrypt ), true );
			$mo2f_transaction_id = isset( $response['txId'] ) ? $response['txId'] : '';
			if ( json_last_error() === JSON_ERROR_NONE && MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
				$content             = $this->mo2f_handle_success_login( $mo2f_user_email, $currentuser, $response, $session_id_encrypt );
				$mo2fa_login_message = $content['login_message'];
				$mo2fa_login_status  = $content['login_status'];
			} else {
				$mo2fa_login_message = MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL;
				$mo2fa_login_status  = MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT;
			}
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt, $mo2f_transaction_id );
			exit;
		}


		/**
		 * Sends otp on email.
		 *
		 * @param string $email Email ID.
		 * @param string $session_id Session id.
		 * @param object $current_user Current user.
		 * @return mixed
		 */
		public function mo2f_send_otp( $email, $session_id, $current_user ) {
			global $mo2f_onprem_cloud_obj, $mo_wpns_utility;
			$content = json_decode( $mo2f_onprem_cloud_obj->mo2f_send_link( $current_user, $this->mo2f_current_method, $email, $session_id_encrypt ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate otp token */
				if ( 'ERROR' === $content['status'] ) {
					wp_send_json_error( $content['message'] );
				} elseif ( MoWpnsConstants::SUCCESS_RESPONSE === $content['status'] ) {
					MO2f_Utility::mo2f_debug_file( 'Email verification link has been sent successfully for ' . $this->mo2f_current_method . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
					$mo2fa_login_message = 'An email verification link has been sent to ' . $email . '.';
					wp_send_json_success( $mo2fa_login_message );
				}
			} else {
				$mo2fa_login_message = user_can( $current_user->ID, 'manage_options' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
				wp_send_json_error( $mo2fa_login_message );
			}
		}

		/**
		 * Handles success at login.
		 *
		 * @param string $mo2f_user_email User email.
		 * @param object $current_user User.
		 * @param array  $content Content.
		 * @param string $session_id_encrypt Session id.
		 * @return array
		 */
		public function mo2f_handle_success_login( $mo2f_user_email, $current_user, $content, $session_id_encrypt ) {
			global $mo_wpns_utility;
			$mo2f_hidden_email   = MO2f_Utility::mo2f_get_hidden_email( $mo2f_user_email );
			$mo2fa_login_message = 'An email verification link has been sent to ' . $mo2f_hidden_email . '.';
			set_transient( $session_id_encrypt . 'mo2f_transactionId', $content['txId'], 300 );
			$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OOB_EMAIL;
			MO2f_Utility::mo2f_debug_file( 'Email verification link has been sent successfully for ' . $this->mo2f_current_method . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $current_user->ID . ' Email-' . $current_user->user_email );
			return array(
				'login_status'  => $mo2fa_login_status,
				'login_message' => $mo2fa_login_message,
			);
		}

		/**
		 * Returns error message.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public function mo2f_handle_error_dashboard( $message ) {
			wp_send_json_error( $message );
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
			MO2f_Utility::mo2f_debug_file( 'An error occured while sending the link - Email - ' . $mo2f_user_email );
			$mo2fa_login_message = user_can( $id, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
			$inline_popup->prompt_user_to_select_2factor_mthod_inline( $id, $mo2fa_login_message, $redirect_to, $session_id );

		}

		/**
		 * Handles success at dashboard.
		 *
		 * @param string $mo2f_user_email Email.
		 * @param int    $user_id User id.
		 * @param mixed  $response Response.
		 * @param string $request_type Request type.
		 * @param string $session_id_encrypt Session id.
		 * @return void
		 */
		public function mo2f_handle_success_dashboard( $mo2f_user_email, $user_id, $response, $request_type, $session_id_encrypt ) {
			global $mo_wpns_utility, $mo2f_onprem_cloud_obj;
			MO2f_Utility::mo2f_debug_file( 'Email verification link has been sent successfully for ' . $this->mo2f_current_method . ' User_IP - ' . $mo_wpns_utility->get_client_ip() . ' User_Id - ' . $user_id . ' Email - ' . $mo2f_user_email );
			$mo2f_hidden_email   = MO2f_Utility::mo2f_get_hidden_email( $mo2f_user_email );
			$mo2fa_login_message = 'An email verification link has been sent to ' . $mo2f_hidden_email . ' . ';
			set_transient( $session_id_encrypt . 'mo2f_transactionId', $response['txId'], 300 );

			$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OOB_EMAIL;
			$login_popup        = new Mo2f_Login_Popup();
			$common_helper      = new Mo2f_Common_Helper();
			$skeleton_values    = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $user_id, 'test_2fa', '', $session_id_encrypt  );
			$html               = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
			$html              .= $login_popup->mo2f_get_validation_popup_script( 'test_2fa', $this->mo2f_current_method, '', '' );
			$html              .= $mo2f_onprem_cloud_obj->mo2f_oobe_get_dashboard_script( $request_type, $response['txId'] );
			$html              .= '<script>emailVerificationPoll()</script>';
			$html              .= $common_helper->mo2f_get_dashboard_hidden_forms();
			wp_send_json_success( $html );
		}

		/**
		 * Show login popup for email.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $current_user Current user.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @param string $transaction_id Transaction ID.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $current_user, $redirect_to, $session_id_encrypt, $transaction_id ) {
			global $mo2f_onprem_cloud_obj;
			$login_popup     = new Mo2f_Login_Popup();
			$common_helper   = new Mo2f_Common_Helper();
			$skeleton_values = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, 'login_2fa', '', $session_id_encrypt );
			$html            = $login_popup->mo2f_twofa_authentication_login_prompt( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, $session_id_encrypt, $skeleton_values, $this->mo2f_current_method );
			$html           .= $common_helper->mo2f_get_hidden_forms_login( $redirect_to, $session_id_encrypt, $mo2fa_login_status, $mo2fa_login_message, $this->mo2f_current_method, $current_user->ID );
			if ( MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT !== $mo2fa_login_status ) {
				$html .= $mo2f_onprem_cloud_obj->mo2f_oobe_get_login_script( 'direct_login', $transaction_id );
			}
			$html .= $common_helper->mo2f_get_hidden_script_login();
			$html .= $common_helper->mo2f_get_hidden_forms_for_ooba( $redirect_to, $session_id_encrypt, $current_user->ID );
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}

		/**
		 * Proccess email verification link data.
		 *
		 * @param string $useridget User id.
		 * @param string $txidget Transaction id.
		 * @param string $accesstokenget Access token.
		 * @return void
		 */
		public function mo2f_process_link_validation( $useridget, $txidget, $accesstokenget ) {
			global $main_dir;
			$otp_token     = get_site_option( $useridget );
			$txidstatus    = get_site_option( $txidget );
			$useridd       = $useridget . 'D';
			$otp_tokend    = get_site_option( $useridd );
			$mo2f_dir_name = dirname( __FILE__ );
			$mo2f_dir_name = explode( 'wp-content', $mo2f_dir_name );
			$mo2f_dir_name = explode( 'handler', $mo2f_dir_name[1] );
			$response      = $this->mo2f_validate_link( $txidstatus, $txidget, $otp_token, $otp_tokend, $accesstokenget, $useridget, $useridd );
			$custom_logo   = get_site_option( 'mo2f_custom_logo', 'miniOrange2.png' );
			$popup_args    = array(
				'head'         => $response['head'],
				'body'         => $response['body'],
				'color'        => $response['color'],
				'bg_color'     => '#FFFFFF',
				'branding_img' => 'background-color: #d5e3d9;',
				'logo_url'     => esc_url( $main_dir . 'includes/images/' . $custom_logo ),
			);
			$popup_args    = apply_filters( 'mo2f_enterprise_plan_settings_filter', $popup_args, 'mo2f_custom_email_verification_popup_args', $popup_args );
			$display_popup = new Mo2f_Login_Popup();
			$display_popup->mo2f_display_email_verification( $popup_args );
			exit;
		}

		/**
		 * Validates email link.
		 *
		 * @param string $txidstatus Txid status.
		 * @param string $txidget Txid.
		 * @param string $otp_token OTP token.
		 * @param string $otp_tokend OTP token d.
		 * @param string $accesstokenget Get access token.
		 * @param string $useridget Get user id.
		 * @param string $useridd User id.
		 * @return array
		 */
		public function mo2f_validate_link( $txidstatus, $txidget, $otp_token, $otp_tokend, $accesstokenget, $useridget, $useridd ) {
			$head  = __( 'You are not authorized to perform this action', 'miniorange-2-factor-authentication' );
			$body  = __( 'Please contact to your admin', 'miniorange-2-factor-authentication' );
			$color = 'red';
			if ( 3 === (int) $txidstatus ) {
				$time                   = 'time' . $txidget;
				$current_time_in_millis = round( microtime( true ) * 1000 );
				$generatedtimeinmillis  = get_site_option( $time );
				$difference             = ( $current_time_in_millis - $generatedtimeinmillis ) / 1000;
				if ( $difference <= 300 ) {
					if ( $accesstokenget === $otp_token ) {
						$common_helper = new Mo2f_Common_Helper();
						$common_helper->mo2f_update_current_user_status( $txidget );
						update_site_option( $txidget, 1 );
						$body  = __( 'Transaction has been successfully validated . Please continue with the transaction.', 'miniorange-2-factor-authentication' );
						$head  = __( 'TRANSACTION SUCCESSFUL', 'miniorange-2-factor-authentication' );
						$color = 'green';
					} elseif ( $accesstokenget === $otp_tokend ) {
						update_site_option( $txidget, 0 );
						$body = __( 'Transaction has been Canceled . Please try Again . ', 'miniorange-2-factor-authentication' );
						$head = __( 'TRANSACTION DENIED', 'miniorange-2-factor-authentication' );
					}
				} else {
					update_site_option( $txidget, 0 );
				}
				delete_site_option( $useridget );
				delete_site_option( $useridd );
				delete_site_option( $time );
			}
			$content = array(
				'body'  => $body,
				'head'  => $head,
				'color' => $color,
			);
			return $content;
		}

		/**
		 * Updates GA details in database.
		 *
		 * @param object $currentuser Current user.
		 * @param string $mo2f_user_email User email.
		 * @return void
		 */
		public function mo2f_update_user_details( $currentuser, $mo2f_user_email ) {
			global $mo2f_onprem_cloud_obj;
			$mocurl = new MocURL();
			$mocurl->mo_create_user( $currentuser, $mo2f_user_email );
			delete_user_meta( $currentuser->ID, 'mo2f_user_profile_set' );
			$mo2f_onprem_cloud_obj->mo2f_update_user_info( $currentuser->ID, true, MoWpnsConstants::OUT_OF_BAND_EMAIL, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $mo2f_user_email, null );
		}

		/**
		 * Handles polling.
		 *
		 * @param string $txidpost Transaction id.
		 * @return void
		 */
		public function mo2f_handle_polling( $txidpost ) {
			global $mo2fdb_queries;
			$status = (int) get_site_option( $txidpost );
			if ( 1 === $status || 0 === $status ) {
				$user_details = get_transient( $txidpost );
				if ( 1 === $status && ! $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_EmailVerification_config_status', $user_details['user_id'] ) ) {
					$this->mo2f_update_user_details( get_user_by( 'id', $user_details['user_id'] ), $user_details['user_email'] );
				}
				delete_site_option( $txidpost );
			}
			wp_send_json( $status );
		}

		/**
		 * Returns error message.
		 *
		 * @param object $currentuser Current user.
		 * @return string
		 */
		public function mo2f_get_error_message( $currentuser ) {
			$mo2fa_login_message = user_can( $currentuser->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
			return $mo2fa_login_message;
		}
	}
}
