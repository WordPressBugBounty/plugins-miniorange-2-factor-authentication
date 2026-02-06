<?php
/**
 * This file contains the class for the password 2 factor login.
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
		 * Pass2login for showing login form
		 *
		 * @return mixed
		 */
		public function mo_2_factor_pass2login_show_wp_login_form() {
			$session_id_encrypt = $this->create_session();
			if ( class_exists( 'Theme_My_Login' ) ) {
				wp_enqueue_script( 'tmlajax_script', plugins_url( 'includes/js/tmlajax.min.js', dirname( __DIR__ ) ), array( 'jQuery' ), MO2F_VERSION, false );
				wp_localize_script(
					'tmlajax_script',
					'my_ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
				);
			}
			if ( class_exists( 'LoginWithAjax' ) ) {
				wp_enqueue_script( 'login_with_ajax_script', plugins_url( 'includes/js/login_with_ajax.min.js', dirname( __DIR__ ) ), array( 'jQuery' ), MO2F_VERSION, false );
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
						set_transient( $session_id_encrypt . 'mo2f_transactionId', $response['txId'], 300 );
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
					$mo2fa_login_message = user_can( $current_user->ID, 'manage_options' ) ? MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_DURING_PROCESS_EMAIL ) : MoWpnsMessages::mo2f_get_message( MoWpnsMessages::ERROR_DURING_PROCESS );
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
				} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
						$redirect_to = wc_get_page_permalink( 'myaccount' ); // function exists in WooCommerce plugin.

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
