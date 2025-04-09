<?php
/** It enables user to log in through mobile authentication as an additional layer of security over password.
 *
 * @package        miniorange-2-factor-authentication/handler/twofa
 * @license        http://www.gnu.org/copyleft/gpl.html MIT/Expat, see LICENSE.php
 */

namespace TwoFA\Handler\Twofa;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\Miniorange_Mobile_Login;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Database\Mo2fDB;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Cloud\Customer_Cloud_Setup;
use TwoFA\Helper\Mo2f_Login_Popup;
use TwoFA\Traits\Instance;
use WP_Error;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require 'class-miniorange-mobile-login.php';

if ( ! class_exists( 'Miniorange_Password_2Factor_Login' ) ) {
	/**
	 * Class will help to set two factor on login
	 */
	class Miniorange_Password_2Factor_Login {

		use Instance;

		/**
		 *  It will store the KBA Question
		 *
		 * @var string .
		 */
		private $mo2f_kbaquestions;

		/**
		 * For user id variable
		 *
		 * @var string
		 */
		private $mo2f_user_id;

		/**
		 * It will strore the transaction id
		 *
		 * @var string .
		 */
		private $mo2f_transactionid;

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
		 * Constructor of the class
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
		}

		/**
		 * This function will invoke to prompt 2fa on login
		 *
		 * @return null
		 */
		public function mo2f_miniorange_sign_in() {
			global $mo_wpns_utility;
			$nonce = isset( $_POST['mo2f_inline_nonce'] ) ? sanitize_key( $_POST['mo2f_inline_nonce'] ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-inline-login-nonce' ) ) {
				$error = new WP_Error();
				return $error;
			}
			$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$password           = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$session_id_encrypt = isset( $_POST['session_id'] ) ? wp_unslash( $_POST['session_id'] ) : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
			$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
			$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			if ( $mo_wpns_utility->check_empty_or_null( $email ) || $mo_wpns_utility->check_empty_or_null( $password ) ) {
				$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::REQUIRED_FIELDS );
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;
			}
			$this->mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt );
		}

		/**
		 * It is for getting the user id or current customer
		 *
		 * @param string $user_id  It will carry the user id.
		 * @param string $email It will carry the email address.
		 * @param string $password It will store the password .
		 * @param string $redirect_to It will carry the redirect url.
		 * @param string $session_id_encrypt  It will carry the session id.
		 * @return void
		 */
		public function mo2f_inline_get_current_customer( $user_id, $email, $password, $redirect_to, $session_id_encrypt ) {
			global $mo2fdb_queries;
			$customer     = new MocURL();
			$content      = $customer->get_customer_key( $email, $password );
			$customer_key = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $customer_key['status'] ) {
					if ( isset( $customer_key['phone'] ) ) {
						update_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
						$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_user_phone' => $customer_key['phone'] ) );
					}
					update_option( 'mo2f_email', $email );
					$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					$this->mo2f_inline_save_success_customer_config( $user_id, $email, $id, $api_key, $token, $app_secret );
					$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS );
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;
				} else {
					$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo_2factor_user_registration_status' => 'MO_2_FACTOR_VERIFY_CUSTOMER' ) );
					$login_message = MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS );
					$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
					return;
				}
			} else {
				$login_message = is_string( $content ) ? $content : '';
				$login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
				$this->miniorange_pass2login_form_fields( $login_status, $login_message, $redirect_to, null, $session_id_encrypt );
				return;
			}

		}
		/**
		 * It is to save the inline settings
		 *
		 * @param string $user_id It will carry the user id .
		 * @param string $email It will carry the email .
		 * @param string $id It will carry the id .
		 * @param string $api_key It will carry the api key .
		 * @param string $token It will carry the token value .
		 * @param string $app_secret It will carry the secret data .
		 * @return void
		 */
		public function mo2f_inline_save_success_customer_config( $user_id, $email, $id, $api_key, $token, $app_secret ) {
			global $mo2fdb_queries;
			update_option( 'mo2f_customerKey', $id );
			update_option( 'mo2f_api_key', $api_key );
			update_option( 'mo2f_customer_token', $token );
			update_option( 'mo2f_app_secret', $app_secret );
			update_option( 'mo_wpns_enable_log_requests', true );
			update_option( 'mo2f_miniorange_admin', $id );
			update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
			$mo2fdb_queries->mo2f_update_user_details(
				$user_id,
				array(
					'mo2f_user_email' => sanitize_email( $email ),
				)
			);
		}

		/**
		 * It is to validate the otp in inline
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_otp_complete() {
			if ( isset( $_POST['miniorange_inline_validate_otp_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['miniorange_inline_validate_otp_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-validate-otp-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, '', $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * Validating the mobile authentication
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_mobile_authentication() {
			if ( isset( $_POST['mo_auth_inline_mobile_registration_complete_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo_auth_inline_mobile_registration_complete_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-mobile-registration-complete-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					MO2f_Utility::mo2f_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to             = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$selected_2factor_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
					$email                   = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id );
					$mo2fa_login_message     = '';
					$mo2fa_login_status      = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$enduser                 = new MO2f_Cloud_Onprem_Interface();
					$response                = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $user_id, true, $selected_2factor_method, null, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );

					if ( JSON_ERROR_NONE === json_last_error() ) { /* Generate Qr code */
						if ( 'ERROR' === $response['status'] ) {
							$mo2fa_login_message = MoWpnsMessages::lang_translate( $response['message'] );
						} elseif ( 'SUCCESS' === $response['status'] ) {
							$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';
						} else {
							$mo2fa_login_message = __( 'An error occured while validating the user. Please Try again.', 'miniorange-2-factor-authentication' );
						}
					} else {
						$mo2fa_login_message = __( 'Invalid request. Please try again', 'miniorange-2-factor-authentication' );
					}
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * This function will invoke the duo push notification
		 *
		 * @return string
		 */
		public function mo2f_duo_mobile_send_push_notification_for_inline_form() {
			if ( isset( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['duo_mobile_send_push_notification_inline_form_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mo2f-send-duo-push-notification-inline-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					MO2f_Utility::mo2f_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id     = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;

					$mo2fdb_queries->mo2f_update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
						)
					);
					$mo2fa_login_message = '';

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * This function will invoke on duo authentication validation
		 *
		 * @return string
		 */
		public function mo2f_inline_validate_duo_authentication() {
			if ( isset( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_POST['mo_auth_inline_duo_auth_mobile_registration_complete_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-duo_auth-registration-complete-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					MO2f_Utility::mo2f_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to             = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$selected_2factor_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
					$email                   = sanitize_email( $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id ) );
					$mo2fdb_queries->mo2f_update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
						)
					);
					$mo2fa_login_message = '';

					include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two_fa_duo_handler.php';
					$ikey = get_site_option( 'mo2f_d_integration_key' );
					$skey = get_site_option( 'mo2f_d_secret_key' );
					$host = get_site_option( 'mo2f_d_api_hostname' );

					$duo_preauth = preauth( $email, true, $skey, $ikey, $host );

					if ( isset( $duo_preauth['response']['stat'] ) && 'OK' === $duo_preauth['response']['stat'] ) {
						if ( isset( $duo_preauth['response']['response']['status_msg'] ) && 'Account is active' === $duo_preauth['response']['response']['status_msg'] ) {
							$mo2fa_login_message = $email . ' user is already exists, please go for step B duo will send push notification on your configured mobile.';
						} elseif ( isset( $duo_preauth['response']['response']['enroll_portal_url'] ) ) {
							$duo_enroll_url = $duo_preauth['response']['response']['enroll_portal_url'];
							update_user_meta( $user_id, 'user_not_enroll_on_duo_before', $duo_enroll_url );
							update_user_meta( $user_id, 'user_not_enroll', true );
						} else {
							$mo2fa_login_message = 'Your account is inactive from duo side, please contact to your administrator.';
						}
					} else {
						$mo2fa_login_message = 'Error through during preauth.';
					}

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * Back to select 2fa methods
		 *
		 * @return string
		 */
		public function back_to_select_2fa() {
			if ( isset( $_POST['miniorange_inline_two_factor_setup'] ) ) { /* return back to choose second factor screen */
				$nonce = sanitize_key( wp_unslash( $_POST['miniorange_inline_two_factor_setup'] ) );
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-setup-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					MO2f_Utility::mo2f_start_session();
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;

					$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

					$redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					$current_user = get_user_by( 'id', $user_id );
					$mo2fdb_queries->mo2f_update_user_details( $current_user->ID, array( 'mo2f_configured_2FA_method' => '' ) );
					$mo2fa_login_message = '';
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}

		/**
		 * It will help to create user in miniorange
		 *
		 * @param string $current_user_id It will carry the current user id .
		 * @param string $email It will carry the email address .
		 * @param string $current_method It will carry the current method .
		 * @return string
		 */
		public function create_user_in_miniorange( $current_user_id, $email, $current_method ) {
			$tempemail = get_user_meta( $current_user_id, 'mo2f_email_miniOrange', true );
			if ( isset( $tempemail ) && ! empty( $tempemail ) ) {
				$email = $tempemail;
			}
			global $mo2fdb_queries;
			if ( get_option( 'mo2f_miniorange_admin' === $current_user_id ) ) {
				$email = get_option( 'mo2f_email' );
			}
			$mocurl     = new MocURL();
			$check_user = json_decode( $mocurl->mo_check_user_already_exist( $email ), true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				if ( 'ERROR' === $check_user['status'] && 'You are not authorized to create users. Please upgrade to premium plan.' === $check_user['message'] ) {
					$current_user = get_user_by( 'id', $current_user_id );
					$content      = json_decode( $mocurl->mo_create_user( $current_user, $email ), true );

						update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
						$mo2fdb_queries->mo2f_update_user_details(
							$current_user_id,
							array(
								'user_registration_with_miniorange' => 'SUCCESS',
								'mo2f_user_email' => $email,
								'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
							)
						);

						$mo2fa_login_message = '';
						$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';

				} elseif ( strcasecmp( $check_user['status'], 'USER_FOUND' ) === 0 ) {
					$mo2fdb_queries->mo2f_update_user_details(
						$current_user_id,
						array(
							'user_registration_with_miniorange' => 'SUCCESS',
							'mo2f_user_email' => $email,
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
						)
					);
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation

					$mo2fa_login_status = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					return $check_user;
				} elseif ( 0 === strcasecmp( $check_user['status'], 'USER_NOT_FOUND' ) ) {
					$current_user = get_user_by( 'id', $current_user_id );
					$content      = json_decode( $mocurl->mo_create_user( $current_user, $email ), true );
					if ( JSON_ERROR_NONE === json_last_error() ) {
						if ( 0 === strcasecmp( $content['status'], 'SUCCESS' ) ) {
							update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not using for obfuscation
							$mo2fdb_queries->mo2f_update_user_details(
								$current_user_id,
								array(
									'user_registration_with_miniorange' => 'SUCCESS',
									'mo2f_user_email' => $email,
									'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
								)
							);

							$mo2fa_login_message = '';
							$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
							return $check_user;
						} else {
							$check_user['status']  = 'ERROR';
							$check_user['message'] = 'There is an issue in user creation in miniOrange. Please skip and contact miniorange';
							return $check_user;
						}
					}
				} elseif ( 0 === strcasecmp( $check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER' ) ) {
					$mo2fa_login_message   = __( 'The email associated with your account is already registered. Please contact your admin to change the email.', 'miniorange-2-factor-authentication' );
					$check_user['status']  = 'ERROR';
					$check_user['message'] = $mo2fa_login_message;
					return $check_user;
				}
			}
		}

		/**
		 * It is a alternate login method
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_alternate_login_kba( $posted ) {
			$nonce = $posted['miniorange_alternate_login_kba_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-alternate-login-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				MO2f_Utility::mo2f_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? $posted['session_id'] : null;
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
				$redirect_to        = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				$this->mo2f_onprem_cloud_obj->mo2f_pass2login_kba_verification( get_user_by( 'id', $user_id ), '', $redirect_to, $session_id_encrypt );
			}
		}
		/**
		 * It is for duo push notification validation
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_duo_push_validation( $posted ) {
			global $mo_wpns_utility;
			$nonce = $posted['miniorange_duo_push_validation_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-duo-validation-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				MO2f_Utility::mo2f_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				$user_id            = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

				$redirect_to = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
				MO2f_Utility::mo2f_debug_file( 'Duo push notification - Logged in successfully User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $user_id );
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			}
		}
		/**
		 * This will invoke Duo push validation failed
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_miniorange_duo_push_validation_failed( $posted ) {
			global $mo_wpns_utility;
			$nonce = $posted['miniorange_duo_push_validation_failed_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-duo-push-validation-failed-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_textarea( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				return $error;
			} else {
				MO2f_Utility::mo2f_debug_file( 'Denied duo push notification  User_IP-' . $mo_wpns_utility->get_client_ip() );
				MO2f_Utility::mo2f_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				$this->remove_current_activity( $session_id_encrypt );
			}
		}
		/**
		 * Duo authenticator setup success form
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_mo2f_duo_authenticator_success_form( $posted ) {
			if ( isset( $posted['mo2f_duo_authenticator_success_nonce'] ) ) {
				$nonce = sanitize_text_field( $posted['mo2f_duo_authenticator_success_nonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mo2f-duo-authenticator-success-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . __( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . __( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
					return $error;
				} else {
					global $mo2fdb_queries;
					MO2f_Utility::mo2f_start_session();
					$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
					MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
					$user_id                 = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					$redirect_to             = isset( $posted['redirect_to'] ) ? esc_url_raw( $posted['redirect_to'] ) : null;
					$selected_2factor_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user_id );
					$email                   = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id );
					$mo2fa_login_message     = '';

					delete_user_meta( $user_id, 'user_not_enroll' );
					delete_site_option( 'current_user_email' );
					$mo2fdb_queries->mo2f_update_user_details(
						$user_id,
						array(
							'mobile_registration_status' => true,
							'mo2f_DuoAuthenticator_config_status' => true,
							'mo2f_configured_2FA_method' => $selected_2factor_method,
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
						)
					);
					$mo2fa_login_status = 'MO_2_FACTOR_SETUP_SUCCESS';

					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
				}
			}
		}
		/**
		 * Duo authenticator error function
		 *
		 * @param string $posted It will carry the post data .
		 * @return string
		 */
		public function check_inline_mo2f_duo_authenticator_error( $posted ) {
			$nonce = $posted['mo2f_inline_duo_authentcator_error_nonce'];

			if ( ! wp_verify_nonce( $nonce, 'mo2f-inline-duo-authenticator-error-nonce' ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( 'Invalid Request.' ) );

				return $error;
			} else {
				global  $mo2fdb_queries;
				MO2f_Utility::mo2f_start_session();
				$session_id_encrypt = isset( $posted['session_id'] ) ? sanitize_text_field( wp_unslash( $posted['session_id'] ) ) : null;
				MO2f_Utility::unset_temp_user_details_in_table( 'mo2f_transactionId', $session_id_encrypt );
				$user_id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );

				$mo2fdb_queries->mo2f_update_user_details(
					$user_id,
					array(
						'mobile_registration_status' => false,
					)
				);
			}
		}

		/**
		 * Pass2 login redirect function
		 *
		 * @return string
		 */
		public function miniorange_pass2login_redirect() {
			do_action( 'mo2f_network_init' );
			global $mo2fdb_queries;
			if ( isset( $_GET['reconfigureMethod'] ) && is_user_logged_in() ) {
				$useridget = get_current_user_id();
				$txidget   = isset( $_GET['transactionId'] ) ? sanitize_text_field( wp_unslash( $_GET['transactionId'] ) ) : '';
				$methodget = isset( $_GET['reconfigureMethod'] ) ? sanitize_text_field( wp_unslash( $_GET['reconfigureMethod'] ) ) : '';
				if ( get_site_option( $txidget ) === $useridget && ctype_xdigit( $txidget ) && ctype_xdigit( $methodget ) ) {
					$method = get_site_option( $methodget );
					$mo2fdb_queries->mo2f_update_user_details(
						$useridget,
						array(
							'mo_2factor_user_registration_status' => 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS',
							'mo2f_configured_2FA_method' => $method,
						)
					);
					$is_authy_configured = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_AuthyAuthenticator_config_status', $useridget );
					if ( MoWpnsConstants::GOOGLE_AUTHENTICATOR === $method || $is_authy_configured ) {
						update_user_meta( $useridget, 'mo2fa_set_Authy_inline', true );
					}
					delete_site_option( $txidget );
				} else {
					$popup_args    = array(
						'head'         => 'You are not authorized to perform this action',
						'body'         => 'Please contact to your admin',
						'color'        => '#FF0000',
						'bg_color'     => '#FFFFFF',
						'branding_img' => 'background-color: #d5e3d9;',
						'logo_url'     => esc_url( $main_dir . 'includes/images/miniOrange2.png' ),
					);
					$display_popup = new Mo2f_Login_Popup();
					$display_popup->mo2f_display_email_verification( $popup_args );
					exit();
				}
			} elseif ( isset( $_POST['emailInlineCloud'] ) ) {
				$nonce = isset( $_POST['miniorange_emailChange_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['miniorange_emailChange_nonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-email-change-nonce' ) ) {
					$error = new WP_Error();
					$error->add( 'empty_username', '<strong>' . esc_html( 'ERROR' ) . '</strong>: ' . esc_html( 'Invalid Request.' ) );
					return $error;
				} else {
					$email              = sanitize_text_field( wp_unslash( $_POST['emailInlineCloud'] ) );
					$current_user_id    = isset( $_POST['current_user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['current_user_id'] ) ) : '';
					$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
					$redirect_to        = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : null;
					if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
						global  $mo2fdb_queries;
						$mo2fdb_queries->mo2f_update_user_details(
							$current_user_id,
							array(
								'mo2f_user_email' => $email,
								'mo2f_configured_2FA_method' => '',
							)
						);
						prompt_user_to_select_2factor_mthod_inline( $current_user_id, 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR', '', $redirect_to, $session_id_encrypt, null );
					}
				}
			} else {
				$value = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : false;
				switch ( $value ) {

					case 'miniorange_alternate_login_kba':
						$this->check_miniorange_alternate_login_kba( $_POST );
						break;

					case 'miniorange_duo_push_validation':
						$this->check_miniorange_duo_push_validation( $_POST );
						break;

					case 'mo2f_inline_duo_authenticator_success_form':
						$this->check_mo2f_duo_authenticator_success_form( $_POST );
						break;

					case 'mo2f_inline_duo_authenticator_error':
						$this->check_inline_mo2f_duo_authenticator_error( $_POST );
						break;

					case 'miniorange_duo_push_validation_failed':
						$this->check_miniorange_duo_push_validation_failed( $_POST );
						break;

					case 'miniorange_back_inline':
						$this->back_to_select_2fa();
						break;

					case 'miniorange_inline_complete_mobile':
						$this->mo2f_inline_validate_mobile_authentication();
						break;
					case 'miniorange_inline_duo_auth_mobile_complete':
						$this->mo2f_inline_validate_duo_authentication();
						break;
					case 'duo_mobile_send_push_notification_for_inline_form':
						$this->mo2f_duo_mobile_send_push_notification_for_inline_form();
						break;

					default:
						$error = new WP_Error();
						$error->add( 'empty_username', __( '<strong>ERROR</strong>: Invalid Request.', 'miniorange-2-factor-authentication' ) );
						return $error;
				}
			}
		}
		/**
		 * Removing the current activity
		 *
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function remove_current_activity( $session_id ) {
			global $mo2fdb_queries;
			$session_variables = array(
				'mo2f_current_user_id',
				'mo2f_1stfactor_status',
				'mo_2factor_login_status',
				'mo2f-login-qrCode',
				'mo2f_transactionId',
				'mo2f_login_message',
				'mo_2_factor_kba_questions',
				'mo2f_show_qr_code',
				'mo2f_google_auth',
				'mo2f_authy_keys',
			);

			$cookie_variables = array(
				'mo2f_current_user_id',
				'mo2f_1stfactor_status',
				'mo_2factor_login_status',
				'mo2f-login-qrCode',
				'mo2f_transactionId',
				'mo2f_login_message',
				'kba_question1',
				'kba_question2',
				'mo2f_show_qr_code',
				'mo2f_google_auth',
				'mo2f_authy_keys',
			);

			$temp_table_variables = array(
				'session_id',
				'mo2f_current_user_id',
				'mo2f_login_message',
				'mo2f_1stfactor_status',
				'mo2f_transactionId',
				'mo_2_factor_kba_questions',
				'ts_created',
			);

			MO2f_Utility::unset_session_variables( $session_variables );
			MO2f_Utility::unset_cookie_variables( $cookie_variables );
			$key             = get_option( 'mo2f_encryption_key' );
			$session_id      = MO2f_Utility::decrypt_data( $session_id, $key );
			$session_id_hash = md5( $session_id );
			$mo2fdb_queries->save_user_login_details(
				$session_id_hash,
				array(

					'mo2f_current_user_id'      => '',
					'mo2f_login_message'        => '',
					'mo2f_1stfactor_status'     => '',
					'mo2f_transactionId'        => '',
					'mo_2_factor_kba_questions' => '',
					'ts_created'                => '',
				)
			);
		}

		/**
		 * It will pass 2fa on login flow
		 *
		 * @param string  $mo2fa_login_status It will carry the login status message .
		 * @param string  $mo2fa_login_message It will carry the login message .
		 * @param string  $redirect_to It will carry the redirect url .
		 * @param string  $qr_code It will carry the qr code .
		 * @param string  $session_id_encrypt It will carry the session id .
		 * @param string  $show_back_button It will help to show button .
		 * @param boolean $mo2fa_transaction_id It will carry the transaction id .
		 * @return void
		 */
		public function miniorange_pass2login_form_fields( $mo2fa_login_status = null, $mo2fa_login_message = null, $redirect_to = null, $qr_code = null, $session_id_encrypt = null, $show_back_button = null, $mo2fa_transaction_id = false ) {
			$login_status  = $mo2fa_login_status;
			$login_message = $mo2fa_login_message;
			switch ( $login_status ) {

				case 'MO_2_FACTOR_CHALLENGE_DUO_PUSH_NOTIFICATIONS':
					$user_id = $this->mo2f_user_id ? $this->mo2f_user_id : MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
					mo2f_get_duo_push_authentication_prompt(
						$login_status,
						$login_message,
						$redirect_to,
						$session_id_encrypt,
						$user_id
					);
					break;

				case 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL':
					mo2f_get_forgotphone_form( $login_status, $login_message, $redirect_to, $session_id_encrypt );
					break;

				default:
					$this->mo_2_factor_pass2login_show_wp_login_form();
					break;
			}
			exit();
		}

		/**
		 * Forgot phone status
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_forgotphone_status( $login_status ) {
			// after clicking on forgotphone link when both kba and email are configured .
			if ( 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL' === $login_status ) {
				return true;
			}

			return false;
		}

		/**
		 * It will redirect to shortcode addon
		 *
		 * @param string $current_user_id .
		 * @param string $login_status It will store the login status message .
		 * @param string $login_message .
		 * @param string $identity .
		 * @return void
		 */
		public function mo2f_redirect_shortcode_addon( $current_user_id, $login_status, $login_message, $identity ) {
			do_action( 'mo2f_shortcode_addon', $current_user_id, $login_status, $login_message, $identity );
		}
		/**
		 * It will Check kba status
		 *
		 * @param string $login_status It will store the login status message .
		 * @return boolean
		 */
		public function miniorange_pass2login_check_kba_status( $login_status ) {
			if ( MoWpnsConstants::MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION === $login_status ) {
				return true;
			}

			return false;
		}

		/**
		 * Pass2login for showing login form
		 *
		 * @return mixed
		 */
		public function mo_2_factor_pass2login_show_wp_login_form() {
			$session_id_encrypt = $this->create_session();
			if ( class_exists( 'Theme_My_Login' ) ) {
				wp_enqueue_script( 'tmlajax_script', plugins_url( 'includes/js/tmlajax.min.js', dirname( dirname( __FILE__ ) ) ), array( 'jQuery' ), MO2F_VERSION, false );
				wp_localize_script(
					'tmlajax_script',
					'my_ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);
			}
			if ( class_exists( 'LoginWithAjax' ) ) {
				wp_enqueue_script( 'login_with_ajax_script', plugins_url( 'includes/js/login_with_ajax.min.js', dirname( dirname( __FILE__ ) ) ), array( 'jQuery' ), MO2F_VERSION, false );
				wp_localize_script(
					'login_with_ajax_script',
					'my_ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);
			}
			?>
		<p><input type="hidden" name="miniorange_login_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>

			<input type="hidden" id="sessid" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>

		</p>

			<?php
		}

		/**
		 * Pass to login push verification
		 *
		 * @param string $currentuser It will carry the current user .
		 * @param string $mo2f_second_factor It will store the second factor method .
		 * @param string $redirect_to It will store the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @return void
		 */
		public function mo2f_pass2login_duo_push_verification( $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt ) {
			global $mo2fdb_queries;
			include_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'two_fa_duo_handler.php';
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $this->create_session();
			}

			$mo2fa_login_message = '';
			$mo2fa_login_status  = 'MO_2_FACTOR_CHALLENGE_DUO_PUSH_NOTIFICATIONS';
			$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt );
		}

		/**
		 * Pass2 login method
		 *
		 * @param string $redirect_to It will carry the redirect url.
		 * @param string $session_id_encrypted It will carry the session id.
		 * @return void
		 */
		public function mo2fa_pass2login( $redirect_to = null, $session_id_encrypted = null ) {
			if ( empty( $this->mo2f_user_id ) && empty( $this->fstfactor ) ) {
				$user_id               = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_current_user_id' );
				$mo2f_1stfactor_status = MO2f_Utility::mo2f_get_transient( $session_id_encrypted, 'mo2f_1stfactor_status' );
			} else {
				$user_id               = $this->mo2f_user_id;
				$mo2f_1stfactor_status = $this->fstfactor;
			}

			if ( $user_id && $mo2f_1stfactor_status && ( 'VALIDATE_SUCCESS' === $mo2f_1stfactor_status ) ) {
				$currentuser = get_user_by( 'id', $user_id );
				wp_set_current_user( $user_id, $currentuser->user_login );
				$mobile_login = new Miniorange_Mobile_Login();
				$mobile_login->remove_current_activity( $session_id_encrypted );
				delete_expired_transients( true );
				delete_site_option( $session_id_encrypted );
				wp_set_auth_cookie( $user_id, true );
				do_action( 'wp_login', $currentuser->user_login, $currentuser );
				redirect_user_to( $currentuser, $redirect_to );
				exit;
			} else {
				$this->remove_current_activity( $session_id_encrypted );
			}
		}
		/**
		 * This function will invoke to create session for user
		 *
		 * @return string
		 */
		public function create_session() {
			global $mo2fdb_queries;
			$session_id      = MO2f_Utility::random_str( 20 );
			$session_id_hash = md5( $session_id );
			$mo2fdb_queries->insert_user_login_session( $session_id_hash );
			$key                = get_site_option( 'mo2f_encryption_key' );
			$session_id_encrypt = MO2f_Utility::encrypt_data( $session_id, $key );
			return $session_id_encrypt;
		}

		/**
		 * Get redirect url for Ultimate Member Form
		 *
		 * @param object $currentuser Current user.
		 * @return string
		 */
		public function mo2f_redirect_url_for_um( $currentuser ) {
			MO2f_Utility::mo2f_debug_file( 'Using UM login form.' );
			$redirect_to = '';
			if ( ! isset( $_POST['wp-submit'] ) && isset( $_POST['um_request'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
				$meta = get_site_option( 'um_role_' . $currentuser->roles[0] . '_meta' );
				if ( isset( $meta ) && ! empty( $meta ) ) {
					if ( isset( $meta['_um_login_redirect_url'] ) ) {
						$redirect_to = $meta['_um_login_redirect_url'];
					}
					if ( empty( $redirect_to ) ) {
						$redirect_to = get_site_url();
					}
				}
				$login_form_url = '';
				if ( isset( $_POST['redirect_to'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
					$login_form_url = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request is coming from Ultimate member login form.
				}
				if ( ! empty( $login_form_url ) && ! is_null( $login_form_url ) ) {
					$redirect_to = $login_form_url;
				}
			}
			return $redirect_to;
		}

		/**
		 * Select methods for twofa
		 *
		 * @param object $currentuser It will carry the current user .
		 * @param string $mo2f_second_factor It will carry the second factor .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2fa_select_method( $currentuser, $mo2f_second_factor, $session_id_encrypt, $redirect_to ) {
			global $mo_wpns_utility, $mo2fdb_queries;
			if ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_second_factor ) {
				if ( MoWpnsUtility::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' ) <= 0 ) {
					update_site_option( 'bGltaXRSZWFjaGVk', 1 );
				}
			}
			$mo_2fa_load_2fa_login_method_view = array(
				MoWpnsConstants::OUT_OF_BAND_EMAIL    => array( $this, 'mo2f_pass2login_push_oobemail_verification' ),
				MoWpnsConstants::OTP_OVER_SMS         => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::OTP_OVER_EMAIL       => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::OTP_OVER_TELEGRAM    => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::GOOGLE_AUTHENTICATOR => array( $this, 'mo2f_pass2login_otp_verification' ),
				MoWpnsConstants::SECURITY_QUESTIONS   => array( $this->mo2f_onprem_cloud_obj, 'mo2f_pass2login_kba_verification' ),
				MoWpnsConstants::DUO_AUTHENTICATOR    => array( $this, 'mo2f_pass2login_duo_push_verification' ),
				MoWpnsConstants::OTP_OVER_WHATSAPP    => array( $this, 'mo2f_pass2login_otp_verification' ),
			);
			if ( ! empty( $mo_2fa_load_2fa_login_method_view[ $mo2f_second_factor ] ) ) {
				call_user_func( $mo_2fa_load_2fa_login_method_view[ $mo2f_second_factor ], $currentuser, $mo2f_second_factor, $redirect_to, $session_id_encrypt );
			} elseif ( 'NONE' === $mo2f_second_factor ) {
				MO2f_Utility::mo2f_debug_file( 'mo2f_second_factor is NONE User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
				} else {
					return $currentuser;
				}
			} else {
				$this->remove_current_activity( $session_id_encrypt );
				$error = new WP_Error();
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					MO2f_Utility::mo2f_debug_file( 'Two factor method has not been configured User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$data = array( 'notice' => '<div style="border-left:3px solid #dc3232;">&nbsp; Two Factor method has not been configured.' );
					wp_send_json_success( $data );
				} else {
					MO2f_Utility::mo2f_debug_file( 'Two factor method has not been configured User_IP-' . $mo_wpns_utility->get_client_ip() . ' User_Id-' . $currentuser->ID . ' Email-' . $currentuser->user_email );
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: Two Factor method has not been configured.', 'miniorange-2-factor-authentication' ) );
					return $error;
				}
			}
		}
		/**
		 * This function will validating the soft token
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $mo2f_second_factor It will carry the second factor method .
		 * @param string $softtoken It will carry the soft token .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param string $redirect_to It will carry the redirect url .
		 * @return string
		 */
		public function mo2f_validate_soft_token( $currentuser, $mo2f_second_factor, $softtoken, $session_id_encrypt, $redirect_to = null ) {
			global $mo2fdb_queries;
			$email   = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $currentuser->ID );
			$content = json_decode( $this->mo2f_onprem_cloud_obj->validate_otp_token( $mo2f_second_factor, $email, null, $softtoken ), true );
			if ( strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
				$this->mo2fa_pass2login( $redirect_to, $session_id_encrypt );
			} else {
				if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
					$data = array( 'notice' => '<div style="border-left:3px solid #dc3232;">&nbsp; Invalid One Time Passcode.' );
					wp_send_json_success( $data );
				} else {
					return new WP_Error( 'invalid_one_time_passcode', '<strong>ERROR</strong>: Invalid One Time Passcode.' );
				}
			}
		}
		/**
		 * Sending the otp over email
		 *
		 * @param string $email It will carry the email address .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id_encrypt It will carry the session id .
		 * @param object $current_user It will carry the current user .
		 * @return void
		 */
		public function mo2f_otp_over_email_send( $email, $redirect_to, $session_id_encrypt, $current_user ) {
			$response = array();
			if ( get_site_option( 'cmVtYWluaW5nT1RQ' ) > 0 ) {
				$content  = $this->mo2f_onprem_cloud_obj->send_otp_token( null, $email, MoWpnsConstants::OTP_OVER_EMAIL, $current_user );
				$response = json_decode( $content, true );
				if ( ! MO2F_IS_ONPREM ) {
					if ( isset( $response['txId'] ) ) {
						MO2f_Utility::mo2f_set_transient( $session_id_encrypt, 'mo2f_transactionId', $response['txId'] );
					}
				}
			} else {
				$response['status'] = 'FAILED';
			}
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $response['status'] ) {
					$cmvtywluaw5nt1rq = get_site_option( 'cmVtYWluaW5nT1RQ' );
					if ( $cmvtywluaw5nt1rq > 0 ) {
						update_site_option( 'cmVtYWluaW5nT1RQ', $cmvtywluaw5nt1rq - 1 );
					}
					$mo2fa_login_message  = 'An OTP has been sent to ' . MO2f_Utility::mo2f_get_hidden_email( $email ) . '. Please verify to set the two-factor';
					$mo2fa_login_status   = MoWpnsConstants::MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL;
					$mo2fa_transaction_id = isset( $response['txId'] ) ? $response['txId'] : null;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt, 1, $mo2fa_transaction_id );
				} else {
					$mo2fa_login_status  = 'MO_2_FACTOR_PROMPT_USER_FOR_2FA_METHODS';
					$mo2fa_login_message = user_can( $current_user->ID, 'administrator' ) ? MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL : MoWpnsMessages::ERROR_DURING_PROCESS;
					$this->miniorange_pass2login_form_fields( $mo2fa_login_status, $mo2fa_login_message, $redirect_to, null, $session_id_encrypt, 1 );
				}
			}
		}

		/**
		 * Get redirect URL.
		 *
		 * @return string
		 */
		public function mo2f_get_redirect_url() {
			if ( isset( $_REQUEST['woocommerce-login-nonce'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				MO2f_Utility::mo2f_debug_file( 'It is a woocommerce login form. Get woocommerce redirectUrl' );
				if ( ! empty( $_REQUEST['redirect_to'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
					$redirect_to = sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				} elseif ( isset( $_REQUEST['_wp_http_referer'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
					$redirect_to = sanitize_text_field( wp_unslash( $_REQUEST['_wp_http_referer'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
				} else {
					if ( function_exists( 'wc_get_page_permalink' ) ) {
						$redirect_to = wc_get_page_permalink( 'myaccount' ); // function exists in WooCommerce plugin.
					}
				}
			} elseif ( get_site_option( 'mo2f_enable_custom_redirect' ) ) {
				$redirect_to = get_site_option( 'mo2f_custom_redirect_url' );
			} else {
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : ( isset( $_REQUEST['redirect'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect'] ) ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request is coming from WooCommerce login form.
			}
			return esc_url_raw( $redirect_to );
		}

		/**
		 * It will help to enqueue the default login
		 *
		 * @return void
		 */
		public function mo_2_factor_enable_jquery_default_login() {
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Save user details in mo2f_user_details table
		 *
		 * @param int     $user_id user id.
		 * @param boolean $config_status configuration status.
		 * @param string  $twofa_method 2FA method.
		 * @param string  $user_registation user registration status.
		 * @param string  $tfastatus 2FA registration status.
		 * @param boolean $enable_byuser Enable 2FA for user.
		 * @param string  $email user's email.
		 * @param string  $phone user'phone.
		 * @param string  $whatsapp user'whatsapp.
		 * @return void
		 */
		public function mo2fa_update_user_details( $user_id, $config_status, $twofa_method, $user_registation, $tfastatus, $enable_byuser, $email = null, $phone = null, $whatsapp = null ) {
			global $mo2fdb_queries;
			$details_to_be_updated  = array();
			$user_details_key_value = array(
				'mo2f_' . implode( '', explode( ' ', MoWpnsConstants::mo2f_convert_method_name( $twofa_method, 'cap_to_small' ) ) ) . '_config_status' => $config_status,
				'mo2f_configured_2FA_method'          => $twofa_method,
				'user_registration_with_miniorange'   => $user_registation,
				'mo_2factor_user_registration_status' => $tfastatus,
				'mo2f_2factor_enable_2fa_byusers'     => $enable_byuser,
				'mo2f_user_email'                     => $email,
				'mo2f_user_phone'                     => $phone,
				'mo2f_user_whatsapp'                  => $whatsapp,
			);

			foreach ( $user_details_key_value as $key => $value ) {
				if ( isset( $value ) ) {
						$details_to_be_updated = array_merge( $details_to_be_updated, array( $key => $value ) );

				}
			}
			delete_user_meta( $user_id, 'mo2f_grace_period_start_time' );
			$mo2fdb_queries->mo2f_update_user_details( $user_id, $details_to_be_updated );
		}

	}
	new Miniorange_Password_2Factor_Login();
}
?>
