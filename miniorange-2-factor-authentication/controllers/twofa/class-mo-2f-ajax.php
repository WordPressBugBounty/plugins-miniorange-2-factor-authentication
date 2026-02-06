<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

namespace TwoFA\Controllers\Twofa;

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Handler\Twofa\Google_Auth_Onpremise;
use TwoFA\Traits\Instance;
use WP_Error;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo_2f_Ajax' ) ) {

	/**
	 * Class Mo_2f_Ajax
	 */
	class Mo_2f_Ajax {

		use Instance;

		/**
		 * Class Mo2f_Cloud_Onprem_Interface object
		 *
		 * @var object
		 */
		private $mo2f_onprem_cloud_obj;
		/**
		 * Constructor of class.
		 */
		public function __construct() {
			$this->mo2f_onprem_cloud_obj = MO2f_Cloud_Onprem_Interface::instance();
			add_action( 'admin_init', array( $this, 'mo_2f_two_factor' ) );
		}
		/**
		 * Function for handling ajax requests.
		 *
		 * @return void
		 */
		public function mo_2f_two_factor() {
			add_action( 'wp_ajax_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax' ) );
		}
		/**
		 * Call functions as per ajax requests.
		 *
		 * @return void
		 */
		public function mo_two_factor_ajax() {
			$GLOBALS['mo2f_is_ajax_request'] = true;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'class-mo2f-ajax' );
			}
			switch ( isset( $_POST['mo_2f_two_factor_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2f_two_factor_ajax'] ) ) : '' ) {
				case 'mo2f_ajax_login_redirect':
					$this->mo2f_ajax_login_redirect();
					break;
				case 'mo2f_set_otp_over_sms':
					$this->mo2f_set_otp_over_sms();
					break;
				case 'mo2f_set_otp_over_whatsapp':
					$this->mo2f_set_otp_over_whatsapp();
					break;
				case 'mo2f_enable_twofactor_userprofile':
					$this->mo2f_enable_twofactor_userprofile( $_POST );
					break;
				case 'mo2f_set_GA':
					$this->mo2f_set_ga();
					break;
				case 'mo2f_google_auth_set_transient':
					$this->mo2f_google_auth_set_transient();
					break;
			}
		}

		/**
		 * Sets google authenticator transients.
		 *
		 * @return void
		 */
		public function mo2f_google_auth_set_transient() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'edit_users' ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			} else {
				$auth_name  = isset( $_POST['auth_name'] ) ? sanitize_text_field( wp_unslash( $_POST['auth_name'] ) ) : null;
				$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				if ( MoWpnsConstants::MSFT_AUTH === $auth_name ) {
					$url = isset( $_POST['micro_soft_url'] ) ? sanitize_text_field( wp_unslash( $_POST['micro_soft_url'] ) ) : null;
				} else {
					$url = isset( $_POST['g_auth_url'] ) ? sanitize_text_field( wp_unslash( $_POST['g_auth_url'] ) ) : null;
				}
				$current_user = wp_get_current_user();
				update_user_meta( $current_user->ID, 'mo2f_ga_qrCode', $url );
				wp_send_json_success();

			}
		}

		/**
		 * Validate Google authenticator in dashboard.
		 *
		 * @return void
		 */
		public function mo2f_validate_google_authenticator() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
			} else {
				$otp_token          = isset( $_POST['otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_token'] ) ) : null;
				$session_id_encrypt = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null;
				$ga_secret          = isset( $_POST['ga_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_secret'] ) ) : null;

				global $mo2fdb_queries, $mo2f_user;
				if ( MO2f_Utility::mo2f_check_number_length( $otp_token ) ) {
					$email     = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $mo2f_user->ID );
					$mo2f_user = wp_get_current_user();
					if ( ! $mo2f_user->ID ) {
						$common_helper = new Mo2f_Common_Helper();
						$user_id       = $common_helper->mo2f_get_current_user_id( $session_id_encrypt );
						if ( is_null( $user_id ) ) {
							wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::INVALID_SESSION ) );
						}
						$mo2f_user = get_user_by( 'id', $user_id );
					}
					$email                  = ( empty( $email ) ) ? $mo2f_user->user_email : $email;
					$twofactor_transactions = new Mo2fDB();
					$exceeded               = $twofactor_transactions->check_alluser_limit_exceeded( $mo2f_user->ID );
					if ( $exceeded ) {
						wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::USER_LIMIT_EXCEEDED ) );
					}
					if ( ! $ga_secret ) {
						$ga_secret = get_user_meta( $mo2f_user->ID, 'mo2f_secret_ga', true );
					}
					$google_response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						if ( MoWpnsConstants::SUCCESS_RESPONSE === $google_response['status'] ) {
							$response = json_decode( $this->mo2f_onprem_cloud_obj->mo2f_update_user_info( $mo2f_user->ID, true, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SUCCESS_RESPONSE, MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS, true, $email, null ), true );
							if ( json_last_error() === JSON_ERROR_NONE ) {
								if ( MoWpnsConstants::SUCCESS_RESPONSE === $response['status'] ) {
									delete_user_meta( $mo2f_user->ID, 'mo2f_2FA_method_to_configure' );
									delete_user_meta( $mo2f_user->ID, 'mo2f_configure_2FA' );
									delete_user_meta( $mo2f_user->ID, 'mo2f_google_auth' );
									$configured_2fa_method = MoWpnsConstants::GOOGLE_AUTHENTICATOR;
									if ( MO2F_IS_ONPREM ) {
										update_user_meta( $mo2f_user->ID, 'mo2f_2FA_method_to_configure', $configured_2fa_method );
										$gauth_obj = new Google_Auth_Onpremise();
										$gauth_obj->mo_g_auth_set_secret( $mo2f_user->ID, $ga_secret );
									}
									update_user_meta( $mo2f_user->ID, 'mo2f_external_app_type', $configured_2fa_method );
									delete_user_meta( $mo2f_user->ID, 'mo2f_user_profile_set' );
									wp_send_json_success( $configured_2fa_method . ' has been configured successfully.' );
								}
							}
						}
						wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::INVALID_OTP ) );
					}
					wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_WHILE_VALIDATING_OTP ) );
				} else {
					wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ONLY_DIGITS_ALLOWED ) );
				}
			}
		}

		/**
		 * Enables userprofile 2FA.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_enable_twofactor_userprofile( $post ) {
			$is_userprofile_2fa_enabled = isset( $post['is_enabled'] ) && 'true' === sanitize_text_field( wp_unslash( $post['is_enabled'] ) );
			wp_send_json( $is_userprofile_2fa_enabled );
		}

		/**
		 * Function to set OTP over SMS of user.
		 *
		 * @return void
		 */
		public function mo2f_set_otp_over_sms() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
				exit;
			}
			$is_2fa_enabled = isset( $_POST['is_2fa_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['is_2fa_enabled'] ) ) : null;
			if ( 'true' !== $is_2fa_enabled ) {
				wp_send_json( '2fadisabled' );
			}
			global $mo2fdb_queries;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = get_transient( $transient_id . 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$new_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : null;
			$new_phone = str_replace( ' ', '', $new_phone );
			$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_user_phone' => $new_phone ) );
			wp_send_json_success( $new_phone );
		}

		/**
		 * Function to set OTP over WhatsApp of user.
		 *
		 * @return void
		 */
		public function mo2f_set_otp_over_whatsapp() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
				exit;
			}
			$is_2fa_enabled = isset( $_POST['is_2fa_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['is_2fa_enabled'] ) ) : null;
			if ( 'true' !== $is_2fa_enabled ) {
				wp_send_json( '2fadisabled' );
			}
			global $mo2fdb_queries;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = get_transient( $transient_id . 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$new_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : null;
			$new_phone = str_replace( ' ', '', $new_phone );
			$mo2fdb_queries->mo2f_update_user_details( $user_id, array( 'mo2f_user_whatsapp' => $new_phone ) );
			wp_send_json_success( $new_phone );
		}
		/**
		 * Function to set Google Authenticator method of user.
		 *
		 * @return void
		 */
		public function mo2f_set_ga() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
			}
			$is_2fa_enabled = isset( $_POST['is_2fa_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['is_2fa_enabled'] ) ) : null;
			if ( 'true' !== $is_2fa_enabled ) {
				wp_send_json( '2fadisabled' );
			}
			include_once dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$transient_id = isset( $_POST['transient_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transient_id'] ) ) : null;
			$user_id      = get_transient( $transient_id . 'mo2f_user_id' );
			if ( empty( $user_id ) ) {
				wp_send_json_error( 'UserIdNotFound' );
			}
			$user      = get_user_by( 'id', $user_id );
			$email     = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id );
			$email     = ! empty( $email ) ? $email : $user->user_email;
			$otp_token = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null;
			$ga_secret = isset( $_POST['ga_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_secret'] ) ) : null;

			$mo2f_onprem_cloud_obj->mo2f_set_gauth_secret( $user_id, $email, $ga_secret );

			$google_response = json_decode( $mo2f_onprem_cloud_obj->mo2f_validate_google_auth( $email, $otp_token, $ga_secret ), true );
			wp_send_json_success( $google_response['status'] );
		}
		/**
		 * Function to redirect user on ajax login.
		 *
		 * @return void
		 */
		public function mo2f_ajax_login_redirect() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			}
			$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : null;
			$password = isset( $_POST['password'] ) ? $_POST['password'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,  WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Password should not be sanitized.
			apply_filters( 'authenticate', null, $username, $password ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook.
		}
	}
}
