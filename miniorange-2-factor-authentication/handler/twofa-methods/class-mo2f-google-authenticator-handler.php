<?php
/**
 * This file is contains functions related to GOOGLE AUTHENTICATOR method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Onprem\Mo2f_Inline_Popup;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\Google_Auth_Onpremise;
use TwoFA\Helper\Mo2f_Inline_Common;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\TwoFAMoSessions;

require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';

if ( ! class_exists( 'Mo2f_GOOGLEAUTHENTICATOR_Handler' ) ) {
	/**
	 * Class Mo2f_GOOGLEAUTHENTICATOR_Handler
	 */
	class Mo2f_GOOGLEAUTHENTICATOR_Handler {

		/**
		 * Current Method.
		 *
		 * @var string
		 */
		private $mo2f_current_method;

		/**
		 * Class Mo2f_GOOGLEAUTHENTICATOR_Handler constructor
		 */
		public function __construct() {
			$this->mo2f_current_method = MoWpnsConstants::GOOGLE_AUTHENTICATOR;
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
			global $mo2f_onprem_cloud_obj;
			$gauth_name = get_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$gauth_name = preg_replace( '#^https?://#i', '', $gauth_name );
			$user       = get_user_by( 'ID', $current_user_id );
			$mo2f_onprem_cloud_obj->mo2f_set_google_authenticator( $user, $this->mo2f_current_method, $gauth_name, $session_id );
			$ga_secret = MO2f_Utility::mo2f_get_transient( $session_id, 'secret_ga' );
			$data      = MO2f_Utility::mo2f_get_transient( $session_id, 'ga_qrCode' );
			if ( ! $ga_secret ) {
				$ga_secret = $this->mo2f_create_secret();
				MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $ga_secret );
			}
			global $mo2fdb_queries;
			if ( empty( $data ) || ! MO2F_IS_ONPREM ) {
				$email     = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$ga_secret = $this->mo2f_create_secret();
				$data      = $this->mo2f_geturl( $ga_secret, $gauth_name, $email );
				MO2f_Utility::mo2f_set_transient( $session_id, 'ga_qrCode', $data );
			}
			$microsoft_url = $this->mo2f_geturl( $ga_secret, $gauth_name, '' );
			MO2f_Utility::mo2f_set_transient( $session_id, 'secret_ga', $ga_secret );
			wp_register_script( 'mo2f_qr_code_minjs', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', dirname( dirname( __FILE__ ) ) ), array(), MO2F_VERSION, false );
			$common_helper = new Mo2f_Common_Helper();
			$inline_helper = new Mo2f_Inline_Popup();
			$prev_screen   = $common_helper->mo2f_get_previous_screen_for_inline( $user->ID );
			$common_helper->mo2f_inline_css_and_js();
			wp_print_scripts( 'mo2f_qr_code_minjs' );
			$html  = '<html>
			<head>  <meta charset="utf-8"/>
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1">
			</head>
			<body>';
			$html .= $common_helper->mo2f_google_authenticator_popup_common_html( $gauth_name, $data, $microsoft_url, $ga_secret, $prev_screen, $redirect_to, $session_id );
			$html .= $inline_helper->mo2f_get_inline_hidden_forms( $redirect_to, $session_id, $user->ID );
			$html .= $this->mo2f_get_script( 'inline', $session_id, $redirect_to );
			echo $html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped the necessary in the definition.
			exit;
		}

		/**
		 * Show Google Authenticator configuration prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_setup_dashboard() {
			global $mo2fdb_queries;
			$current_user = wp_get_current_user();
			$mo2fdb_queries->insert_user( $current_user->ID );
			$common_helper = new Mo2f_Common_Helper();
			$gauth_name    = get_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$gauth_obj     = new Google_auth_onpremise();
			$secret        = $this->mo2f_create_secret();
			$issuer        = get_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$email         = $current_user->user_email;
			$ga_url        = $this->mo2f_geturl( $secret, $issuer, $email );
			$microsoft_url = $gauth_obj->mo2f_geturl( $secret, $gauth_name, '' );
			$html          = $common_helper->mo2f_google_authenticator_popup_common_html( $gauth_name, $ga_url, $microsoft_url, $secret, 'dashboard', null, null );
			$html         .= $common_helper->mo2f_get_dashboard_hidden_forms();
			$html         .= $this->mo2f_get_script( 'dashboard', '', '' );
			wp_send_json_success( $html );
		}

		/**
		 * Show E Testing prompt on dashboard.
		 *
		 * @return mixed
		 */
		public function mo2f_prompt_2fa_test_dashboard() {
			$current_user        = wp_get_current_user();
			$mo2fa_login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::ENTER_OTP ) . MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFY_YOURSELF );
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION';
			$login_popup         = new Mo2f_Login_Popup();
			$common_helper       = new Mo2f_Common_Helper();
			$skeleton_values     = $login_popup->mo2f_twofa_login_prompt_skeleton_values( $mo2fa_login_message, $mo2fa_login_status, null, null, $current_user->ID, 'test_2fa', '' );
			$html                = $login_popup->mo2f_get_twofa_skeleton_html( $mo2fa_login_status, $mo2fa_login_message, '', '', $skeleton_values, $this->mo2f_current_method, 'test_2fa' );
			$html               .= $login_popup->mo2f_get_validation_popup_script( 'test_2fa', $this->mo2f_current_method, '', '' );
			$html               .= $common_helper->mo2f_get_test_script();
			wp_send_json_success( $html );
		}

		/**
		 * Gets script for all the flows.
		 *
		 * @param string $twofa_flow Twofa flow.
		 * @param string $session_id Session id.
		 * @param string $redirect_to Redirection to.
		 * @return string
		 */
		public function mo2f_get_script( $twofa_flow, $session_id, $redirect_to ) {
			$common_helper    = new Mo2f_Common_Helper();
			$call_to_function = array( $common_helper, 'mo2f_get_validate_success_response_' . $twofa_flow . '_script' );
			$script           = '<script>
			jQuery("a[href=\'#mo2f_inline_form\']").click(function() {
				jQuery("#mo2f_backto_inline_registration").submit();
			});
			var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";
			function mo2f_validate_gauth(nonce, ga_secret){
				var data = {
					"action"                 : "mo_two_factor_ajax",
					"mo_2f_two_factor_ajax"  : "mo2f_validate_otp_for_configuration",
					"nonce"                  : nonce,	
					"otp_token"              : jQuery("#google_auth_code").val(),
					"ga_secret"              : ga_secret,
					"mo2f_session_id"        : "' . esc_js( $session_id ) . '",
					"redirect_to"            : "' . esc_js( $redirect_to ) . '",
					"mo2f_otp_based_method"  : "' . esc_js( $this->mo2f_current_method ) . '",
				};
				jQuery.ajax({
					url: ajaxurl,
					type: "POST",
					dataType: "json",
					data: data,
					success: function (response) {
						if (response.success) {
							' . call_user_func( $call_to_function ) . '
						} else {
							jQuery("#otpMessage").css("display","block");
							jQuery("#mo2f_gauth_inline_message").text(response.data);
						}
					}
				});
			}
			</script>';
			return $script;
		}

		/**
		 * Creates secret according to the given length.
		 *
		 * @param integer $secret_length Length of the secret.
		 * @throws Exception Throws exception.
		 * @return string
		 */
		public function mo2f_create_secret( $secret_length = 16 ) {
			$valid_chars = $this->mo2f_get_base32_lookup_table();
			// Valid secret lengths are 80 to 640 bits.
			if ( $secret_length < 16 || $secret_length > 128 ) {
				throw new Exception( 'Bad secret length' );
			}
			$secret = '';
			$rnd    = false;
			if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
				$rnd = openssl_random_pseudo_bytes( $secret_length, $crypto_strong );
				if ( ! $crypto_strong ) {
					$rnd = false;
				}
			}
			if ( false !== $rnd ) {
				for ( $i = 0; $i < $secret_length; ++$i ) {
					$secret .= $valid_chars[ ord( $rnd[ $i ] ) & 31 ];
				}
			} else {
				throw new Exception( 'No source of secure random' );
			}
			return $secret;
		}

		/**
		 * Returns the Base32 lookup table.
		 *
		 * @return array
		 */
		public function mo2f_get_base32_lookup_table() {
			return array(
				'A',
				'B',
				'C',
				'D',
				'E',
				'F',
				'G',
				'H',
				'I',
				'J',
				'K',
				'L',
				'M',
				'N',
				'O',
				'P',
				'Q',
				'R',
				'S',
				'T',
				'U',
				'V',
				'W',
				'X',
				'Y',
				'Z',
				'2',
				'3',
				'4',
				'5',
				'6',
				'7',
				'=',  // padding char.
			);
		}

		/**
		 * Returns url according to the secret, issuer and user email id.
		 *
		 * @param string $secret The google authenticator secret.
		 * @param string $issuer The google authenticator name.
		 * @param string $email The email id of user.
		 * @return string
		 */
		public function mo2f_geturl( $secret, $issuer, $email ) {
			// id can be email or name.
			$url  = 'otpauth://totp/';
			$url .= $email . '?secret=' . $secret . '&issuer=' . $issuer;
			return $url;
		}

		/**
		 * Validates google authenticator code.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $session_id_encrypt Session id.
		 * @param object $user User.
		 * @param string $prev_input Prev input.
		 * @param array  $post Post data.
		 * @return void
		 */
		public function mo2f_validate_otp( $otp_token, $session_id_encrypt, $user, $prev_input, $post ) {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$ga_secret = isset( $post['ga_secret'] ) ? sanitize_text_field( wp_unslash( $post['ga_secret'] ) ) : ( isset( $post['session_id'] ) ? MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'secret_ga' ) : null );
			if ( MO2f_Utility::mo2f_check_number_length( $otp_token ) ) {
				$email           = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user->ID );
				$email           = ( empty( $email ) ) ? $user->user_email : $email;
				$google_response = json_decode( $mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
				$this->mo2f_process_inline_ga_validate( $google_response, $user, $email, $ga_secret );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ONLY_DIGITS_ALLOWED ) );
			}
		}

		/**
		 * Processes GA validation at inline.
		 *
		 * @param array  $google_response Response.
		 * @param object $user User.
		 * @param string $email Email id.
		 * @param string $ga_secret GA secrets.
		 * @return void
		 */
		public function mo2f_process_inline_ga_validate( $google_response, $user, $email, $ga_secret ) {
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( MoWpnsConstants::SUCCESS_RESPONSE === $google_response['status'] ) {
					$response = $this->mo2f_update_user_details( $user, $email );
					$this->mo2f_process_update_details_response( $response, $user, $ga_secret );
				}
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_OTP ) );
			}
			wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
		}

		/**
		 * Processes update details.
		 *
		 * @param array  $response Response.
		 * @param object $user User.
		 * @param string $ga_secret GA secrets.
		 * @return void
		 */
		public function mo2f_process_update_details_response( $response, $user, $ga_secret ) {
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
					delete_user_meta( $user->ID, 'mo2f_2FA_method_to_configure' );
					delete_user_meta( $user->ID, 'mo2f_configure_2FA' );
					delete_user_meta( $user->ID, 'mo2f_google_auth' );
					$configured_2fa_method = MoWpnsConstants::GOOGLE_AUTHENTICATOR;
					if ( MO2F_IS_ONPREM ) {
						update_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', $configured_2fa_method );
						$gauth_obj = new Google_Auth_Onpremise();
						$gauth_obj->mo_g_auth_set_secret( $user->ID, $ga_secret );
					}
					update_user_meta( $user->ID, 'mo2f_external_app_type', $configured_2fa_method );
					wp_send_json_success( $configured_2fa_method . ' has been configured successfully.' );
				}
			}
		}

		/**
		 * Updates GA details in database.
		 *
		 * @param object $user Current user.
		 * @param string $email Email.
		 * @return array
		 */
		public function mo2f_update_user_details( $user, $email ) {
			global $mo2f_onprem_cloud_obj;
			delete_user_meta( $user->ID, 'mo2f_user_profile_set' );
			return json_decode( $mo2f_onprem_cloud_obj->mo2f_update_user_info( $user->ID, true, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
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
			$mo2fa_login_message = 'Please enter the one time passcode shown in the <b> Authenticator</b> app.';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION';
			MO2f_Utility::mo2f_debug_file( $mo2fa_login_status . ' User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
			$this->mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id_encrypt );
			exit;
		}

		/**
		 * Show login popup for email.
		 *
		 * @param string $mo2fa_login_message Login message.
		 * @param string $mo2fa_login_status Login status.
		 * @param object $currentuser User id.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session ID.
		 * @return void
		 */
		public function mo2f_show_login_prompt( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id ) {
			$login_popup = new Mo2f_Login_Popup();
			$login_popup->mo2f_show_login_prompt_for_otp_based_methods( $mo2fa_login_message, $mo2fa_login_status, $currentuser, $redirect_to, $session_id, $this->mo2f_current_method );
			exit;
		}

		/**
		 * Validate otp at login.
		 *
		 * @param string $otp_token OTP token.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session ID.
		 * @return mixed
		 */
		public function mo2f_login_validate( $otp_token, $redirect_to, $session_id_encrypt ) {
			global $mo2f_onprem_cloud_obj, $mo2fdb_queries;
			$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( ! $user_id && is_user_logged_in() ) {
				$user_id = wp_get_current_user()->ID;
			}
			$attempts = TwoFAMoSessions::get_session_var( 'mo2f_attempts_before_redirect' );
			$user     = get_user_by( 'id', $user_id );
			$email    = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
			$content  = json_decode( $mo2f_onprem_cloud_obj->validate_otp_token( $this->mo2f_current_method, $email, null, $otp_token, $user ), true );
			if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
				TwoFAMoSessions::add_session_var( 'mo2f_attempts_before_redirect', 3 );
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
			return $content;
		}

	}
			new Mo2f_GOOGLEAUTHENTICATOR_Handler();
}
