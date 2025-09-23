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
 * @package        miniorange-2-factor-authentication/api
 */

namespace TwoFA\Cloud;

use TwoFA\Helper\Mo2f_Api;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Traits\Instance;
use TwoFA\Helper\MocURL;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsMessages;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Customer_Cloud_Setup' ) ) {

	/**
	 *  Class contains functions to setup customer with miniOrange.
	 */
	class Customer_Cloud_Setup {
		use Instance;

		/**
		 * Email id of user.
		 *
		 * @var $email string.
		 */
		public $email;
		/**
		 * Phone number of user.
		 *
		 * @var int.
		 */
		public $phone;
		/**
		 * Customer key of user.
		 *
		 * @var string
		 */
		public $customer_key;
		/**
		 * Transaction id of the customer to send OTP via SMS or Email.
		 *
		 * @var string
		 */
		public $transaction_id;
		/**
		 * Instantiation of Mo2f_Api class.
		 *
		 * @var object
		 */
		private $mo2f_api;

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			$this->mo2f_api = Mo2f_Api::instance();
		}
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
				),
				MoWpnsConstants::OTP_OVER_SMS         => array(
					'doc'   => MoWpnsConstants::OTP_OVER_SMS_DOCUMENT_LINK,
					'video' => MoWpnsConstants::OTP_OVER_SMS_YOUTUBE,
					'desc'  => 'A One Time Passcode (OTP) will be sent to your Phone number',
				),
				MoWpnsConstants::OTP_OVER_EMAIL       => array(
					'doc'   => MoWpnsConstants::OTP_OVER_EMAIL_DOCUMENT_LINK,
					'video' => null,
					'desc'  => 'A One Time Passcode (OTP) will be sent to your Email address',
				),
				MoWpnsConstants::OUT_OF_BAND_EMAIL    => array(
					'doc'   => MoWpnsConstants::EMAIL_VERIFICATION_DOCUMENT_LINK,
					'video' => MoWpnsConstants::EMAIL_VERIFICATION_YOUTUBE,
					'desc'  => 'Accept the verification link sent to your email address',
				),
				MoWpnsConstants::OTP_OVER_TELEGRAM    => array(
					'doc'   => MoWpnsConstants::OTP_OVER_TELEGRAM_DOCUMENT_LINK,
					'video' => MoWpnsConstants::OTP_OVER_TELEGRAM_YOUTUBE,
					'desc'  => 'Enter the One Time Passcode sent to your Telegram account',
				),
				MoWpnsConstants::SECURITY_QUESTIONS   => array(
					'doc'   => MoWpnsConstants::KBA_DOCUMENT_LINK,
					'video' => MoWpnsConstants::KBA_YOUTUBE,
					'desc'  => 'Configure and Answer Three Security Questions to login',
				),
				MoWpnsConstants::OTP_OVER_WHATSAPP    => array(
					'doc'   => MoWpnsConstants::OTP_OVER_WA_DOCUMENT_LINK,
					'video' => null,
					'desc'  => 'Enter the One Time Passcode sent to your WhatsApp number.',
					'crown' => false,
				),
			);
			return $two_factor_methods_details;
		}

		/**
		 * Function to get customer key of user.
		 *
		 * @return string
		 */
		public function get_customer_key() {

			$url = MO_HOST_NAME . '/moas/rest/customer/key';

			$email        = get_site_option( 'mo2f_email' );
			$password     = get_site_option( 'mo2f_password' );
			$fields       = array(
				'email'    => $email,
				'password' => $password,
			);
			$field_string = wp_json_encode( $fields );

			$headers = array(
				'Content-Type'  => 'application/json',
				'charset'       => 'UTF-8',
				'Authorization' => 'Basic',
			);

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string );

			return $content;
		}

		/**
		 * Update user info on cloud.
		 *
		 * @param int     $user_id User id.
		 * @param string  $config_status 2FA configuration status.
		 * @param string  $twofa_method 2fa method of user.
		 * @param string  $user_registration User registrtion status.
		 * @param string  $twofa_reg_status 2fa registration status.
		 * @param boolean $twofa_by_user Enable 2fa by users.
		 * @param string  $email User's email.
		 * @param string  $phone User's phone.
		 * @param string  $transaction_name 2fa transactin name.
		 * @param boolean $enable_admin_2fa 2fa enable for admin.
		 * @return mixed
		 */
		public function mo2f_update_user_info( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone = null, $transaction_name = null, $enable_admin_2fa = null ) {
			$mocurl  = new MocURL();
			$content = $mocurl->mo2f_update_user_info( $email, $twofa_method, $phone, $transaction_name, $enable_admin_2fa );
			if ( isset( $user_id ) ) {
				$update_details = new Miniorange_Password_2Factor_Login();
				$update_details->mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registration, $twofa_reg_status, $twofa_by_user, $email, $phone );
			}

			return $content;
		}

		/**
		 * Function to send otp to the user via miniOrange service.
		 *
		 * @param string $phone Phone.
		 * @param string $email Email ID.
		 * @param string $auth_type Authentication method of the user.
		 * @param object $currentuser Contains details of current user.
		 * @return string
		 */
		public function send_otp_token( $phone, $email, $auth_type, $currentuser ) {
			$mo2f_send_otp = new MocURL();
			$content       = $mo2f_send_otp->send_otp_token( $auth_type, $phone, $email );
			return $content;
		}

		/**
		 * Function to validate the otp token.
		 *
		 * @param string $auth_type Authentication method of user.
		 * @param string $username Username of user.
		 * @param string $transaction_id Transaction id which is used to validate the sent otp token.
		 * @param string $otp_token OTP token received by user.
		 * @param object $current_user Contains details of current user.
		 * @return string
		 */
		public function validate_otp_token( $auth_type, $username, $transaction_id, $otp_token, $current_user = null ) {
			$mo2f_validate_otp = new MocURL();
			$content           = $mo2f_validate_otp->validate_otp_token( $transaction_id, $otp_token, $username, $auth_type );
			return $content;
		}
		/**
		 * Function to raise support query.
		 *
		 * @param string $q_email Email id of customer to be sent to the query.
		 * @param int    $q_phone Phone number of customer to be sent to the query.
		 * @param string $query Query raised by the customer.
		 * @return boolean
		 */
		public function submit_contact_us( $q_email, $q_phone, $query ) {

			$url = MO_HOST_NAME . '/moas/rest/customer/contact-us';
			global $user;
			$user              = wp_get_current_user();
			$is_nc_with_1_user = MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' ) && MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NNC', 'site_option' );
			$is_ec_with_1_user = ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' );

			$customer_feature = '';

			if ( $is_ec_with_1_user ) {
				$customer_feature = 'V1';
			} elseif ( $is_nc_with_1_user ) {
				$customer_feature = 'V3';
			}
			global $mo_wpns_utility;
			$query        = '[WordPress 2 Factor Authentication Plugin: ' . $customer_feature . ' - V ' . MO2F_VERSION . ' ]: ' . $query;
			$fields       = array(
				'firstName' => $user->user_firstname,
				'lastName'  => $user->user_lastname,
				'company'   => isset( $_SERVER['SERVER_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null,
				'email'     => $q_email,
				'ccEmail'   => 'mfasupport@xecurify.com',
				'phone'     => $q_phone,
				'query'     => $query,
			);
			$field_string = wp_json_encode( $fields );

			$headers = array(
				'Content-Type'  => 'application/json',
				'charset'       => 'UTF-8',
				'Authorization' => 'Basic',
			);

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string );

			return true;
		}
		/**
		 * Google Authenticator setup
		 *
		 * @param object $user user object.
		 * @param string $session_id session id.
		 * @return void
		 */
		public function mo2f_gauth_setup( $user, $session_id ) {
			$cloud_setup = new Mo2f_Cloud_Challenge();
			$cloud_setup->mo2f_gauth_setup( $user, $session_id );
		}

		/**
		 * Sends email verification link to the users email.
		 *
		 * @param string $user_email user email.
		 * @param string $mo2f_second_factor 2FA method of user.
		 * @param string $current_user Current User.
		 * @return mixed
		 */
		public function mo2f_send_verification_link( $user_email, $mo2f_second_factor, $current_user ) {
			$content = $this->send_otp_token( null, $user_email, $mo2f_second_factor, $current_user );
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
			$script = '<script>
            var calls = 0;
            var requestType = "' . esc_js( $request_type ) . '";
            var transId = "' . esc_js( $transaction_id ) . '";
            emailVerificationPoll();
            function emailVerificationPoll()
            {
                calls = calls + 1;
                var data = {"txId":"' . esc_js( $transaction_id ) . '"};
                jQuery.ajax({
                    url: "' . esc_url( MO_HOST_NAME ) . '/moas/api/auth/auth-status",
                    type: "POST",
                    dataType: "json",
                    data: JSON.stringify(data), // Stringify JSON data
                    contentType: "application/json; charset=utf-8",
                    success: function (result) {
                        var status = result.status;
                        if (status === "SUCCESS") {
                            if (requestType === "configure_2fa") {
                                jQuery("#mo2f_2factor_test_prompt_cross").submit();
                            } else {
                                jQuery("#mo2f_2fa_popup_dashboard").css("display", "none");
                                success_msg("You have successfully completed the test.");
                            }
                        } else if (status === "ERROR" || status === "FAILED" || status === "DENIED" || status === 0) {
                            jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
                            closeVerification = true;
                            error_msg("You have denied the transaction.");
                        } else {
                            if (calls < 300)
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
          </script>';
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
			$script = '<script>
            var calls = 0;
            var requestType = "' . esc_js( $request_type ) . '";
            var transId = "' . esc_js( $transaction_id ) . '";
            emailVerificationPoll();
            function emailVerificationPoll()
            {
                calls = calls + 1;
                var data = {"txId":"' . esc_js( $transaction_id ) . '"};
                jQuery.ajax({
                    url: "' . esc_url( MO_HOST_NAME ) . '/moas/api/auth/auth-status",
                    type: "POST",
                    dataType: "json",
                    data: JSON.stringify(data), // Stringify JSON data
                    contentType: "application/json; charset=utf-8",
                    success: function (result) {
                        var status = result.status;
                   		if (status === "SUCCESS") {
							jQuery("#mo2f_mobile_validation_form").submit();
						} else if (status === "ERROR" || status === "FAILED" || status === "DENIED" || status === 0) {
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
		 * @return mixed
		 */
		public function mo2f_set_gauth_secret( $user_id, $email, $ga_secret ) {
			$google_auth     = new Mo2f_Cloud_Utility();
			$google_response = json_decode( $google_auth->mo2f_google_auth_service( $email, 'miniOrangeAu' ), true );
			return $google_response;
		}

		/**
		 * Set Up email verification
		 *
		 * @param object $current_user current user object.
		 * @return void
		 */
		public function mo2f_email_verification_call( $current_user ) {
			$email        = $current_user->user_email;
			$content      = $this->send_otp_token( null, $email, MoWpnsConstants::OUT_OF_BAND_EMAIL, $current_user );
			$response     = json_decode( $content, true );
			$show_message = new MoWpnsMessages();
			if ( json_last_error() === JSON_ERROR_NONE ) { /* Generate out of band email */
				if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $response['message'] ), 'ERROR' );
				} else {
					if ( 'SUCCESS' === $response['status'] ) {
						update_user_meta( $current_user->ID, 'mo2f_transactionId', $response['txId'] );
						update_site_option( 'mo2f_transactionId', $response['txId'] );
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::VERIFICATION_EMAIL_SENT ) . '<b> ' . $email . '</b>. ' . MoWpnsMessages::lang_translate( MoWpnsMessages::ACCEPT_LINK_TO_VERIFY_EMAIL ), 'SUCCESS' );
					} else {
						$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::ERROR_DURING_PROCESS ), 'ERROR' );
					}
				}
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_REQ ), 'ERROR' );

			}
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
			$current_user    = get_userdata( $current_user->ID );
			$email           = $current_user->user_email;
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$response        = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );

			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fa_login_message = $response['message'] . 'Skip the two-factor for login';
			} else {

				$mo2fdb_queries->mo2f_update_user_details(
					$current_user->ID,
					array(
						'mo2f_email_verification_status' => true,
						'mo2f_configured_2FA_method'     => MoWpnsConstants::OUT_OF_BAND_EMAIL,
						'mo2f_user_email'                => $email,
					)
				);
				$mo2fa_login_status  = 'MO_2_FACTOR_SETUP_SUCCESS';
				$mo2fa_login_message = '';
			}
			return array(
				'mo2fa_login_status'  => $mo2fa_login_status,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}
		/**
		 * Set OTP over email method for a user.
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selcted 2fa method.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $redirect_to redirect url.
		 * @return mixed
		 */
		public function mo2f_set_otp_over_email( $current_user, $selected_method, $session_id_encrypt, $redirect_to ) {
			global $mo2fdb_queries;
			$twofactor_login = new Miniorange_Password_2Factor_Login();
			$email           = $current_user->user_email;
			$response        = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] || null === $response ) {
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fa_login_message = isset( $response['message'] ) ? $response['message'] : 'An unknown error occured.';
				$mo2fa_login_message = $mo2fa_login_message . ' Please try again or select another method.';
				return array(
					'mo2fa_login_status'  => $mo2fa_login_status,
					'mo2fa_login_message' => $mo2fa_login_message,
				);
			} else {
				$user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );
				if ( ! empty( $user_email ) && ! is_null( $user_email ) ) {
					$email = $user_email;
				}
				$twofactor_login->mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user );
			}
		}
		/**
		 * Set google authenticator for a user
		 *
		 * @param object $current_user user object.
		 * @param string $selected_method selected 2FA method.
		 * @param string $google_account_name Google authenticator app name.
		 * @param string $session_id_encrypt encrypted session id.
		 * @return array
		 */
		public function mo2f_set_google_authenticator( $current_user, $selected_method, $google_account_name, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$email     = $current_user->user_email;
			$tempemail = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID );

			if ( ! isset( $tempemail ) && ! is_null( $tempemail ) && ! empty( $tempemail ) ) {
				$email = $tempemail;
			}
			$twofactor_login     = new Miniorange_Password_2Factor_Login();
			$response            = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			$mo2fa_login_message = '';
			$mo2fa_login_status  = '';
			if ( isset( $response['status'] ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_message = $response['message'];
				$mo2fa_login_status  = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
			} else {
				$mo2fdb_queries->mo2f_update_user_details(
					$current_user->ID,
					array(
						'mo2f_configured_2FA_method' => $selected_method,
					)
				);
				$google_auth     = new Mo2f_Cloud_Utility();
				$google_response = json_decode( $google_auth->mo2f_google_auth_service( $email, $google_account_name ), true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					if ( 'SUCCESS' === $google_response['status'] ) {
						$mo2f_google_auth              = array();
						$mo2f_google_auth['ga_qrCode'] = $google_response['qrCodeData'];
						$mo2f_google_auth['ga_secret'] = $google_response['secret'];
						update_user_meta( $current_user->ID, 'mo2f_secret_ga', $mo2f_google_auth['ga_secret'] );
						update_user_meta( $current_user->ID, 'mo2f_ga_qrCode', $mo2f_google_auth['ga_qrCode'] );
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please register with miniOrange to configure 2 Factor plugin.', 'miniorange-2-factor-authentication' );
					}
				}
			}
			return array(
				'mo2fa_login_status'  => $mo2fa_login_status,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}

		/**
		 * Set 2fa method for a user
		 *
		 * @param object $current_user currently logged in user.
		 * @param string $selected_method 2fa method seleced by user.
		 * @return array
		 */
		public function mo2f_set_user_two_fa( $current_user, $selected_method ) {
			global $mo2fdb_queries;
			$twofactor_login     = new Miniorange_Password_2Factor_Login();
			$current_user        = get_userdata( $current_user->ID );
			$email               = $current_user->user_email;
			$mo2fa_login_message = '';
			$mo2fa_login_status  = '';
			$response            = $twofactor_login->create_user_in_miniorange( $current_user->ID, $email, $selected_method );
			if ( ! is_null( $response ) && 'ERROR' === $response['status'] ) {
				$mo2fa_login_message = $response['message'];
			} else {
				$mo2fa_login_status = MoWpnsConstants::MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS;
				$mo2fdb_queries->mo2f_update_user_details( $current_user->ID, array( 'mo2f_configured_2FA_method' => $selected_method ) );
			}

			return array(
				'mo2fa_login_status'  => $mo2fa_login_status,
				'mo2fa_login_message' => $mo2fa_login_message,
			);
		}
		/**
		 * Set google authenticator on WordPress user profile.
		 *
		 * @param object $user user object.
		 * @return mixed
		 */
		public function mo2f_user_profile_ga_setup( $user ) {
			if ( ! get_user_meta( $user->ID, 'mo2f_google_auth', true ) ) {
				Mo2f_Cloud_Utility::mo2f_get_g_a_parameters( $user );
			}
			$mo2f_google_auth = get_user_meta( $user->ID, 'mo2f_google_auth', true );
			$data             = isset( $mo2f_google_auth['ga_qrCode'] ) ? $mo2f_google_auth['ga_qrCode'] : null;
			$ga_secret        = isset( $mo2f_google_auth['ga_secret'] ) ? $mo2f_google_auth['ga_secret'] : null;
			echo '<br><div id="displayQrCode">
			<img id="mo2f_gauth" style="line-height: 0;background:white;" src="data:image/jpg;base64,' . esc_attr( $data ) . '" />
			</div>';
			return $ga_secret;
		}


		/**
		 * KBA verification function.
		 *
		 * @param int    $user_id User id.
		 * @param string $session_id Session id.
		 * @param string $redirect_to Redirect url.
		 * @return mixed
		 */
		public function mo2f_login_kba_verification( $user_id, $session_id, $redirect_to ) {
			$kba_verification = new Mo2f_Cloud_Validate();
			return $kba_verification->mo2f_login_kba_verification( $user_id, $session_id, $redirect_to );
		}

		/**
		 * This function returns status of 2nd factor
		 *
		 * @param object $user object containing user details.
		 * @return string
		 */
		public function mo2f_get_user_2ndfactor( $user ) {
			global $mo2fdb_queries;
			$mo2f_user_email        = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
			$auth_type              = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
			$is_telegram_configured = MoWpnsConstants::OTP_OVER_TELEGRAM === $auth_type;
			if ( $is_telegram_configured ) {
				$mo2f_second_factor = $auth_type;
			} else {
				$enduser  = new MocURL();
				$userinfo = json_decode( $enduser->mo2f_get_userinfo( $mo2f_user_email ), true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					if ( 'ERROR' === $userinfo['status'] ) {
						$mo2f_second_factor = 'NONE';
					} elseif ( 'SUCCESS' === $userinfo['status'] ) {
						$mo2f_second_factor = $userinfo['authType'];
					} elseif ( 'FAILED' === $userinfo['status'] ) {
						$mo2f_second_factor = 'USER_NOT_FOUND';
					} else {
						$mo2f_second_factor = 'NONE';
					}
				} else {
					$mo2f_second_factor = 'NONE';
				}
			}

			return $mo2f_second_factor;
		}

		/**
		 * Register KBA on cloud.
		 *
		 * @param string $email user email.
		 * @param string $question1 kba question 1.
		 * @param string $question2 kba question 2.
		 * @param string $question3 kba question 3.
		 * @param string $answer1 kba answer 1.
		 * @param string $answer2 kba answer 2.
		 * @param string $answer3 kba answer 3.
		 * @return mixed
		 */
		public function mo2f_cloud_register_kba( $email, $question1, $question2, $question3, $answer1, $answer2, $answer3 ) {
			$response = new Mo2f_Cloud_Utility();

			return $response->mo2f_cloud_register_kba( $email, $question1, $question2, $question3, $answer1, $answer2, $answer3 );
		}
		/**
		 * Google Authenticator validation
		 *
		 * @param string $useremail user email.
		 * @param string $otptoken google authenticator secret key.
		 * @param string $secret otp token.
		 * @return string
		 */
		public function mo2f_google_auth_validate( $useremail, $otptoken, $secret ) {
			$customer = new Mo2f_Cloud_Validate();
			$content  = $customer->mo2f_google_auth_validate( $useremail, $otptoken, $secret );

			return $content;
		}

		/**
		 * Creates user in miniOrange.
		 *
		 * @param object $user user.
		 * @param string $email Email address.
		 * @return void
		 */
		public function mo2f_create_user_in_miniorange( $user, $email ) {
			global $mo2fdb_queries;
			$mocurl       = new MocURL();
			$show_message = new MoWpnsMessages();
			$check_user   = json_decode( $mocurl->mo_check_user_already_exist( $email ), true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'ERROR' === $check_user['status'] ) {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $check_user['message'] ), 'ERROR' );
					return;
				} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND' ) === 0 ) {
					$mo2fdb_queries->mo2f_update_user_details(
						$user->ID,
						array(
							'user_registration_with_miniorange' => 'SUCCESS',
							'mo2f_user_email' => $email,
						)
					);
					update_site_option( base64_encode( 'totalUsersCloud' ), intval( get_site_option( base64_encode( 'totalUsersCloud' ) ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				} elseif ( strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) === 0 ) {
					$content = json_decode( $mocurl->mo_create_user( $user, $email ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
							update_site_option( base64_encode( 'totalUsersCloud' ), intval( get_site_option( base64_encode( 'totalUsersCloud' ) ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
							$mo2fdb_queries->mo2f_update_user_details(
								$user->ID,
								array(
									'user_registration_with_miniorange' => 'SUCCESS',
									'mo2f_user_email' => $email,
								)
							);
						}
					}
				} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) === 0 ) {
					$mo2fa_login_message = esc_html__( 'The email associated with your account is already registered in miniOrange. Please Choose another email or contact miniOrange.', 'miniorange-2-factor-authentication' );
					$show_message->mo2f_show_message( $mo2fa_login_message, 'ERROR' );
					return;
				}
			}

		}
	}
}
