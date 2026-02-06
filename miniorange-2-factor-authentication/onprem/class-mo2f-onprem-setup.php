<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2023  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/onprem
 */

namespace TwoFA\OnPrem;

use TwoFA\Traits\Instance;
use TwoFA\Handler\Twofa\Google_Auth_Onpremise;
use TwoFA\Onprem\Mo2f_OnPremRedirect;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\TwoFAMoSessions;
use TwoFA\Handler\Mo2f_All_Inclusive_Premium_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'class-mo2f-onpremredirect.php';

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Mo2f_Onprem_Setup' ) ) {

	/**
	 *  Class contains functions to validate 2FA.
	 */
	class Mo2f_Onprem_Setup {

		use Instance;

		/**
		 * Undocumented function
		 *
		 * @return array
		 */
		public function mo2f_plan_methods() {
			$two_factor_methods_details = array(
				MoWpnsConstants::GOOGLE_AUTHENTICATOR => array(
					'doc'   => MoWpnsConstants::GA_DOCUMENT_LINK,
					'video' => MoWpnsConstants::GA_YOUTUBE,
					'desc'  => 'Use One Time Password shown in <b>Google/Authy/Microsoft Authenticator App</b> to login',
					'crown' => false,
				),
				MoWpnsConstants::OTP_OVER_SMS         => array(
					'doc'   => MoWpnsConstants::OTP_OVER_SMS_DOCUMENT_LINK,
					'video' => MoWpnsConstants::OTP_OVER_SMS_YOUTUBE,
					'desc'  => 'A One Time Passcode (OTP) will be sent to your Phone number',
					'crown' => false,
				),
				MoWpnsConstants::OTP_OVER_EMAIL       => array(
					'doc'   => MoWpnsConstants::OTP_OVER_EMAIL_DOCUMENT_LINK,
					'video' => null,
					'desc'  => 'A One Time Passcode (OTP) will be sent to your Email address',
					'crown' => false,
				),
				MoWpnsConstants::OUT_OF_BAND_EMAIL    => array(
					'doc'   => MoWpnsConstants::EMAIL_VERIFICATION_DOCUMENT_LINK,
					'video' => MoWpnsConstants::EMAIL_VERIFICATION_YOUTUBE,
					'desc'  => 'Accept the verification link sent to your email address',
					'crown' => false,
				),
				MoWpnsConstants::OTP_OVER_TELEGRAM    => array(
					'doc'   => MoWpnsConstants::OTP_OVER_TELEGRAM_DOCUMENT_LINK,
					'video' => MoWpnsConstants::OTP_OVER_TELEGRAM_YOUTUBE,
					'desc'  => 'Enter the One Time Passcode sent to your Telegram account',
					'crown' => false,
				),
				MoWpnsConstants::SECURITY_QUESTIONS   => array(
					'doc'   => MoWpnsConstants::KBA_DOCUMENT_LINK,
					'video' => MoWpnsConstants::KBA_YOUTUBE,
					'desc'  => 'Configure and Answer Three Security Questions to login',
					'crown' => false,
				),
				MoWpnsConstants::OTP_OVER_WHATSAPP    => array(
					'doc'   => MoWpnsConstants::OTP_OVER_WA_DOCUMENT_LINK,
					'video' => null,
					'desc'  => 'Enter the One Time Passcode sent to your WhatsApp number. <a href="' . admin_url( 'admin.php?page=mo_2fa_whatsapp' ) . '" target="_blank" >[Configure your business account]</a>',
					'crown' => false,
				),
			);
			$lv_needed                  = apply_filters( 'mo2f_is_lv_needed', false );
			if ( ! $lv_needed ) { // To do - Remove this after adding OTP Over Whatsapp functionaly.
				$two_factor_methods_details[ MoWpnsConstants::OTP_OVER_WHATSAPP ] = array(
					'doc'   => MoWpnsConstants::OTP_OVER_WA_DOCUMENT_LINK,
					'video' => null,
					'desc'  => 'Enter the One Time Passcode sent to your WhatsApp number.',
					'crown' => true,
				);
			}
			return $two_factor_methods_details;
		}

		/**
		 * Send OTP token according to different authentication methods
		 *
		 * @param string $phone Phone number.
		 * @param string $email Email ID.
		 * @param string $auth_type authentication type.
		 * @param mixed  $current_user current user object.
		 * @param string $session_id session id.
		 * @return mixed
		 */
		public function send_otp_token( $phone, $email, $auth_type, $current_user = null, $session_id = null ) {
			if ( is_null( $current_user ) || ! isset( $current_user ) ) {
				$current_user = wp_get_current_user();
			}
			if ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
				$mo2f_sms_mo2f_curl_redirect = new MocURL();
				$content                     = $mo2f_sms_mo2f_curl_redirect->send_otp_token( $auth_type, $phone, $email );
			} elseif ( ( MoWpnsConstants::OTP_OVER_WHATSAPP === $auth_type ) ) {
				$mo2f_wa_mo2f_all_inclusive_redirect = new Mo2f_All_Inclusive_Premium_Settings();
				$content                             = $mo2f_wa_mo2f_all_inclusive_redirect->mo2f_send_whatsapp_otp_token( $auth_type, $phone, $email );
			} else {
				$mo2f_email_on_prem_redirect = new Mo2f_OnPremRedirect();
				$content                     = $mo2f_email_on_prem_redirect->on_prem_send_redirect( $email, $auth_type, $current_user, $session_id );
			}
			return $content;
		}

		/**
		 * Validate otp token for different authentication methods.
		 *
		 * @param string $auth_type authentication type.
		 * @param string $username user's name.
		 * @param string $transaction_id transaction ID.
		 * @param string $otp_token OTP token.
		 * @param object $current_user current user object.
		 * @param string $session_id session id.
		 * @return mixed
		 */
		public function validate_otp_token( $auth_type, $username, $transaction_id, $otp_token, $current_user = null, $session_id = null ) {
			if ( ! isset( $current_user ) || is_null( $current_user ) ) {
				$current_user = wp_get_current_user();
			}
			if ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
				$mo2f_sms_mo2f_curl_redirect = new MocURL();
				$content                     = $mo2f_sms_mo2f_curl_redirect->validate_otp_token( $transaction_id, $otp_token, $username, $auth_type );
			} elseif ( MoWpnsConstants::OTP_OVER_WHATSAPP === $auth_type ) {
				$mo2f_wa_mo2f_all_inclusive_redirect = new Mo2f_All_Inclusive_Premium_Settings();
				$content                             = $mo2f_wa_mo2f_all_inclusive_redirect->mo_wa_validate_otp_token( $transaction_id, $otp_token );
			} else {
				$mo2f_email_on_prem_redirect = new Mo2f_OnPremRedirect();
				$content                     = $mo2f_email_on_prem_redirect->on_prem_validate_redirect( $auth_type, $otp_token, $transaction_id, $current_user, $session_id );

			}
			return $content;
		}

		/**
		 * Google Authenticator validation
		 *
		 * @param string $useremail user email.
		 * @param string $otptoken google authenticator secret key.
		 * @param string $secret_ga GA secret.
		 * @return string
		 */
		public function mo2f_google_auth_validate( $useremail, $otptoken, $secret_ga ) {

			include_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj = new Google_auth_onpremise();
			$user      = get_user_by( 'email', $useremail );
			$content   = $gauth_obj->mo2f_verify_code( $secret_ga, $otptoken );
			$value     = json_decode( $content, true );
			if ( 'SUCCESS' === $value['status'] ) {
				$user_id = $user->ID;
				$gauth_obj->mo_g_auth_set_secret( $user_id, $secret_ga );
				update_user_meta( $user_id, 'mo2f_2FA_method_to_configure', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
				update_user_meta( $user_id, 'mo2f_external_app_type', MoWpnsConstants::GOOGLE_AUTHENTICATOR );
				global $mo2fdb_queries;
				$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_configured_2FA_method' => MoWpnsConstants::GOOGLE_AUTHENTICATOR ) );
			}
			return $content;
		}

		/**
		 * Undocumented function
		 *
		 * @param int     $user_id user id.
		 * @param boolean $config_status 2fa configuration status.
		 * @param string  $twofa_method 2fa method.
		 * @param string  $user_registration user registration status.
		 * @param string  $twofa_reg_status 2fa registration status.
		 * @param boolean $twofa_by_user enable 2fa by user.
		 * @param string  $email user's email.
		 * @param string  $phone user'phone.
		 * @param string  $whatsapp user'WhatsApp.
		 * @return mixed
		 */
		public function mo2f_update_user_info( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone = null, $whatsapp = null ) {
			if ( isset( $user_id ) ) {
				$update_details = new Miniorange_Password_2Factor_Login();
				$update_details->mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone, $whatsapp );
			}
			return wp_json_encode( array( 'status' => 'SUCCESS' ) );
		}

		/**
		 * Register KbA details on onpremise.
		 *
		 * @param string $email user email.
		 * @param array  $kba_ques_ans questions and answers.
		 * @param string $user_id User ID.
		 * @return mixed
		 */
		public function mo2f_cloud_register_kba( $email, $kba_ques_ans, $user_id = null ) {
			$question_answer = array();
			$total_questions = count( $kba_ques_ans ) / 2;
			for ( $i = 1; $i <= $total_questions; $i++ ) {
				$question_key = 'kba_q' . $i;
				$answer_key   = 'kba_a' . $i;
				if ( isset( $kba_ques_ans[ $question_key ] ) && isset( $kba_ques_ans[ $answer_key ] ) ) {
					$answer = wp_hash_password( strtolower( $kba_ques_ans[ $answer_key ] ) );
					$question_answer[ $kba_ques_ans[ $question_key ] ] = $answer;
				}
			}
			update_user_meta( $user_id, 'mo2f_kba_challenge', $question_answer );
			global $mo2fdb_queries;
			$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_configured_2FA_method' => MoWpnsConstants::SECURITY_QUESTIONS ) );
			$response = wp_json_encode( array( 'status' => 'SUCCESS' ) );
			return $response;
		}

		/**
		 * Verifies the answers of KBA while logging in.
		 *
		 * @param int    $user_id User ID.
		 * @param string $session_id Session ID.
		 * @param string $redirect_to Redirect URL.
		 * @return array
		 */
		public function mo2f_login_kba_verification( $user_id, $session_id, $redirect_to ) {
			$question_answers    = is_array( get_user_meta( $user_id, 'mo2f_kba_challenge', true ) ) ? get_user_meta( $user_id, 'mo2f_kba_challenge', true ) : array();
			$challenge_questions = array_keys( $question_answers );
			$random_keys         = array_rand( $challenge_questions, 2 );
			$challenge_ques1     = $challenge_questions[ $random_keys[0] ];
			$challenge_ques2     = $challenge_questions[ $random_keys[1] ];
			$questions           = array( $challenge_ques1, $challenge_ques2 );
			set_transient( $session_id . 'mo_2_factor_kba_questions', $questions, 300 );
			return $questions;
		}

		/**
		 * Google Authenticator setup
		 *
		 * @param object $user user object.
		 * @param string $session_id session id.
		 * @return void
		 */
		public function mo2f_gauth_setup( $user, $session_id ) {
			global $mo2fdb_queries;
			include_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj        = new Google_auth_onpremise();
			$email            = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
			$onpremise_secret = $gauth_obj->mo2f_create_secret();
			$issuer           = get_site_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
			$url              = $gauth_obj->mo2f_geturl( $onpremise_secret, $issuer, $email );
			update_user_meta( $user->ID, 'mo2f_secret_ga', $onpremise_secret );
			update_user_meta( $user->ID, 'mo2f_ga_qrCode', $url );
		}
		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $user_email user email.
		 * @param string $mo2f_second_factor 2FA method of user.
		 * @param string $current_user Current user.
		 * @param string $session_id_encrypt Session id.
		 * @return mixed
		 */
		public function mo2f_send_verification_link( $user_email, $mo2f_second_factor, $current_user, $session_id_encrypt ) {
			MO2f_Utility::mo2f_debug_file( 'Email verification link has been sent successfully for ' . $mo2f_second_factor . ' Email-' . $user_email );
			$mo2f_on_prem_redirect = new Mo2f_OnPremRedirect();
			if ( ! $mo2f_on_prem_redirect->mo2f_check_if_email_transactions_exists() ) {
				$content = array(
					'status'  => 'ERROR',
					'message' => MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL ),
				);
			} else {
				$content = $mo2f_on_prem_redirect->mo2f_pass2login_push_email_onpremise( $current_user, $user_email, false, $session_id_encrypt );
			}
			$content = is_array( $content ) ? wp_json_encode( $content ) : $content;
			return $content;
		}

		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $request_type user email.
		 * @param string $transaction_id 2FA method of user.
		 * @return mixed
		 */
		public function mo2f_oobe_get_dashboard_script( $request_type, $transaction_id ) {
			$nonce  = wp_create_nonce( 'mo2f-out-of-band-ajax-nonce' );
			$script = '<script>
			jQuery("#mo2f_validateotp_form").css("display","none");
			var calls = 0;
			var requestType = "' . esc_js( $request_type ) . '";
			var ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
			function emailVerificationPoll()
			{
				calls = calls + 1;
				var data = {\'mo2f_out_of_band_email\':\'test_polling\', \'session_id\':\'' . esc_js( $transaction_id ) . '\', \'nonce\':\'' . esc_js( $nonce ) . '\'};
				jQuery.ajax({
					url: ajax_url ,
					type: "POST",
					data: data,
					success: function (result) {
						var status = result;
						if (status == 1) {
							if ( requestType == "configure_2fa") {
								jQuery("#mo2f_2factor_test_prompt_cross").submit();
							} else {
								jQuery("#mo2f_2fa_popup_dashboard").css("display", "none");
								success_msg("You have successfully completed the test.");
							}
						} else if (status == \'ERROR\' || status == \'FAILED\' || status == \'DENIED\' || status == 0) {
							jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
							closeVerification = true;
							error_msg("You have denied the transaction.");
						} else {
							if(calls<300)
							{
								timeout = setTimeout(emailVerificationPoll, 1000);
							}
							else
							{	
								jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
								closeVerification = true;
								error_msg("Session timeout.");
							}
						}
					}
				});
			}
			function success_msg(msg) {
				jQuery("#wpns_nav_message").empty();
				jQuery("#wpns_nav_message").append(
					"<div id=\'notice_div\' class=\'overlay_success\' style=\'z-index:9999\'><div class=\'popup_text\'>" +
						msg +
						"</div></div>"
				);
				window.onload = nav_popup();
			}
			
			function error_msg(msg) {
				jQuery("#wpns_nav_message").empty();
				jQuery("#wpns_nav_message").append(
					"<div id=\'notice_div\' class=\'overlay_error\'><div class=\'popup_text\'>" +
						msg +
						"</div></div>"
				);
				window.onload = nav_popup();
			}
			
			function nav_popup() {
				if (document.getElementById("notice_div") !== null) {
					document.getElementById("notice_div").style.width = "40%";
					setTimeout(function () {
						jQuery("#notice_div").fadeOut("slow");
					}, 3000);
				}
			}</script>';
			return $script;
		}

		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $request_type user email.
		 * @param string $transaction_id 2FA method of user.
		 * @return mixed
		 */
		public function mo2f_oobe_get_login_script( $request_type, $transaction_id ) {
			$nonce  = wp_create_nonce( 'mo2f-out-of-band-ajax-nonce' );
			$script = '<script>
			var calls = 0;
			var flow = "' . esc_js( $request_type ) . '";
			if(flow=="direct_login"){
				emailVerificationPoll();
			}
			var ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
			function emailVerificationPoll()
			{
				calls = calls + 1;
				var data = {\'mo2f_out_of_band_email\':\'login_polling\', \'session_id\':\'' . esc_js( $transaction_id ) . '\',  \'nonce\':\'' . esc_js( $nonce ) . '\'};
				jQuery.ajax({
					url: ajax_url,
					type: "POST",
					data: data,
					success: function (result) {
						var status = result;
						if (status == 1) {
							jQuery("#mo2f_mobile_validation_form").submit();
						} else if (status == \'ERROR\' || status == \'FAILED\' || status == \'DENIED\' || status == 0) {
							jQuery("#mo2f_email_verification_failed_form").submit();
						} else {
							if(calls<300)
							{
								timeout = setTimeout(emailVerificationPoll, 1000);
							}
							else
							{	jQuery("#mo2f_email_verification_failed_form").submit();
							}
						}
					}
				});
			}
</script>';
			return $script;
		}

		/**
		 * Set Google authenticator secret key.
		 *
		 * @param int    $user_id User ID.
		 * @param string $email User email.
		 * @param string $ga_secret Google authenticator secret key.
		 * @return void
		 */
		public function mo2f_set_gauth_secret( $user_id, $email, $ga_secret ) {
			$gauth_obj = new Google_auth_onpremise();
			$gauth_obj->mo_g_auth_set_secret( $user_id, $ga_secret );
		}
		/**
		 * Send Email verification link to the user's email.
		 *
		 * @param object $current_user User object.
		 * @return void
		 */
		public function mo2f_email_verification_call( $current_user ) {
			$mo2f_on_prem_redirect = new Mo2f_OnPremRedirect();
			$mo2f_on_prem_redirect->mo2f_pass2login_push_email_onpremise( $current_user, $current_user->user_email, true, $session_id );
		}
		/**
		 * Set email verification for user.
		 *
		 * @param object $current_user currently logged in user object.
		 * @param string $selected_method Selected 2fa method by user.
		 * @return array
		 */
		public function mo2f_cloud_set_oob_email( $current_user, $selected_method ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->mo2f_update_user_details(
				$current_user->ID,
				array(
					'mo2f_EmailVerification_config_status' => true,
					'mo2f_configured_2FA_method'           => MoWpnsConstants::OUT_OF_BAND_EMAIL,
					'mo2f_user_email'                      => $current_user->user_email,
					'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
				)
			);
			$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';

			return array(
				'mo2fa_login_status' => $mo2fa_login_status,
			);
		}
		/**
		 * Set OTP over email method for a user.
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selcted 2fa method.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $redirect_to redirect url.
		 * @return void
		 */
		public function mo2f_set_otp_over_email( $current_user, $selected_method, $session_id_encrypt, $redirect_to ) {
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$email           = $current_user->user_email;
			$twofactor_login->mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user );
		}
		/**
		 * Set google authenticator for a user
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selected 2FA method.
		 * @param string $google_account_name Google authenticator app name.
		 * @param string $session_id_encrypt encrypted session id.
		 * @return void
		 */
		public function mo2f_set_google_authenticator( $current_user, $selected_method, $google_account_name, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$mo2fdb_queries->mo2f_update_user_details(
				$current_user->ID,
				array(
					'mo2f_configured_2FA_method' => $selected_method,
				)
			);
			include_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			$gauth_obj = new Google_auth_onpremise();

			$onpremise_secret              = $gauth_obj->mo2f_create_secret();
			$issuer                        = $google_account_name;
			$url                           = $gauth_obj->mo2f_geturl( $onpremise_secret, $issuer, $current_user->user_email );
			$mo2f_google_auth              = array();
			$mo2f_google_auth['ga_qrCode'] = $url;
			$mo2f_google_auth['ga_secret'] = $onpremise_secret;
			update_user_meta( $current_user->ID, 'mo2f_secret_ga', $onpremise_secret );
			update_user_meta( $current_user->ID, 'mo2f_ga_qrCode', $url );
		}

		/**
		 * Set 2fa method for a user
		 *
		 * @param object $current_user currently logged in user.
		 * @param string $selected_method 2fa method seleced by user.
		 * @return mixed
		 */
		public function mo2f_set_user_two_fa( $current_user, $selected_method ) {
			$response = array(
				'mo2fa_login_status'  => MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS,
				'mo2fa_login_message' => '',
			);
			return $response;
		}
		/**
		 * Set google authenticator on WordPress user profile.
		 *
		 * @param object $user user object.
		 * @return mixed
		 */
		public function mo2f_user_profile_ga_setup( $user ) {
			global $mo2f_main_dir;
			include_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
				$gauth_obj        = new Google_auth_onpremise();
				$ga_secret        = $gauth_obj->mo2f_create_secret();
				$issuer           = get_site_option( 'mo2f_google_appname', DEFAULT_GOOGLE_APPNAME );
				$url              = $gauth_obj->mo2f_geturl( $ga_secret, $issuer, $user->user_email );
				$mo2f_google_auth = array(
					'ga_qrCode' => $url,
					'ga_secret' => $ga_secret,
				);
				update_user_meta( $user->ID, 'mo2f_google_auth', wp_json_encode( $mo2f_google_auth ) );
				$otpcode = $gauth_obj->mo2f_get_code( $ga_secret );
				$data    = isset( $mo2f_google_auth ) ? $mo2f_google_auth['ga_qrCode'] : null;
				wp_enqueue_script( 'mo_wpns_min_qrcode_script', $mo2f_main_dir . '/includes/jquery-qrcode/jquery-qrcode.min.js', array(), MO2F_VERSION, false );
				echo '<div class="mo2f_gauth_column mo2f_gauth_left" >';
				echo '<div class="mo2f_gauth"  data-qrcode=' . esc_attr( $data ) . '></div>';
				echo '</div>';
				return $ga_secret;
		}
		/**
		 * Get 2FA method of a user.
		 *
		 * @param object $user user object.
		 * @return string
		 */
		public function mo2f_get_user_2ndfactor( $user ) {
			global $mo2fdb_queries;
			return $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
		}
	}

}
