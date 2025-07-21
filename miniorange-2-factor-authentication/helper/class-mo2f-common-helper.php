<?php
/**
 * This file is contains functions related to KBA method.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use TwoFA\Onprem\Mo2f_KBA_Handler;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Handler\TwofaMethods\Mo2f_GOOGLEAUTHENTICATOR_Handler;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\Mo2f_Inline_Popup;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_Common_Helper' ) ) {
	/**
	 * Class Mo2f_Common_Helper
	 */
	class Mo2f_Common_Helper {

		use Instance;

		/**
		 * Class Mo2f_Common_Helper variable
		 *
		 * @var object
		 */
		private $login_form_url;

		/**
		 * Cunstructor for Mo2f_Common_Helper
		 */
		public function __construct() {
			$this->login_form_url = MoWpnsUtility::get_current_url();
			add_action( 'admin_notices', array( $this, 'mo2f_display_test_2fa_notification' ) );
		}
		/**
		 * Checks if a premium feature file exists for the given plan and returns a tooltip if not.
		 *
		 * @param mixed $feature Type of premium feature used to identify the corresponding file.
		 * @param string $plan_name Name of the subscription plan used for displaying the upgrade tooltip.
		 * @return string
		 */
		public static function mo2f_check_plan( $feature, $plan_name ) {
			global $mo2f_dir_name;
			$filename = 'class-mo2f-' . $feature . '-premium-settings.php';
			$basic_path = rtrim( $mo2f_dir_name, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . $filename;
			if ( file_exists( $basic_path ) ) {
				return '';
			} else {
				return self::mo2f_get_premium_tooltip( $plan_name );
			}
		}
		/**
		 * Tooltip component for displaying the upgrade message.
		 *
		 * @param mixed $plan_name Name of the subscription plan used for displaying the upgrade tooltip.
		 * @return string
		 */
		public static function mo2f_get_premium_tooltip( $plan_name ) {
			return '<span class="mo2f_premium_tooltip">' . MoWpnsConstants::PREMIUM_CROWN . '
                                <span class="mo2f_premium_tooltiptext">
                                    <span class="mo2f_premium_header" onclick="upgradeLink()">' . esc_html( $plan_name ) . '</span><br/>
                                    <span class="mo2f_premium_body">' . esc_html( MoWpnsConstants::MO2F_PREMIUM_PLAN_DESCRIPTION )  . '</span>
                                </span>
                            </span>';
		}
		
		/**
		 * Return the handler object for selected method.
		 *
		 * @param string $selected_method Twofa method name.
		 * @return object
		 */
		public function mo2f_get_object( $selected_method ) {
			$class_name = 'TwoFA\Handler\TwofaMethods\Mo2f_' . str_replace( ' ', '', $selected_method ) . '_Handler';
			if ( class_exists( $class_name ) ) {
				return new $class_name();
			} else {
				$error_prompt = new Mo2f_Login_Popup();
				$current_user = wp_get_current_user();
				$error_prompt->mo2f_show_login_prompt_for_otp_based_methods( MoWpnsMessages::ERROR_DURING_PROCESS, MoWpnsConstants::MO2F_ERROR_MESSAGE_PROMPT, $current_user, '', '', '' );
				exit;
			}
		}

		/**
		 * Gets user from username.
		 *
		 * @param string $username Username.
		 * @return mixed
		 */
		public function mo2f_get_user( $username ) {
			$user = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			return $user;
		}

		/**
		 * Authenticates username and password.
		 *
		 * @param string $username Username.
		 * @param string $password Password.
		 * @param object $user User.
		 * @return mixed
		 */
		public function mo2f_wp_authenticate( $username, $password = '', $user = null ) {
			if ( is_email( $username ) ) {
				$current_user = wp_authenticate_email_password( $user, $username, $password );
			} else {
				$current_user = wp_authenticate_username_password( $user, $username, $password );
			}
			return $current_user;
		}

		/**
		 * Checks if the 2FA is set for this user.
		 *
		 * @param int $current_user_id user id.
		 * @return bool
		 */
		public function mo2f_is_2fa_set( $current_user_id ) {
			global $mo2fdb_queries;
			return MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS === $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $current_user_id );
		}

		/**
		 * It will invoke after inline registration setup success
		 *
		 * @param string $current_user_id It will carry the user id value .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function mo2f_inline_setup_success( $current_user_id, $redirect_to, $session_id ) {
			global $mo2fdb_queries;
			$backup_methods = (array) get_site_option( 'mo2f_enabled_backup_methods' );
			if ( get_site_option( 'mo2f_enable_backup_methods' ) ) {
				if ( in_array( 'backup_kba', $backup_methods, true ) && MoWpnsConstants::SECURITY_QUESTIONS !== $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $current_user_id ) && ! TwoFAMoSessions::get_session_var( 'mo2f_is_kba_backup_configured' . $current_user_id ) ) {
					do_action(
						'mo2f_basic_plan_settings_action',
						'show_kba_registration_form',
						array(
							'user_id'     => $current_user_id,
							'redirect_to' => $redirect_to,
							'session_id'  => $session_id,
						)
					);
				}
				TwoFAMoSessions::unset_session( 'mo2f_is_kba_backup_configured' . $current_user_id );
				if ( in_array( 'mo2f_back_up_codes', $backup_methods, true ) ) {
					$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user_id );
					if ( empty( $mo2f_user_email ) ) {
						$currentuser     = get_user_by( 'id', $current_user_id );
						$mo2f_user_email = $currentuser->user_email;
					}
					$generate_backup_code = new MocURL();
					$codes                = apply_filters( 'mo2f_basic_plan_settings_filter', $generate_backup_code->mo2f_get_backup_codes( $mo2f_user_email, site_url() ), 'generate_backup_codes', array( 'user_id' => $current_user_id ) );
					if ( ! is_array( $codes ) ) {
						$codes = explode( ' ', trim( $codes ) );
					}
					$common_helper = new Mo2f_Common_Helper();
					$mo2f_message  = $common_helper->mo2f_check_backupcode_status( $codes, $current_user_id );
					if ( ! $mo2f_message ) {
						$inline_popup = new Mo2f_Inline_Popup();
						$codes        = $this->mo2f_send_backupcodes_inline( $current_user_id, $codes, $mo2f_user_email );
						$inline_popup->mo2f_show_generated_backup_codes_inline( $redirect_to, $session_id, $codes );
					}
				}
			}
			$pass2fa = new Mo2f_Main_Handler();
			$pass2fa->mo2fa_pass2login( $redirect_to, $session_id );
			exit;
		}

		/**
		 * Sends backup codes on users email.
		 *
		 * @param int    $user_id User id.
		 * @param mixed  $codes Backup codes.
		 * @param string $mo2f_user_email user email.
		 * @return mixed.
		 */
		public function mo2f_send_backupcodes_inline( $user_id, $codes, $mo2f_user_email ) {
			$result = MO2f_Utility::mo2f_email_backup_codes( $codes, $mo2f_user_email );
			update_user_meta( $user_id, 'mo_backup_code_generated', 1 );
			update_user_meta( $user_id, 'mo_backup_code_screen_shown', 1 );
			return $codes;
		}

		/**
		 * Inline invoke 2fa
		 *
		 * @param object $currentuser It will carry the current user detail .
		 * @param string $redirect_to It will carry the redirect url .
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function mo2fa_inline( $currentuser, $redirect_to, $session_id ) {
			global $mo2fdb_queries;
			$current_user_id = $currentuser->ID;
			$email           = $currentuser->user_email;
			$mo2fdb_queries->mo2f_update_user_details(
				$current_user_id,
				array(
					'user_registration_with_miniorange'   => 'SUCCESS',
					'mo2f_user_email'                     => $email,
					'mo_2factor_user_registration_status' => 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR',
				)
			);
			$user_id      = MO2f_Utility::mo2f_get_transient( $session_id, 'mo2f_current_user_id' );
			$inline_popup = new Mo2f_Inline_Popup();
			$inline_popup->prompt_user_to_select_2factor_mthod_inline( $user_id, '', $redirect_to, $session_id );
			exit;
		}

		/**
		 * Removing the current activity
		 *
		 * @param string $session_id It will carry the session id .
		 * @return void
		 */
		public function mo2f_remove_current_activity( $session_id ) {
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

			MO2f_Utility::unset_session_variables( $session_variables, $session_id );
			TwoFAMoSessions::unset_session( 'mo2f_show_error_message' );
			TwoFAMoSessions::unset_session( 'mo2f_show_defult_login_form' );
			TwoFAMoSessions::unset_session( 'mo2f_change_error_message' );
			$key             = get_site_option( 'mo2f_encryption_key' );
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
		 * This function will return the configured method value
		 *
		 * @param string $currentuserid It will carry the current user id .
		 * @return array
		 */
		public function mo2fa_return_methods_value( $currentuserid ) {
			global $mo2fdb_queries;
			$count_methods = $mo2fdb_queries->mo2f_get_user_configured_methods( $currentuserid );
			if ( ! empty( $count_methods ) && is_array( $count_methods ) && isset( $count_methods[0] ) && is_object( $count_methods[0] ) ) {
				$value = get_object_vars( $count_methods[0] );
			} else {
				$value = array();
			}
			$configured_methods_arr = array();
			foreach ( $value as $config_status_option => $config_status ) {
				if ( strpos( $config_status_option, 'config_status' ) ) {
					$config_status_string_array = explode( '_', $config_status_option );
					$config_method              = MoWpnsConstants::mo2f_convert_method_name( $config_status_string_array[1], 'pascal_to_cap' );
					if ( (int) $value[ $config_status_option ] ) {
						array_push( $configured_methods_arr, $config_method );
					}
				}
			}
			return $configured_methods_arr;
		}

		/**
		 * Check if the backcodes are error.
		 *
		 * @param mixed $status Code status.
		 * @param int   $user_id User ID.
		 * @return mixed
		 */
		public function mo2f_check_backupcode_status( $status, $user_id ) {
			$status = is_array( $status ) ? $status[0] : $status;
			$error_status_and_message = array(
				'InternetConnectivityError' => MoWpnsMessages::BACKUP_CODE_INTERNET_ISSUE,
				'AllUsed'                   => MoWpnsMessages::BACKUP_CODE_ALL_USED,
				'UserLimitReached'          => MoWpnsMessages::BACKUP_CODE_DOMAIN_LIMIT_REACH,
				'LimitReached'              => MoWpnsMessages::BACKUP_CODE_LIMIT_REACH,
				'invalid_request'           => MoWpnsMessages::BACKUP_CODE_INVALID_REQUEST,
			);
			foreach ( $error_status_and_message as $error_status => $error_message ) {
				if ( $error_status === $status ) {
					return $error_status_and_message[ $status ];
				}
			}
			update_user_meta( $user_id, 'mo_backup_code_generated', 1 );
			return false;
		}
		/**
		 * Sends backup codes on email.
		 *
		 * @param array  $codes Backup codes.
		 * @param string $mo2f_user_email User email.
		 * @param int    $user_id User ID.
		 * @return string
		 */
		public function mo2f_send_backcodes_on_email( $codes, $mo2f_user_email, $user_id ) {
			$result = MO2f_Utility::mo2f_email_backup_codes( $codes, $mo2f_user_email );
			if ( $result ) {
				$mo2fa_login_message = MoWpnsMessages::BACKUP_CODES_SENT_SUCCESS;
				update_user_meta( $user_id, 'mo_backup_code_generated', 1 );
			} else {
				$mo2fa_login_message = MoWpnsMessages::BACKUP_CODE_SENT_ERROR;
				update_user_meta( $user_id, 'mo_backup_code_generated', 0 );
			}
			return $mo2fa_login_message;
		}

		/**
		 * Checks if mfa enabled.
		 *
		 * @param array $configure_array_method Twofa methods.
		 * @return bool
		 */
		public function mo2f_check_mfa_details( $configure_array_method ) {
			return ( count( $configure_array_method ) > 1 ) && ( (int) get_site_option( 'mo2f_multi_factor_authentication' ) === 1 );
		}

		/**
		 * It is useful for grace period
		 *
		 * @param object $currentuser It will carry the current user .
		 * @return string
		 */
		public function mo2f_is_grace_period_expired( $currentuser ) {
			$grace_period_set_time = get_user_meta( $currentuser->ID, 'mo2f_grace_period_start_time', true );
			if ( ! $grace_period_set_time ) {
				return false;
			}
			$grace_period = get_site_option( 'mo2f_grace_period_value' );
			if ( get_site_option( 'mo2f_grace_period_type' ) === 'hours' ) {
				$grace_period = $grace_period * 60 * 60;
			} else {
				$grace_period = $grace_period * 24 * 60 * 60;
			}

			$total_grace_period = $grace_period + (int) $grace_period_set_time;
			$current_time_stamp = strtotime( current_datetime()->format( 'h:ia M d Y' ) );
			return $total_grace_period <= $current_time_stamp;
		}

		/**
		 * Gets the previous screen for inline.
		 *
		 * @param int $user_id User id.
		 * @return string
		 */
		public function mo2f_get_previous_screen_for_inline( $user_id ) {
			return ! get_user_meta( $user_id, 'mo2f_user_profile_set', true ) ? 'mo2f_inline_form' : 'mo2f_login_form';
		}

		/**
		 * Go back link form.
		 *
		 * @param string $prev_screen Previous screen.
		 * @return string
		 */
		public function mo2f_go_back_link_form( $prev_screen ) {
			$html = '	<a href="#' . esc_attr( $prev_screen ) . '" style="color:#828783;text-decoration:none;">
			<span style="float:left;" class="text-with-arrow text-with-arrow-left" >
			<svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
			<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
			</svg>' . esc_html__( 'Go Back', 'miniorange-2-factor-authentication' ) . '
			</span>
			</a>';
			return $html;
		}

		/**
		 * Back to inline registration form.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @return string
		 */
		public function mo2f_backto_inline_registration_form1( $session_id_encrypt, $redirect_to ) {
			$html = '<form name="f" id="mo2f_backto_inline_registration" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
				<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
				<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
				<input type="hidden" name="option" value="miniorange2f_back_to_inline_registration"> 
				<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			</form>';
			return $html;
		}

		/**
		 * Back to mfa form.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @return string
		 */
		public function mo2f_backto_mfa_form( $session_id_encrypt, $redirect_to ) {
			$html = '		<form name="f" id="mo2f_backto_mfa_form" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
			<input type="hidden" name="option" value="mo2f_back_to_mfa_screen"> 
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			</form>';
			return $html;
		}

		/**
		 * Back to mfa form.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @return string
		 */
		public function mo2f_backto_mfa_form1( $session_id_encrypt, $redirect_to ) {
			$html = '<form name="f" id="mo2f_backto_mfa_form" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
			<input type="hidden" name="option" value="mo2f_back_to_mfa_screen"> 
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			</form>';
			return $html;
		}

		/**
		 * Back to login form.
		 *
		 * @return string
		 */
		public function mo2f_backto_login_form() {
			$html = '<form name="f" id="mo2f_backto_mo_loginform" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
		</form>';
			return $html;
		}

		/**
		 * Back to 2FA validation screen.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @param string $twofa_method Twofa method.
		 * @return string
		 */
		public function mo2f_backto_2fa_validation_screen_form( $session_id_encrypt, $redirect_to, $twofa_method ) {
			$html = '<form name="f" id="mo2f_backto_2fa_validation" method="post" action="" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
			<input type="hidden" name="option" value="mo2f_back_to_2fa_validation_screen"> 
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="twofa_method" value="' . esc_attr( $twofa_method ) . '"/>
	</form>';
			return $html;
		}

		/**
		 * Gets hiddedn forms at login.
		 *
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @param string $login_status Login status.
		 * @param string $login_message Login message.
		 * @param string $twofa_method Twofa method.
		 * @param int    $user_id User id.
		 * @return string
		 */
		public function mo2f_get_hidden_forms_login( $redirect_to, $session_id_encrypt, $login_status, $login_message, $twofa_method, $user_id ) {
			$html  = $this->mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message, $twofa_method );
			$html .= $this->mo2f_backto_inline_registration_form( $session_id_encrypt, $redirect_to );
			$html .= $this->mo2f_backto_mfa_form( $session_id_encrypt, $redirect_to );
			$html .= $this->mo2f_backto_login_form();
			$html .= $this->mo2f_backto_2fa_validation_screen_form( $session_id_encrypt, $redirect_to, $twofa_method );
			$html .= $this->mo2f_get_reconfiguration_link_hidden_forms( $redirect_to, $session_id_encrypt, $twofa_method );
			$html .= apply_filters(
				'mo2f_premium_common_helper',
				'',
				'mo2f_get_backup_method_hidden_form',
				array(
					'redirect_to'        => $redirect_to,
					'session_id_encrypt' => $session_id_encrypt,
					'twofa_method'       => $twofa_method,
				)
			);
			$html .= apply_filters(
				'mo2f_premium_common_helper',
				'',
				'mo2f_get_rba_consent_hidden_form',
				array(
					'redirect_to'        => $redirect_to,
					'session_id_encrypt' => $session_id_encrypt,
					'user_id'            => $user_id,
				)
			);
			$html .= apply_filters(
				'mo2f_premium_common_helper',
				'',
				'mo2f_get_rem_ip_consent_hidden_form',
				array(
					'redirect_to'        => $redirect_to,
					'session_id_encrypt' => $session_id_encrypt,
					'user_id'            => $user_id,
				)
			);
			$html .= $this->mo2f_get_validation_success_form( $redirect_to, $session_id_encrypt, $user_id );
			return $html;
		}

		/**
		 * Gets hidden for validation success.
		 *
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session id.
		 * @param int    $current_user_id User id.
		 * @return string
		 */
		public function mo2f_get_validation_success_form( $redirect_to, $session_id, $current_user_id ) {
			global $mo2fdb_queries;
			$html = '<form name="mo2f_inline_otp_validated_form" method="post" action="" id="mo2f_inline_otp_validated_form" style="display:none;">
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
			<input type="hidden" name="option" value="mo2f_process_validation_success"/>
			<input type="hidden" name="twofa_status" value="' . esc_attr( $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $current_user_id ) ) . '"/> 
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
		</form>';

			return $html;
		}

		/**
		 * Reconfiguration link hidden forms
		 *
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @param string $twofa_method Twofa method.
		 * @return string
		 */
		public function mo2f_get_reconfiguration_link_hidden_forms( $redirect_to, $session_id_encrypt, $twofa_method ) {
			$html = '<form name="f" id="mo2f_send_reconfig_link" method="post" action="" style="display:none;">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '" />
			<input type="hidden" name="option" value="mo2f_send_reconfig_link">
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
			<input type="hidden" name="mo2f_login_method" value="' . esc_attr( $twofa_method ) . '"/>
		</form>';
			return $html;
		}

		/**
		 * Gets hidden script for login.
		 *
		 * @return string
		 */
		public function mo2f_get_hidden_script_login() {

			$script = '<script>jQuery("a[href=\'#mo2f_backup_option\']").click(function() {
				jQuery("#mo2f_backup").submit();
			});
			jQuery("a[href=\'#mo2f_backup_generate\']").click(function() {
				jQuery("#mo2f_create_backup_codes").submit();
			});
			jQuery("a[href=\'#mo2f_send_reconfig_link\']").click(function() {
				jQuery("#mo2f_send_reconfig_link").submit();
			});
			jQuery("a[href=\'#kba_backup_method_link\']").click(function() {
				jQuery("#mo2f_backup_method_form").submit();
			});
			jQuery("a[href=\'#mo2f_validation_screen\']").click(function() {
				jQuery("#mo2f_backto_2fa_validation").submit();
			});
			jQuery("a[href=\'#mo2f_inline_form\']").click(function() {
				jQuery("#mo2f_backto_inline_registration").submit();
			});
			jQuery("a[href=\'#mo2f_mfa_form\']").click(function() {
				jQuery("#mo2f_backto_mfa_form").submit();
			});
			jQuery("#miniorange_trust_device_yes").click(function() {
				jQuery("#mo2f_trust_device_confirm_form").submit();
			});
			jQuery("#miniorange_trust_device_no").click(function() {
				jQuery("#mo2f_trust_device_cancel_form").submit();
			});
			jQuery("#mo2f_remember_ip_yes").click(function() {
				jQuery("#mo2f_remember_ip_confirm_form").submit();
			});
			jQuery("#mo2f_remember_ip_no").click(function() {
				jQuery("#mo2f_remember_ip_cancel_form").submit();
			});
			jQuery("a[href=\'#mo2f_login_form\']").click(function() {
			    jQuery("#mo2f_backto_mo_loginform").submit();
			});</script>';
			return $script;
		}

		/**
		 * Reconfiguration link hidden forms
		 *
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id_encrypt Session id.
		 * @param int    $user_id User id.
		 * @return string
		 */
		public function mo2f_get_hidden_forms_for_ooba( $redirect_to, $session_id_encrypt, $user_id ) {
			global $mo2fdb_queries;
			$html = '<form name="f" id="mo2f_mobile_validation_form" method="post" class="mo2f_display_none_forms">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce"
				value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
				<input type="hidden" name="option" value="mo2f_email_verification_success">
				<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
				<input type="hidden" name="tx_type"/>
				<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
				<input type="hidden" name="twofa_status" value="' . esc_attr( $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $user_id ) ) . '"/>    
			</form>
			<form name="f" id="mo2f_email_verification_failed_form" method="post" action="' . esc_url( wp_login_url() ) . '"
			class="mo2f_display_none_forms">
			<input type="hidden" name="option" value="mo2f_email_verification_failed">
			<input type="hidden" name="miniorange_inline_save_2factor_method_nonce"
				value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
			<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
			<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
		</form>';
			return $html;
		}

		/**
		 * This function used for creation of backup codes
		 *
		 * @param string $redirect_to redirect url.
		 * @param string $session_id_encrypt encrypted session id.
		 * @param string $login_status login status of user.
		 * @param string $login_message message used to show success/failed login actions.
		 * @param string $login_method login method of user.
		 * @return string
		 */
		public function mo2f_create_backup_form( $redirect_to, $session_id_encrypt, $login_status, $login_message, $login_method = '' ) {
			$html  = '<form name="f" id="mo2f_backup" method="post" action="" style="display:none;">
		<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '" />
		<input type="hidden" name="option" value="mo2f_use_backup_codes">
		<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '" />
		<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '" />
		<input type="hidden" name="login_method" value="' . esc_attr( $login_method ) . '" />
	</form>';
			$html .= '<form name="f" id="mo2f_create_backup_codes" method="post" action="" style="display:none;">
		<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '" />
		<input type="hidden" name="option" value="mo2f_send_backup_codes">
		<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '" />
		<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '" />
		<input type="hidden" name="login_method" value="' . esc_attr( $login_method ) . '" />
		<input type="hidden" name="login_status" value="' . esc_attr( $login_status ) . '" />
		<input type="hidden" name="login_message" value="' . wp_kses( $login_message, array( 'b' => array() ) ) . '" />
	</form>';
			return $html;
		}

		/**
		 * Back to login form.
		 *
		 * @return string
		 */
		public function mo2f_backto_login_form1() {
			$html = '<form name="f" id="mo2f_backto_mo_loginform" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
			</form>';
			return $html;
		}

		/**
		 * Returns skeleton values for OTP Over SMS.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_sms_common_skeleton( $user_id ) {
			global $mo2fdb_queries;
			$mo2f_user_phone = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_phone', $user_id );
			$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : '';
			$skeleton        = array(
				'##input_field##'  => '<br><span class="mo2f_phone_field_label">' . esc_html__( 'Enter Your Phone', 'miniorange-2-factor-authentication' ) . '</span><br><br><input class="mo2f_phone_field mb-mo-4" type="text" name="mo2f_phone_email_telegram" id="mo2f_phone_field"
                                    value="' . esc_attr( $user_phone ) . '" pattern="' . esc_attr( MoWpnsConstants::PHONE_PATTERN ) . '"
                                    title="' . esc_attr__( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/>',
				'##instructions##' => '',
			);
			return $skeleton;
		}

		/**
		 * Returns skeleton values for OTP Over Email.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_email_common_skeleton( $user_id ) {
			global $mo2fdb_queries;
			if ( ! $user_id && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$mo2f_user_email = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user_id );
			$email           = $mo2f_user_email ? $mo2f_user_email : get_user_by( 'id', $user_id )->user_email;
			$skeleton        = array(
				'##input_field##'  => '<br><div class="modal-body" style="height:auto;">
                                    <span class="mo2f_phone_field_label">' . esc_html__( 'Enter your Email:', 'miniorange-2-factor-authentication' ) . '</span>
                                    <input type ="text" class="mo2f_phone_field" pattern="' . esc_attr( MoWpnsConstants::EMAIL_PATTERN ) . '" name="mo2f_phone_email_telegram"  size="30" required value="' . esc_attr( $email ) . '"/><br>
                                    </div>',
				'##instructions##' => '',
			);
			return $skeleton;
		}

		/**
		 * Returns skeleton values for OTP Over Telegram.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_telegram_common_skeleton( $user_id ) {
			$chat_id  = get_user_meta( $user_id, 'mo2f_chat_id', true );
			$chat_id  = $chat_id ? $chat_id : '';
			$skeleton = array(
				'##input_field##'  => '<input class="mo2f_phone_field" type="text" name="mo2f_phone_email_telegram" id="mo2f_telegram"
                                    value="' . esc_attr( $chat_id ) . '" pattern="[0-9]+" 
                                    title="' . esc_attr__( 'Enter Chat ID recieved on your Telegram without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/><br></h4>',
				'##instructions##' => '<h4 class="mo_wpns_not_bold">' . esc_html__( '1. Open the telegram app and search for \'miniorange2fa\'. Click on start button or send \'/start\' message.', 'miniorange-2-factor-authentication' ) . '</h4>
                                    <h4 class="mo_wpns_not_bold">' . esc_html__( '2. Enter the recieved chat id in the below box.', 'miniorange-2-factor-authentication' ) . '</h4>',

			);
			return $skeleton;
		}

		/**
		 * Returns skeleton values for OTP Over WhatsApp.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		public function mo2f_whatsapp_common_skeleton( $user_id ) {
			global $mo2fdb_queries;
			$mo2f_user_phone = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_whatsapp', $user_id );
			$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : '';
			$skeleton        = array(
				'##input_field##'  => '<br><span class="mo2f_phone_field_label" class="mo2f_middle_text"><i>' . esc_html__( 'Enter Your Phone', 'miniorange-2-factor-authentication' ) . ':</i></span><br><br>
				                    <input class="mo2f_phone_field mb-mo-4" type="text" name="mo2f_phone_email_telegram" id="mo2f_phone_field"
                                    value="' . esc_attr( $user_phone ) . '" pattern="' . esc_attr( MoWpnsConstants::PHONE_PATTERN ) . '"
                                    title="' . esc_attr__( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/>',
				'##instructions##' => '',
			);
			return $skeleton;
		}

		/**
		 * Gets script for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_dashboard_script_for_otp_based_methods() {
			$common_helper = new Mo2f_Common_Helper();
			$script        = '<script>
			jQuery("#verify").click(function() {
			' . $common_helper->mo2f_show_loader() . '
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_send_otp_for_configuration",
					mo2f_otp_based_method: jQuery("input[name=mo2f_otp_based_method]").val(),
					mo2f_phone_email_telegram: jQuery("input[name=mo2f_phone_email_telegram]").val(),
					mo2f_session_id: jQuery("input[name=mo2f_session_id]").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    ' . $common_helper->mo2f_hide_loader() . '
					if (response.success) {
						jQuery("#go_back_verify").css("display", "none");
						jQuery("#mo2f_validateotp_form").css("display", "block");
						jQuery("input[name=otp_token]").focus();
						mo2f_show_message(response.data);
					} else {
						mo2f_show_message(response.data);
					}
				});
			});
			
			jQuery("#validate").click(function() {
			   ' . $common_helper->mo2f_show_loader() . '
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_validate_otp_for_configuration",
					mo2f_otp_based_method: jQuery("input[name=mo2f_otp_based_method]").val(),
					otp_token: jQuery("input[name=otp_token]").val(),
					mo2f_session_id: jQuery("input[name=mo2f_session_id]").val(),
					mo2f_phone_email_telegram: jQuery("input[name=mo2f_phone_email_telegram]").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    ' . $common_helper->mo2f_hide_loader() . '
					if (response.success) {
						jQuery("#mo2f_2factor_test_prompt_cross").submit();
					} else {
						mo2f_show_message(response.data);
					}
				});
			});
			</script>';
			return $script;
		}

		/**
		 * Gets script for otp based methods.
		 *
		 * @param string $twofa_flow Twofa flow.
		 * @return string
		 */
		public function mo2f_get_script_for_otp_based_methods( $twofa_flow ) {
			$call_to_function = array( $this, 'mo2f_get_validate_success_response_' . $twofa_flow . '_script' );
			$common_helper    = new Mo2f_Common_Helper();
			$script           = '<script>	jQuery(document).ready(function($){
				jQuery(function(){
				var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";
				var selected_2FA_method = jQuery("input[name=mo2f_otp_based_method]").val();
				jQuery("#verify").click(function()
				{   
				' . $common_helper->mo2f_show_loader() . '
					var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
					var data = {
						"action"  : "mo_two_factor_ajax",
						"mo_2f_two_factor_ajax"  : "mo2f_send_otp_for_configuration",
						"mo2f_otp_based_method" : selected_2FA_method,
						"mo2f_phone_email_telegram" : jQuery("input[name=mo2f_phone_email_telegram]").val(),
						"mo2f_session_id"  : jQuery("input[name=mo2f_session_id]").val(),
						"nonce"  : nonce,	
					};
					jQuery.post(ajaxurl, data, function(response) {
					    ' . $common_helper->mo2f_hide_loader() . '
						if( response["success"] ){
							if( selected_2FA_method == "' . esc_js( MoWpnsConstants::OUT_OF_BAND_EMAIL ) . '"){
								jQuery("#showPushImage").css("display","block");
								jQuery("#verify").css("display","none");
								emailVerificationPoll();
							} else{
								jQuery("#go_back_verify").css("display","none");
								jQuery("#mo2f_validateotp_form").css("display","block");
								jQuery("#verify").css("display","none");
								jQuery("input[name=otp_token]").focus();
							}
							mo2f_show_message(response["data"]);
						}else if( ! response["success"] ){
							mo2f_show_message(response["data"]);
						}else{
							mo2f_show_message("Unknown error occured. Please try again!");
						}
					});
				});
			jQuery("#validate").click(function()
			{   ' . $common_helper->mo2f_show_loader() . '
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
					var data = {
						"action"  : "mo_two_factor_ajax",
						"mo_2f_two_factor_ajax"   : "mo2f_validate_otp_for_configuration",
						"mo2f_otp_based_method"  : jQuery("input[name=mo2f_otp_based_method]").val(),
						"otp_token"  : jQuery("input[name=otp_token]").val(),
						"mo2f_session_id"  : jQuery("input[name=mo2f_session_id]").val(),
						"mo2f_phone_email_telegram" : jQuery("input[name=mo2f_phone_email_telegram]").val(),
						"nonce"  : nonce,	
					};
				jQuery.post(ajaxurl, data, function(response) {
				    ' . $common_helper->mo2f_hide_loader() . '
					if( response["success"] ){
						' . call_user_func( $call_to_function ) . '
					}else if( ! response["success"] ){
						mo2f_show_message(response["data"]);
					}else{
						mo2f_show_message("Unknown error occured. Please try again!");
					}
				});
			});
			jQuery(\'#mo2f_next_step3\').css(\'display\',\'none\');	
		});
	});</script>';
			return $script;
		}
		/**
		 * Gets script response for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_validate_success_response_dashboard_script() {
			$script = 'jQuery("#mo2f_2factor_test_prompt_cross").submit();';
			return $script;
		}

		/**
		 * Gets script respnse for inline.
		 *
		 * @return string
		 */
		public function mo2f_get_validate_success_response_inline_script() {
			$script = 'jQuery("#mo2f_inline_otp_validated_form").submit();';
			return $script;
		}

		/**
		 * Get hidden forms for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_dashboard_hidden_forms() {
			$html = '<form name="f" method="post" action="" id="mo2f_2factor_test_prompt_cross">
			<input type="hidden" name="option" value="mo2f_2factor_test_prompt_cross"/>
			<input type="hidden" name="mo2f_2factor_test_prompt_cross_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-2factor-test-prompt-cross-nonce' ) ) . '"/>
		</form>';
			return $html;
		}

		/**
		 * Back to inline registration form.
		 *
		 * @param string $session_id_encrypt Session id.
		 * @param string $redirect_to Redirection url.
		 * @return string
		 */
		public function mo2f_backto_inline_registration_form( $session_id_encrypt, $redirect_to ) {
			$html = '<form name="f" id="mo2f_backto_inline_registration" method="post" action="' . esc_url( $this->login_form_url ) . '" class="mo2f_display_none_forms">
					<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '"/>
					<input type="hidden" name="session_id" value="' . esc_attr( $session_id_encrypt ) . '"/>
					<input type="hidden" name="option" value="miniorange2f_back_to_inline_registration"> 
					<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
					</form>';
			return $html;
		}

		/**
		 * Show inline popup for OTP over SMS/Email/Telegram
		 *
		 * @param array  $skeleton Skeleton values.
		 * @param string $current_selected_method Twofa method.
		 * @param string $login_message Login message.
		 * @param int    $current_user_id User id.
		 * @param string $redirect_to Redirection Url.
		 * @param string $session_id Session id.
		 * @param string $prev_screen Previous screen.
		 * @return string
		 */
		public function mo2f_otp_based_methods_configuration_screen( $skeleton, $current_selected_method, $login_message, $current_user_id, $redirect_to, $session_id, $prev_screen ) {
			$show_validation_form = 'none';
			$common_helper        = new Mo2f_Common_Helper();
			$html                 = '<div id="mo2f_2fa_popup_dashboard_loader" class="modal" hidden></div>';
			$html                .= '<div class="mo2f-setup-popup-dashboard">';
			$html                .= '<div class="login mo_customer_validation-modal-content">';
			$html                .= '<div class="mo2f_modal-header">
			<h4 class="mo2f_modal-title">';
			$html                .=
				'<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '" onclick="mologinback();">
					<span aria-hidden="true">&times;</span>
				</button>';
				$html            .= esc_html__( 'Configure ' . MoWpnsConstants::mo2f_convert_method_name( $current_selected_method, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
				$html            .= '</h4>
				</div>';
			$html                .= '<div class="mo2f_modal-body"> 
			        <div class="mo2f_login_form_border_otp">
						<div id="mo2f-otpMessagehide" class="hidden">
							<p class="mo2fa_display_message_frontend mo_feedback_text">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
						</div>

						<div class="mo2f_row">
							<form name="f" method="post" action="" id="mo2f_inline_verifyphone_form">
								';

					$html .= wp_kses(
						$skeleton['##instructions##'],
						array(
							'h4' => array(
								'clase' => array(),
								'style' => array(),

							),
							'b'  => array(),

						)
					);
					$html .=

						wp_kses(
							$skeleton['##input_field##'],
							array(
								'div'   => array(
									'style' => array(),
									'class' => array(),
								),
								'h2'    => array(),
								'i'     => array(),
								'br'    => array(),
								'input' => array(
									'id'      => array(),
									'class'   => array(),
									'name'    => array(),
									'type'    => array(),
									'value'   => array(),
									'style'   => array(),
									'pattern' => array(),
									'title'   => array(),
									'size'    => array(),

								),
								'a'     => array(
									'href'   => array(),
									'target' => array(),
								),
								'span'  => array(
									'title' => array(),
									'class' => array(),
									'style' => array(),
								),

							)
						);
							$html     .= '
				
								<br>
								<input type="hidden" name="option" value="mo2f_send_otp_for_configuration"/>
								<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $current_selected_method ) . '"/>
								<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id ) . '"/>
								<input type="button" id ="verify" name="verify" class="mo2f-save-settings-button" value="' . esc_attr__( 'Send ' . ( MoWpnsConstants::OUT_OF_BAND_EMAIL !== $current_selected_method ? 'OTP' : 'Link' ), 'miniorange-2-factor-authentication' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
								$html .= '" />
								<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
							</form>
						</div>  

						<form name="f" method="post" action="" id="mo2f_validateotp_form" style="display:' . esc_attr( $show_validation_form ) . '">
							<input type="hidden" name="option" value="mo2f_validate_otp_for_configuration"/>
							<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id ) . '"/>
							<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $current_selected_method ) . '"/>
							<input type="hidden" name="mo2f_validate_otp_for_configuration_nonce" value=" ' . esc_attr( wp_create_nonce( 'mo2f-configure-otp-based-methods-validate-nonce' ) ) . '"/> <div class="mo2f_phone_field_label">' . esc_html__( 'Enter One Time Passcode', 'miniorange-2-factor-authentication' ) . '</div>
							<input class="mo2f_enter_otp_field"  autofocus="true" type="text" name="otp_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '"/> <a href="#resendsmslink" class="mo2f_resend_link">' . esc_html__( 'Resend OTP', 'miniorange-2-factor-authentication' ) . '</a>
							<br><br>
							<input type="button" name="validate" id="validate" class="mo2f-save-settings-button" value="' . esc_attr__( 'Validate OTP', 'miniorange-2-factor-authentication' ) . '"/>
						</form>
						';
						$html         .= ' 	<div id="showPushImage" style="display:none;">
						<div class="mo2fa_text-align-center">We are waiting for your approval...</div>
<div class="mo2fa_text-align-center">
   <img src="' . esc_url( plugins_url( 'includes/images/email-loader.gif', dirname( __FILE__ ) ) ) . '"/>
</div></div></div><br>';
			if ( 'mo2f_inline_form' === $prev_screen ) {
				$prev_screen = 'mo2f_inline_form';
				$html       .= $common_helper->mo2f_go_back_link_form( $prev_screen );
			}
				$html .= $common_helper->mo2f_customize_logo();
				$html .= '
					</div>
				</div>
			</div>';
			$html     .= '<script>
			jQuery(document).ready(function ($) {
				const otpInput = document.querySelector(".mo2f_enter_otp_field");
				if (otpInput) {
					otpInput.addEventListener("input", function () {
					this.value = this.value.replace(/[^a-zA-Z0-9]/g, "");
					});
				}
			});
			function mologinback() {
				jQuery("#mo2f_backto_mo_loginform").submit();
				jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
				closeVerification = true;
			}';
			$html     .= 'jQuery("#mo2f_phone_field").intlTelInput();';
			$html     .= 'jQuery("#go_back").click(function () {
			jQuery("#mo2f_go_back_form").submit();
		});';
			$html     .= 'jQuery("#go_back_verify").click(function () {
			jQuery("#mo2f_go_back_form").submit();
		});';

			$html .= 'jQuery("a[href=\"#resendsmslink\"]").click(function (e) {
			jQuery("#verify").click();
		});';
			$html .= 'jQuery("input[name=mo2f_phone_email_telegram]").keypress(function(e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery("#verify").click();
				jQuery("input[name=otp_token]").focus();
			}

		});';
			$html .= 'jQuery("input[name=otp_token]").keypress(function(e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery("#validate").click();
			}

		});';
			$html .= 'jQuery("a[href=\"#mo2f_inline_form\"]").click(function() {
			jQuery("#mo2f_backto_inline_registration").submit();
		});';
			$html .= "
		function mo2f_show_message(response) {
			var html = '<div id=\"mo2f-otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
			jQuery('#mo2f-otpMessage').remove();
			jQuery('#mo2f-otpMessagehide').after(html);
		}
		</script>";
			return $html;
		}

		/**
		 * This function shows miniorange registration screen
		 *
		 * @param string $login_message message used to show success/failed login actions.
		 * @param string $redirect_to redirect url.
		 * @param string $session_id session id.
		 * @param string $prev_screen Prev_screen.
		 * @param array  $skeleton Skeleton.
		 * @return string
		 */
		public function mo2f_get_miniorange_user_registration_prompt( $login_message, $redirect_to, $session_id, $prev_screen, $skeleton ) {
			$success_response = array( $this, 'mo2f_get_mo_login_registration_success_response_' . $prev_screen . '_script' );
			$error_response   = array( $this, 'mo2f_get_mo_login_registration_error_response_' . $prev_screen . '_script' );
			$common_helper    = new Mo2f_Common_Helper();

			$html  = '<div>';
			$html .= $skeleton['##crossbutton##'];
			$html .= '<div>';
			$html .= '<div id="mo2f-otpMessagehide" class="hidden">
						<p>' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
					  </div>';
			$html .= '<form name="mo2f_inline_register_form" id="mo2f_inline_register_form" method="post" class="mo2f-padding-6" hidden>
						<input type="hidden" name="option" value="miniorange_inline_register" />
						<input type="hidden" name="mo2f_inline_register_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-inline-register-nonce' ) ) . '"/>
						<div class="mo2f-bg-white mo2f-rounded-xl flex flex-col">
							<p class="mo2f-heading mo2f-mb-1_5">' . esc_html__( 'Create New Account', 'miniorange-2-factor-authentication' ) . '</p>
							<div class="w-full mo-input-wrapper group">
								<label class="mo-input-label">Email</label>
								<input class="w-full mo2f-input" type="email" name="email" required placeholder="person@example.com" />
							</div>
							<div class="w-full mo-input-wrapper group">
								<label class="mo-input-label">Password</label>
								<input class="w-full mo2f-input" type="password" name="password" required placeholder="Choose your password (Min. length 6)" />
							</div>
							<div class="w-full mo-input-wrapper group">
								<label class="mo-input-label">Confirm Password</label>
								<input class="w-full mo2f-input" type="password" name="confirmPassword" required placeholder="Confirm your password" />
							</div>
							<div class="flex">
								<input type="checkbox" class="mo2f-plugin-policy-check" id="mo2f_agree_plugin_policy" name="mo2f_customer_validation_agree_plugin_policy" value="1" />
								<label for="mo2f_agree_plugin_policy">
									' . wp_kses(
								sprintf(
									/* translators: %1$s: End User Agreement link, %2$s: Plugin Privacy Policy link */
									esc_html__( 'I have read and agree to the %1$s and %2$s', 'miniorange-2-factor-authentication' ),
									'<u><i><a target="_blank" class="mo2f-font-bold" href="https://plugins.miniorange.com/end-user-license-agreement#v5-software-warranty-refund-policy">' . esc_html__( 'refund policy', 'miniorange-2-factor-authentication' ) . '</a></i></u>',
									'<u><i><a target="_blank" class="mo2f-font-bold" href="https://plugins.miniorange.com/wp-content/uploads/2023/08/Plugins-Privacy-Policy.pdf">' . esc_html__( 'plugin privacy policy', 'miniorange-2-factor-authentication' ) . '</a></i></u>'
								),
								array(
									'a' => array(
										'target' => array(),
										'class'  => array(),
										'href'   => array(),
									),
									'u' => array(),
									'i' => array(),
								)
							) . '
								</label>
							</div>
							<div class="mo2f_login_register_button">
								<input type="button" name="submit" value="Create Account" id="mo2f_register" class="mo2f-save-settings-button" disabled />
								&nbsp;&nbsp;&nbsp;&nbsp;
								<a href="#mo2f_account_exist">
									<button class="mo2f-reset-settings-button">Already have an account?</button>
								</a>
							</div>
						</div>
					  </form>';
			$html .= '<form name="mo2f_inline_login_form" id="mo2f_inline_login_form" method="post" class="mo2f-padding-6 items-center">
						<input type="hidden" name="option" value="miniorange_inline_login"/>
						<input type="hidden" name="mo2f_inline_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-inline-login-nonce' ) ) . '"/>
						<div class="mo2f-bg-white mo2f-rounded-xl w-[75%] flex px-mo-32 flex-col mo2f-gap-1">
							<p class="mo2f-heading mo2f-mb-1_5">Login Using Miniorange Account</p>
							<div class="mo-input-wrapper group">
								<label class="mo-input-label">Email</label>
								<input class="w-full mo2f-input" type="email" name="miniorange_email" id="miniorange_email" required placeholder="person@example.com"/>
							</div>
							<div class="w-full mo-input-wrapper group">
								<label class="mo-input-label">Password</label>
								<input class="w-full mo2f-input" required type="password" name="miniorange_password" placeholder="Enter your miniOrange password" />
							</div>
							<div>
								<a href="' . esc_url( 'https://portal.miniorange.com/forgotpassword' ) . '" target="_blank" class="mo2f-text-right mo2f-font-bold hover:underline mo2f-float-right">Forgot Password?</a>
							</div>
							<div class="mo2f_login_register_button">
								<br>
								<input type="button" id="mo2f_login" class="mo2f-save-settings-button" value="' . esc_attr__( 'Sign In', 'miniorange-2-factor-authentication' ) . '" />
								&nbsp;&nbsp;&nbsp;&nbsp;
								<input type="button" id="mo2f_cancel_link" class="mo2f-reset-settings-button" value="' . esc_attr__( 'Go Back To Registration', 'miniorange-2-factor-authentication' ) . '" />
							</div>
						</div>
					  </form>';
			$html .= '<br>';
			$html .= $skeleton['##miniorangelogo##'];
			$html .= '</div></div>';
			$html .= '<div id="mo2f_2fa_popup_dashboard_loader" class="modal hidden"></div>';
			$html .= '<form name="mo2f_goto_two_factor_form" method="post" id="mo2f_goto_two_factor_form">
						<input type="hidden" name="option" value="miniorange_back_inline"/>
						<input type="hidden" name="miniorange_inline_two_factor_setup" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ) . '"/>
					  </form>';
			$html .= '<script>';
			$html .= 'myaccount' === $prev_screen ? 'jQuery("#mo_2fa_my_account").addClass("side-nav-active"); jQuery("#mo2f-myaccount-details").addClass("side-nav-active");jQuery("#mo2f-myaccount-submenu").css("display", "block");' : '';
			$html .= 'jQuery("#mo2f_inline_back_btn").click(function() {  
						jQuery("#mo2f_goto_two_factor_form").submit();
					});
					jQuery("a[href=\'#mo2f_account_exist\']").click(function (e) {
						e.preventDefault();
						jQuery("#mo2f_inline_register_form").hide();
						jQuery("#mo2f-otpMessage").hide();
						jQuery("#mo2f_inline_login_form").show();
						var input = jQuery("input[name=miniorange_email]");
						var len = input.val().length;
						input[0].focus();
						
					});
					jQuery("#mo2f_cancel_link").click(function(){                               
							jQuery("#mo2f_inline_register_form").show();
							jQuery("#mo2f_inline_login_form").hide();
					});     
					function mologinback(){
						jQuery("#mo2f_backto_mo_loginform").submit();
						jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
						closeVerification = true;
					}
					var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";	
					jQuery("#mo2f_login").click(function() {
						var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
						' . $common_helper->mo2f_show_loader() . '
						var data = {
							action: "mo_two_factor_ajax",
							mo_2f_two_factor_ajax: "mo2f_miniorange_sign_in",
							email: jQuery("input[name=\'miniorange_email\']").val(),
							password: jQuery("input[name=\'miniorange_password\']").val(),
							nonce: nonce,
						};
						jQuery.post(ajaxurl, data, function(response) {
							' . $common_helper->mo2f_hide_loader() . '
							if (response.success) {
								' . call_user_func( $success_response ) . '
							} else {
							' . call_user_func( $error_response ) . '
							}
						});
					});';
			$html .= 'jQuery("#mo2f_register").click(function() {
						var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
						' . $common_helper->mo2f_show_loader() . '
						var data = {
							action: "mo_two_factor_ajax",
							mo_2f_two_factor_ajax: "mo2f_miniorange_sign_up",
							email: jQuery("input[name=\'email\']").val(),
							password: jQuery("input[name=\'password\']").val(),
							confirmPassword: jQuery("input[name=\'confirmPassword\']").val(),
							nonce: nonce,
						};
						jQuery.post(ajaxurl, data, function(response) {
							' . $common_helper->mo2f_hide_loader() . '
							if (response.success) {
								' . call_user_func( $success_response ) . '
							} else {
							' . call_user_func( $error_response ) . '
							}
						});
					});';
			$html .= "
					function mo2f_show_message(response) {
						var html = '<div id=\"mo2f-otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
						jQuery('#mo2f-otpMessage').remove();
						jQuery('#mo2f-otpMessagehide').after(html);
					}
					const checkbox = document.getElementById('mo2f_agree_plugin_policy');
					const button = document.getElementById('mo2f_register');
					if(checkbox && button){
						checkbox.addEventListener('change', function() {
							button.disabled = !this.checked;
						});
					}";
			$html .= '</script>';
			return $html;
		}

		/**
		 * Gets script response for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_mo_login_registration_success_response_dashboard_script() {
			$script = 'prompt_2fa_popup_dashboard( "OTPOverSMS", "setup" );';
			return $script;
		}
		/**
		 * Gets script response for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_mo_login_registration_error_response_dashboard_script() {
			$script = 'mo2f_show_message(response.data);';
			return $script;
		}

		/**
		 * Gets script response for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_mo_login_registration_success_response_myaccount_script() {
			$script = 'jQuery("#mo2f_login_registration_div").hide();
			jQuery("#mo2f_account_details").show()';
			$script = 'window.location.href ="' . esc_url( admin_url() ) . '" + \'admin.php?page=mo_2fa_my_account\';';
			return $script;
		}

		/**
		 * Gets script response for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_mo_login_registration_error_response_myaccount_script() {
			$script = 'error_msg(response.data);';
			return $script;
		}

		/**
		 * This function shows KBA setup screen.
		 *
		 * @param int    $user_id User id.
		 * @param string $login_message Message used to show success/failed login actions.
		 * @param string $redirect_to Redirect URL.
		 * @param string $session_id Session ID.
		 * @param string $prev_screen Previous screen.
		 * @return string
		 */
		public function prompt_user_for_kba_setup( $user_id, $login_message, $redirect_to, $session_id, $prev_screen ) {
			$html      = '<div id="mo2f_2fa_popup_dashboard_loader" class="modal" hidden></div>';
			$html     .= '<div class="mo2f_kba_setup_popup_dashboard">';
			$html     .= '<div class="login mo_customer_validation-modal-content">';
			$html     .= '<div class="mo2f_modal-header">
			<h4 class="mo2f_modal-title">';
			$html     .=
				'<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '" onclick="mologinback();">
					<span aria-hidden="true">&times;</span>
				</button>';
				$html .= esc_html__( 'Configure ' . MoWpnsConstants::mo2f_convert_method_name( MoWpnsConstants::SECURITY_QUESTIONS, 'cap_to_small' ), 'miniorange-2-factor-authentication' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
				$html .= '</h4>
				</div>';
				$html .= '<div class="mo2f_modal-body">';
				$html .= '	<div id="mo2f-otpMessagehide" class="hidden">
				<p class="mo2fa_display_message_frontend" style="text-align: left !important; ">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
			</div>';

			$html         .= '<form name="mo2f_configure_kba_method_form" method="post" action="" class="mo2f_kba_configure_form">
                ' . $this->mo2f_configure_kba_questions() . '
                <br/>
                <div class="row">
                    <div style="margin: 0 auto; width: 100px;">
                        <input type="button" name="validate" id="mo2f_save_kba" class="mo2f-save-settings-button" value="' . esc_attr__( 'Save', 'miniorange-2-factor-authentication' ) . '" />
                    </div>
                </div>
                <input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
                <input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
            </form>';
			$common_helper = new Mo2f_Common_Helper();
			if ( 'mo2f_inline_form' === $prev_screen && ! TwoFAMoSessions::get_session_var( 'mo2f_is_kba_backup_configured' . $user_id ) ) {
				$prev_screen = 'mo2f_inline_form';
				$html       .= $common_helper->mo2f_go_back_link_form( $prev_screen );
			}
			$html .= $common_helper->mo2f_customize_logo();
			$html .= '</div></div></div>';
			$html .= '<script>';
			$html .= "
		function mo2f_show_message(response) {
			var html = '<div id=\"mo2f-otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
			jQuery('#mo2f-otpMessage').remove();
			jQuery('#mo2f-otpMessagehide').after(html);
		}";
			$html .= 'function mologinback() {
				jQuery("#mo2f_backto_mo_loginform").submit();
				jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
				closeVerification = true;
			}</script>';

			return $html;
		}

		/**
		 * Function to show setup wizard for configuring KBA.
		 *
		 * @return string
		 */
		public function mo2f_configure_kba_questions() {
			$default_question_count = get_site_option( 'mo2f_default_kbaquestions_users', 2 );
			$custom_question_count  = get_site_option( 'mo2f_custom_kbaquestions_users', 1 );
			$total_questions        = $default_question_count + $custom_question_count;
			$html                   = '<div class="mo2f_kba_header mo2f_kba_header_background">' . esc_html__( 'Please choose ', 'miniorange-2-factor-authentication' ) . $total_questions . esc_html__( ' questions', 'miniorange-2-factor-authentication' ) . '</div>';
			$html                  .= '<br>';
			$html                  .= '<table id="mo2f_configure_kba" cellspacing="10">';
			$html                  .= '<thead>';
			$html                  .= '<tr class="mo2f_kba_header">';
			$html                  .= '<th>' . esc_html__( 'Sr. No.', 'miniorange-2-factor-authentication' ) . '</th>';
			$html                  .= '<th class="mo2f_kba_tb_data">' . esc_html__( 'Questions', 'miniorange-2-factor-authentication' ) . '</th>';
			$html                  .= '<th>' . esc_html__( 'Answers', 'miniorange-2-factor-authentication' ) . '</th>';
			$html                  .= '</tr>';
			$html                  .= '</thead>';

			for ( $i = 1; $i <= $total_questions; $i++ ) {
				$html .= '<tr class="mo2f_kba_body">';
				$html .= '<td class="mo2f_align_center">' . $i . '.</td>';

				if ( $i <= $default_question_count ) {
					$html .= '<td class="mo2f_kba_tb_data">' . $this->mo2f_kba_question_set( $i ) . '</td>';
				} else {
					$html .= '<td class="mo2f_kba_tb_data">';
					$html .= '<input class="mo2f_kba_ques" type="text" style="width: 100%;" name="mo2f_kbaquestion_' . esc_attr( $i ) . '" id="mo2f_kbaquestion_' . esc_attr( $i ) . '" required="true" placeholder="' . esc_attr__( 'Enter your custom question here', 'miniorange-2-factor-authentication' ) . '"/>';
					$html .= '</td>';
				}

				$input_id = 'mo2f_kba_ans' . $i;
				$html    .= '<td>';
				$html    .= '<input class="mo2f_table_textbox" type="password" name="' . esc_attr( $input_id ) . '" id="' . esc_attr( $input_id ) . '"';
				$html    .= ' title="' . esc_attr__( 'Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed.', 'miniorange-2-factor-authentication' ) . '"';
				$html    .= ' pattern="(?=\\S)[A-Za-z0-9_@.$#&amp;+\-\s]{1,100}" required="true" placeholder="' . esc_attr__( 'Enter your answer', 'miniorange-2-factor-authentication' ) . '"/>';
				$html    .= '</td>';
				$html    .= '</tr>';
			}

			$html .= '</table>';

			$html .= '<script>';
			$html .= 'function mo_option_hide(question_no) {';
			$html .= 'var dropdowns = document.querySelectorAll(".mo2f_kba_ques");';
			$html .= 'if (!dropdowns.length) return;';
			$html .= 'var selectedOptions = [];';
			$html .= 'dropdowns.forEach(function(dropdown) {';
			$html .= 'if (dropdown && dropdown.options && dropdown.selectedIndex !== -1) {';
			$html .= 'var selectedValue = dropdown.options[dropdown.selectedIndex].value;';
			$html .= 'if (selectedValue) selectedOptions.push(selectedValue);';
			$html .= '}';
			$html .= '});';
			$html .= 'dropdowns.forEach(function(dropdown) {';
			$html .= 'if (dropdown && dropdown.options) {';
			$html .= 'for (var i = 0; i < dropdown.options.length; i++) {';
			$html .= 'dropdown.options[i].style.display = "block";';
			$html .= '}';
			$html .= '}';
			$html .= '});';
			$html .= 'dropdowns.forEach(function(dropdown) {';
			$html .= 'if (dropdown && dropdown.options) {';
			$html .= 'for (var i = 0; i < dropdown.options.length; i++) {';
			$html .= 'if (selectedOptions.includes(dropdown.options[i].value)) {';
			$html .= 'dropdown.options[i].style.display = "none";';
			$html .= '}';
			$html .= '}';
			$html .= '}';
			$html .= '});';
			$html .= '}';
			$html .= '</script>';

			return $html;
		}

		/**
		 * Show KBA question set.
		 *
		 * @param integer $question_no Question number.
		 * @return string
		 */
		public function mo2f_kba_question_set( $question_no ) {
			$question_set = isset( $GLOBALS['mo2f_default_kba_question_set'] ) ? $GLOBALS['mo2f_default_kba_question_set'] : array();
			$question_set = apply_filters( 'mo2f_enterprise_plan_settings_filter', $question_set, 'mo2f_check_for_custom_security_questions', $question_set );
			$html         = '<select name="mo2f_kbaquestion_' . esc_attr( $question_no ) . '" id="mo2f_kbaquestion_' . esc_attr( $question_no ) . '" class="mo2f_kba_ques" required onchange="mo_option_hide(' . esc_attr( $question_no ) . ')">';
			$html        .= '<option value="" selected>' . esc_html__( 'Select your question', 'miniorange-2-factor-authentication' ) . '</option>';

			foreach ( $question_set as $index => $question ) {
				$option_id = 'mq' . ( $index + 1 ) . '_' . esc_attr( $question_no );
				$html     .= '<option id="' . esc_attr( $option_id ) . '" value="' . esc_attr( $question ) . '">';
				$html     .= esc_html( $question );
				$html     .= '</option>';
			}

			$html .= '</select>';
			return $html;
		}

		/**
		 * Gets the miniOrange customer.
		 *
		 * @param string $email Email of the user.
		 * @param string $password Password of the user.
		 * @return void
		 */
		public function mo2f_get_miniorange_customer( $email, $password ) {
			$customer     = new MocURL();
			$content      = $customer->get_customer_key( $email, $password );
			$customer_key = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( 'SUCCESS' === $customer_key['status'] ) {
					if ( isset( $customer_key['phone'] ) ) {
						update_site_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
					}
					update_site_option( 'mo2f_email', $email );
					$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					$this->mo2f_save_customer_configurations( $id, $api_key, $token, $app_secret );
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to obfuscate the option as it will be stored in database.
					$mocurl = new MocURL();
					$plans  = array( 'otp_recharge_plan', 'wp_otp_verification' );
					foreach ( $plans as $plan ) {
						$content = json_decode( $mocurl->get_customer_transactions( $plan, 'PREMIUM' ), true );
						if ( isset( $content['status'] ) && 'SUCCESS' === $content['status'] ) {
							break;
						}
					}
					if ( ! isset( $content['status'] ) || 'SUCCESS' !== $content['status'] ) {
						$content = json_decode( $mocurl->get_customer_transactions( '-1', 'DEMO' ), true );
					}
					update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', isset( $content['smsRemaining'] ) ? $content['smsRemaining'] : 0 );
					update_site_option( 'cmVtYWluaW5nT1RQ', get_site_option( 'cmVtYWluaW5nT1RQ', 30 ) );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::REG_SUCCESS ) );
				} else {
					wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::ACCOUNT_EXISTS ) );
				}
			} else {
				$mo2f_message = is_string( $content ) ? $content : '';
				wp_send_json_error( MoWpnsMessages::lang_translate( $mo2f_message ) );
			}
		}

		/**
		 * It is to save the inline settings
		 *
		 * @param string $id It will carry the id .
		 * @param string $api_key It will carry the api key .
		 * @param string $token It will carry the token value .
		 * @param string $app_secret It will carry the secret data .
		 * @return void
		 */
		public function mo2f_save_customer_configurations( $id, $api_key, $token, $app_secret ) {
			update_site_option( 'mo2f_customerKey', $id );
			update_site_option( 'mo2f_api_key', $api_key );
			update_site_option( 'mo2f_customer_token', $token );
			update_site_option( 'mo2f_app_secret', $app_secret );
			update_site_option( 'mo2f_miniorange_admin', $id );
			update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
		}

		/**
		 * Gets the script for testing.
		 *
		 * @return string
		 */
		public function mo2f_get_test_script() {
			$common_helper = new Mo2f_Common_Helper();
			$script        = '<script>			jQuery("#mo2f_validate").click(function() {
			    ' . $common_helper->mo2f_show_loader() . '
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_validate_user_for_login",
					mo2f_login_method: jQuery("input[name=\'mo2f_login_method\']").val(),
					redirect_to: jQuery("input[name=\'redirect_to\']").val(),
					session_id: jQuery("input[name=\'session_id\']").val(),
					mo2fa_softtoken: jQuery("input[name=\'mo2fa_softtoken\']").val(),
					mo2f_answer_1: jQuery("input[name=\'mo2f_answer_1\']").val(),
					mo2f_answer_2: jQuery("input[name=\'mo2f_answer_2\']").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    ' . $common_helper->mo2f_hide_loader() . '
					if (response.success) {
						jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
						closeVerification = true;
						success_msg("You have successfully validated your 2FA method.");
					} else if ( response.data == \'INVALID_OTP\'){
						jQuery("#mo2fa_softtoken").val("");
						mo2f_show_message("Invalid OTP. Please enter the correct OTP.");
					} else if ( response.data == \'INVALID_ANSWERS\'){
						jQuery("#mo2f_answer_1").val("");
						jQuery("#mo2f_answer_2").val("");
						jQuery("input[name=mo2f_answer_1]").focus();
						jQuery("#mo2f_answer_1, #mo2f_answer_2").addClass("mo2f_kba_error");
						mo2f_show_message("Invalid answers. Please enter the correct answers.");
					}
				});
			});
		</script>';
			return $script;
		}

		/**
		 * Get the loginn script.
		 *
		 * @param string $twofa_method Twofa method.
		 * @return string
		 */
		public function mo2f_get_login_script( $twofa_method ) {
			$common_helper = new Mo2f_Common_Helper();
			$script        = '<script>		
			var twofa_method = "' . esc_js( $twofa_method ) . '";
			var attemptleft = 3;	
			jQuery("#mo2f_validate").click(function() {
				' . $common_helper->mo2f_show_loader() . '
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";

				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_validate_user_for_login",
					mo2f_login_method: jQuery("input[name=\'mo2f_login_method\']").val(),
					redirect_to: jQuery("input[name=\'redirect_to\']").val(),
					session_id: jQuery("input[name=\'session_id\']").val(),
					mo2fa_softtoken: jQuery("input[name=\'mo2fa_softtoken\']").val(),
					mo2f_answer_1: jQuery("input[name=\'mo2f_answer_1\']").val(),
					mo2f_answer_2: jQuery("input[name=\'mo2f_answer_2\']").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    ' . $common_helper->mo2f_hide_loader() . '
					if (response.success) {
						jQuery("#mo2f_inline_otp_validated_form").submit();
					} else if(response.data == "LIMIT_EXCEEDED"){
						mologinback();
					} else if( response.data == \'INVALID_ANSWERS\') {
						jQuery("#mo2f_answer_1").val("");
						jQuery("#mo2f_answer_2").val("");
						jQuery("input[name=mo2f_answer_1]").focus();
						jQuery("#mo2f_answer_1, #mo2f_answer_2").addClass("mo2f_kba_error");
						mo2f_show_message("Invalid answers. Please enter the correct answers.");	
					} else{
						jQuery("#mo2fa_softtoken").val("");
						attemptleft = attemptleft - 1;
						var span =   document.getElementById("mo2f_attempt_span");
						span.textContent = attemptleft;
						if(response.data == "ALREADY_USED"){
							mo2f_show_message("' . __( 'The OTP has already been used. Please enter new OTP.', 'miniorange-2-factor-authentication' ) . '");
						} else{
							mo2f_show_message("' . __( 'Invalid OTP. Please enter the correct OTP.', 'miniorange-2-factor-authentication' ) . '");
						}
					}

				});
			});
		</script>';
			return $script;
		}
		/**
		 * This function prints customized logo.
		 *
		 * @return string
		 */
		public function mo2f_customize_logo() {
			$custom_logo_enabled = get_site_option( 'mo2f_custom_logo', 'miniOrange2.png' );
			$html                = '<div style="float:right;"><img
							alt="logo"
							src="' . esc_url( plugins_url( 'includes/images/' . $custom_logo_enabled, dirname( __FILE__ ) ) ) . '"/></div>';
			return $html;
		}

		/**
		 * This function used to include css and js files.
		 *
		 * @return void
		 */
		public function mo2f_echo_js_css_files() {

			if ( is_user_logged_in() && ! get_site_transient( 'mo2f_page_protection_flow_1' . wp_get_current_user()->ID ) ) {
				wp_register_style( 'mo2f_style_settings', plugins_url( 'includes/css/twofa_style_settings.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
				wp_print_styles( 'mo2f_style_settings' );
			} else {
				wp_register_style( 'mo2f_bootstrap_settings', plugins_url( 'includes/css/bootstrap.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
				wp_print_styles( 'mo2f_bootstrap_settings' );
			}
			wp_register_style( 'mo2f_main_css', plugins_url( 'includes/css/mo2f-main.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_print_styles( 'mo2f_main_css' );
			wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'mo2f_bootstrap_js' );
			if ( get_site_option( 'mo2f_enable_login_popup_customization' ) ) {
				$this->mo2f_output_custom_login_popup_css();
			}
		}

		/**
		 * Outputs the custom CSS for the login popup.
		 *
		 * @return void
		 */
		public function mo2f_output_custom_login_popup_css() {
			$custom_css        = '';
			$current_popup_css = array(
				'mo2f_custom_background_color'  => '.mo2f-modal-backdrop{background-color: ##custom_css## !important;}',
				'mo2f_background_image'         => '.mo2f-modal-backdrop{ background-image: url("##custom_css##") !important; background-repeat: no-repeat, repeat; background-size:cover;background-attachment: fixed;background-position: center;  }',
				'mo2f_custom_popup_bg_color'    => '.mo_customer_validation-modal-content, .mo2f_gauth_getapp, .mo2f-backup-codes-outer-container, .mo2f_dashboard_test_popup_background{background:##custom_css## !important;}',
				'mo2f_custom_otp_bg_color'      => '.mo2f-otp-catchy{background-color:##custom_css## !important;}',
				'mo2f_custom_otp_text_color'    => '.mo2f-otp-catchy{color:##custom_css## !important;}',
				'mo2f_custom_middle_text_color' => '.mo2f_middle_text, .mo2f_dashboard_test_popup_text{color:##custom_css##!important;}',
				'mo2f_custom_header_text_color' => '.mo2f_modal-title{color:##custom_css##!important;}',
				'mo2f_custom_footer_text_color' => '.mo2f_footer_text{color:##custom_css##!important;}',
				'mo2f_custom_links_text_color'  => '.pushHelpLink{color:##custom_css##!important;}',
				'mo2f_custom_notif_text_color'  => '.mo2fa_display_message_frontend{color:##custom_css##!important;}',
				'mo2f_custom_notif_bg_color'    => '#mo2f-otpMessage{background:##custom_css##!important;}',
				'mo2f_custom_button_color'      => '.mo2f-save-settings-button, .miniorange_otp_token_submit, .miniorange_kba_validate, .miniorange_button {background:##custom_css##!important;}.miniorange_otp_token_submit:hover, .miniorange_kba_validate:hover {background:##custom_css##d6 !important;}.miniorange_otp_token_submit{border-color:##custom_css##!important;}',

			);
			$updated_popup_css = get_site_option( 'mo2f_custom_2fa_popup_css', array() );
			foreach ( $current_popup_css as $element => $value ) {
				if ( isset( $updated_popup_css[ $element ] ) ) {
					$css_value   = $updated_popup_css[ $element ];
					$custom_css .= str_replace( '##custom_css##', $css_value, $value );
				}
			}
			if ( ! empty( $custom_css ) ) {
				echo wp_kses( "<style>$custom_css</style>", array( 'style' => array() ) );
			}
		}

		/**
		 * Gets html for Google authentication
		 *
		 * @param string $gauth_name Gauth name.
		 * @param string $data Qr code data.
		 * @param string $microsoft_url Microsoft qr code url.
		 * @param string $secret Secrets.
		 * @param string $prev_screen Previous screen.
		 * @param string $redirect_to Redirection url.
		 * @param string $session_id Session id.
		 * @return string
		 */
		public function mo2f_google_authenticator_popup_common_html( $gauth_name, $data, $microsoft_url, $secret, $prev_screen, $redirect_to, $session_id ) {
			$common_helper = new Mo2f_Common_Helper();
			require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-google-auth-app-links.php';

			$html = '<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
			<div' . ( 'dashboard' !== $prev_screen ? ' class="mo2f-modal-backdrop"' : '' ) . '></div>
			<div class="mo2f_modal-dialog mo2f_modal-lg">
				<div id="mo2f_2fa_popup_dashboard_loader" class="modal" hidden></div>
				<div class="login mo_customer_validation-modal-content mo2f_authenticator_popup_size">';

			$html .= '<h4>';
			$html .= '<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
				title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '"
				onclick="mologinback();"><span aria-hidden="true">&times;</span></button>';
			$html .= esc_html__( 'Configure Google/Authy/Microsoft Authenticator', 'miniorange-2-factor-authentication' ) . '</h4>';

			$html .= '<hr><table class="mo2f_configure_ga"><tr><td class="mo2f_google_authy_step2">';
			$html .= '<div id="mo2f-otpMessage" class="hidden"><p id="mo2f_gauth_inline_message" class="mo2fa_display_message_frontend mo_feedback_text"></p></div>';

			$html .= '<div id="mo2f_choose_app_tour" class="mo2f-choose-app-container">
				<label for="authenticator_type"><b>' . esc_html__( '1. Choose an Authenticator App:', 'miniorange-2-factor-authentication' ) . '</b></label>
				<select id="authenticator_type" class="mo2f-select-authenticator-app">';
			foreach ( $auth_app_links as $auth_app => $auth_app_link ) {
				$html .= '<option data-apptype="' . esc_attr( $auth_app ) . '" data-playstorelink="' . esc_attr( $auth_app_link['Android'] ) . '" data-appstorelink="' . esc_attr( $auth_app_link['Ios'] ) . '">' . esc_html( MoWpnsConstants::mo2f_convert_method_name( $auth_app_link['app_name'], 'cap_to_small' ) ) . '</option>';
			}
			$html .= '</select></div>';

			$html .= '<div class="mo2f-auth-icons">';
			$auth_images = array(
				'google authenticator.png',
				'microsoft authenticator.png',
				'authy authenticator.png',
				'duo authenticator.png',
				'lastpass authenticator.png',
				'freeotp authenticator.png',
			);
			foreach ( $auth_images as $img ) {
				$html .= '<img src="' . esc_url( plugins_url( 'includes/images/' . $img, __DIR__ ) ) . '" width="34" height="34" class="mo2f-auth-icon-img" />';
			}
			$html .= '</div>';

			$html .= '<h4 class="mo2f-section-heading">' . esc_html__( '2. Scan the QR code from the Authenticator App.', 'miniorange-2-factor-authentication' ) . '</h4>';

			$html .= '<div class="mo2f-qr-section"><ol>
				<div class="mo2f_gauth" id="mo2f_google_auth_qr_code" data-qrcode="' . $data . '"></div>
				<div class="mo2f_gauth_microsoft" id="mo2f_microsoft_auth_qr_code" data-qrcode="' . esc_html( $microsoft_url ) . '"></div>
			</ol></div>';

			$html .= '<div class="mo2f-gauth-otp-container">
				<form name="mo2f_validate_code_form" id="mo2f_validate_code_form" method="post" class="mo2f-gauth-otp-form">
					<span><b>' . esc_html__( '3. Enter the code from authenticator app:', 'miniorange-2-factor-authentication' ) . '</b></span><br>
					<input class="mo2f_table_textbox mo2f-gauth-otp-input" id="google_auth_code" autofocus="true" required="true" type="text" name="google_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '" />
					<input type="hidden" name="option" value="mo2f_inline_validation_success">
					<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect_to ) . '"/>
					<input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
					<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '" />
				</form>
				<div class="mo2f-gauth-otp-button-wrapper">
					<button name="mo2f_validate_gauth" id="mo2f_save_otp_ga_tour" class="mo2f-save-settings-button mo2f-gauth-otp-button">' . esc_html__( 'Verify', 'miniorange-2-factor-authentication' ) . '</button>
				</div>
			</div>';

			if ( 'dashboard' !== $prev_screen ) {
				$html .= '<br>' . $common_helper->mo2f_go_back_link_form( $prev_screen );
			}

			$html .= '<br></td><td class="mo2f_vertical_line"></td><td class="mo2f_google_authy_step3">';

			$html .= '<div><a href="#mo2f_scanbarcode_a" class="mo2f-gauth-link">' . esc_html__( 'Can\'t scan the QR code? ', 'miniorange-2-factor-authentication' ) . '' . MoWpnsConstants::MO2F_SVG_ARROW_ICON_DROPDOWN . '</a></div>';

			$html .= '<br><div id="mo2f_secret_key" class="mo2f-secret-key-box">
				<p class="mo2f-secret-key-text">' . esc_html__( 'Use the secret key below to set up your account in the authenticator app.', 'miniorange-2-factor-authentication' ) . '</p>
				<div class="mo2f_google_authy_secret_outer_div mo2f-secret-outer">
					<div class="mo2f_google_authy_secret_inner_div mo2f-secret-inner">' . esc_html( $secret ) . '</div>
				</div>
			</div>';

			if ( 'dashboard' === $prev_screen ) {
				$html .= '<div><a href="https://faq.miniorange.com/knowledgebase/sync-mobile-app/" target="_blank" class="mo2f-gauth-link">' . esc_html__( 'Sync your server time with authenticator app time ', 'miniorange-2-factor-authentication' ) . '' . MoWpnsConstants::MO2F_SVG_ARROW_ICON . '</a>
					<div class="mo2f-server-time-wrapper">
						<p class="mo2f-server-time-label">' . esc_html__( 'Current Server Time', 'miniorange-2-factor-authentication' ) . '</p>
						<div id="mo2f_server_time" class="mo2f-server-time-box">--</div>
					</div>
				</div>';
			}

			$html .= '<div id="links_to_apps_tour" class="mo2f-app-links-container"><span id="links_to_apps"></span></div>';
			$html .= '<div class="mo2f_customize_logo">' . $common_helper->mo2f_customize_logo() . '</div>';

			$html .= '</td></tr></table></div></div></div><br>';

			$server_time = isset( $_SERVER['REQUEST_TIME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_TIME'] ) ) * 1000 : null;

			$html .= '<script>
				jQuery("a[href=\"#mo2f_scanbarcode_a\"]").click(function(e){
					jQuery("#mo2f_secret_key").slideToggle();
				});
				jQuery(document).ready(function () {
					var serverTime = new Date(Number(' . esc_js( $server_time ) . '));
					var server_time = serverTime.toLocaleTimeString();
					var nonce = "' . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . '";
					var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
					var ms_url = "' . esc_js( $microsoft_url ) . '";
					var gu_url = "' . esc_js( $data ) . '";
					var ga_secret = "' . esc_js( $secret ) . '";
					var session_id = "' . esc_js( $session_id ) . '";
					var redirect_to = "' . esc_js( $redirect_to ) . '";
					var twofaFlow = "' . esc_js( $prev_screen ) . '";
					var mo2fSvgArrowIcon = "<svg class=\'mo2f-gauth-link-icon\' xmlns=\'http://www.w3.org/2000/svg\' width=\'1em\' height=\'1em\' viewBox=\'0 0 512 512\' fill=\'currentColor\' aria-hidden=\'true\' focusable=\'false\'>" +
					"<path d=\'M432 320h-32a16 16 0 0 0-16 16v112H80V128h112a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16H64A64 64 0 0 0 0 128v320a64 64 0 0 0 64 64h320a64 64 0 0 0 64-64V336a16 16 0 0 0-16-16zm56-320H336a24 24 0 0 0-17 41l35 35L176 309a24 24 0 0 0 0 34l22 22a24 24 0 0 0 34 0l178-178 35 35a24 24 0 0 0 41-17V32a32 32 0 0 0-32-32z\'/>" +
					"</svg>";
		
					if ( twofaFlow == "dashboard" ) {
						document.getElementById("mo2f_server_time").innerHTML = server_time;
					}
		
					jQuery("#google_auth_code").keypress(function(event) {
						if (event.which === 13) {
							event.preventDefault();
							mo2f_validate_gauth(nonce, ga_secret);
						}
					});
		
					jQuery("#mo2f_save_otp_ga_tour").click(function() {
						mo2f_validate_gauth(nonce, ga_secret);
					});
		
					jQuery("#authenticator_type").change(function () {
						var selectedAuthenticator = jQuery(this).children("option:selected").data("apptype");
						var playStoreLink = jQuery(this).children("option:selected").data("playstorelink");
						var appStoreLink = jQuery(this).children("option:selected").data("appstorelink");
		
						jQuery("#links_to_apps").html("<p class=\'mo2f-app-links-message\'>" +
						"Get the Authenticator App - <br><a href=" + playStoreLink + " target=\'_blank\' class=\'mo2f-gauth-link\'>Android Play Store " + mo2fSvgArrowIcon + "</a> &emsp;" +
						"<a href=" + appStoreLink + " target=\'_blank\' class=\'mo2f-gauth-link\'>iOS App Store " + mo2fSvgArrowIcon + "</a></p>");
						jQuery("#links_to_apps").show();
		
						var data = {
							"action": "mo_two_factor_ajax",
							"mo_2f_two_factor_ajax": "mo2f_google_auth_set_transient",
							"auth_name": selectedAuthenticator,
							"micro_soft_url": ms_url,
							"g_auth_url": gu_url,
							"session_id": session_id,
							"nonce": nonce
						};
		
						jQuery.post(ajaxurl, data, function(response) {
							if (!response["success"] && twofaFlow == "dashboard") {
								error_msg("Unknown error occurred. Please try again!");
							}
						});
		
						if (selectedAuthenticator == "msft_authenticator") {
							jQuery("#mo2f_microsoft_auth_qr_code").css("display", "block");
							jQuery("#mo2f_google_auth_qr_code").css("display", "none");
						} else {
							jQuery("#mo2f_microsoft_auth_qr_code").css("display", "none");
							jQuery("#mo2f_google_auth_qr_code").css("display", "block");
						}
						mo2f_show_auth_methods(selectedAuthenticator);
					});
		
					jQuery(".mo2f_gauth").qrcode({
						"render": "image",
						"size": 120,
						"text": jQuery(".mo2f_gauth").data("qrcode")
					});
					jQuery(".mo2f_gauth_microsoft").qrcode({
						"render": "image",
						"size": 120,
						"text": jQuery(".mo2f_gauth_microsoft").data("qrcode")
					});
		
					jQuery(this).scrollTop(0);
					jQuery("#links_to_apps").html("<p class=\'mo2f-app-links-message\'>" +
					"Get the Authenticator App - <br><a href=\'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2\' target=\'_blank\' class=\'mo2f-gauth-link\'>Android Play Store " + mo2fSvgArrowIcon + "</a> &emsp;" +
					"<a href=\'http://itunes.apple.com/us/app/google-authenticator/id388497605\' target=\'_blank\' class=\'mo2f-gauth-link\'>iOS App Store " + mo2fSvgArrowIcon + "</a></p>");
					jQuery("#mo2f_change_app_name").show();
					jQuery("#links_to_apps").show();
					jQuery("#mo2f_next_step3").css("display", "none");
				});
		
				function mo2f_show_auth_methods(selected_method) {
					var auth_methods = ["google_authenticator", "msft_authenticator", "authy_authenticator", "last_pass_auth", "free_otp_auth", "duo_auth"];
					auth_methods.forEach(function(method) {
						if (method == selected_method) {
							jQuery("#mo2f_" + method + "_instructions").css("display", "block");
						} else {
							jQuery("#mo2f_" + method + "_instructions").css("display", "none");
						}
					});
				}
		
				function mologinback() {
					jQuery("#mo2f_backto_mo_loginform").submit();
					jQuery("#mo2f_2fa_popup_dashboard").fadeOut();
				}
			</script>';

			return $html;
		}
		/**
		 * The method is used to display notification in the plugin .
		 *
		 * @param object $user used to get customer email and id.
		 * @return void
		 */
		public function mo2f_display_test_2fa_notification( $user = null ) {
			global $mo2fdb_queries;
			$user = wp_get_current_user();
			if ( get_site_transient( 'mo2f_show_setup_success_prompt' . $user->ID ) ) {
				$mo2f_configured_2_f_a_method = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
				wp_print_scripts( 'jquery' );
				echo '<div id="twoFAtestAlertModal" class="modal" role="dialog">
		<div class="mo2f_modal-dialog">
			<div class="modal-content mo2f_dashboard_test_popup_background">
			<div class="mo2fa_text-align-center">
				<div class="modal-header">
					<h2 class="mo2f_modal-title" style="color: #2271b1;">2FA Setup Successful</h2>
					<span type="button" id="test-methods" class="modal-span-close" data-dismiss="modal">&times;</span>
				</div>
				<div class="mo2f_modal-body mo2f_dashboard_test_popup_text">
					<p style="font-size:14px;"><b>' . esc_attr( MoWpnsConstants::mo2f_convert_method_name( $mo2f_configured_2_f_a_method, 'cap_to_small' ) ) . '</b> has been set as your 2-factor authentication method.
					<br>
					<br>Please test the login flow once with 2nd factor in another browser or in an incognito window of the same browser to ensure you don\'t get locked out of your site.</p>
				</div>
				<div class="mo2f_modal-footer">
					<button type="button" id="test-methods-button" class="mo2f-save-settings-button" data-dismiss="modal">Test</button>
				</div>
					</div>
			</div>
		</div>
	</div>';

				echo '<script>
		jQuery("#twoFAtestAlertModal").css("display", "block");
		jQuery("#test-methods").click(function(){
			jQuery("#twoFAtestAlertModal").css("display", "none");
		});
		jQuery("#test-methods-button").click(function(){
			jQuery("#twoFAtestAlertModal").css("display", "none");
			var twofa_method = "' . esc_js( $mo2f_configured_2_f_a_method ) . '";
			twofa_method = twofa_method.replace(/\s/g, "");
			testAuthenticationMethod(twofa_method);
		});
	</script>';
			}
			delete_site_transient( 'mo2f_show_setup_success_prompt' . $user->ID );
		}

		/**
		 * This function includes css,js scripts.
		 *
		 * @return void
		 */
		public function mo2f_inline_css_and_js() {

			wp_register_style( 'mo2f_bootstrap', plugins_url( 'includes/css/bootstrap.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_register_style( 'mo2f_front_end_login', plugins_url( 'includes/css/front_end_login.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_register_style( 'mo2f_style_setting', plugins_url( 'includes/css/style_settings.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/css/hide-login.min.css' ) ) {
				wp_register_style( 'mo2f_hide-login', plugins_url( 'includes/css/hide-login.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			}
			wp_print_styles( 'mo2f_bootstrap' );
			wp_print_styles( 'mo2f_front_end_login' );
			wp_print_styles( 'mo2f_style_setting' );
			wp_print_styles( 'mo2f_hide-login' );
			wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'mo2f_bootstrap_js' );
			wp_register_script( 'mo2f_phone_js', plugins_url( 'includes/js/phone.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_print_scripts( 'mo2f_phone_js' );
			wp_register_style( 'mo2f_phone', plugins_url( 'includes/css/phone.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			wp_print_styles( 'mo2f_phone' );
			if ( get_site_option( 'mo2f_enable_login_popup_customization' ) ) {
				$this->mo2f_output_custom_login_popup_css();
			}
		}

		/**
		 * This function returns array of methods
		 *
		 * @param object $current_user object containing user details.
		 * @return array
		 */
		public function fetch_methods( $current_user = null ) {

			$methods = array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM );
			if ( apply_filters( 'mo2f_is_lv_needed', false ) ) {
				array_push( $methods, MoWpnsConstants::OTP_OVER_WHATSAPP );
			}
			if ( ! is_null( $current_user ) && ( 'administrator' !== $current_user->roles[0] ) && ! get_site_option( 'mo2f_email' ) || ! get_site_option( 'mo2f_customerKey' ) ) {
				$methods = array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM, MoWpnsConstants::OUT_OF_BAND_EMAIL );
			}
			if ( get_site_option( 'duo_credentials_save_successfully' ) ) {
				array_push( $methods, 'DUO' );
			}
			return $methods;
		}

		/**
		 * Removes account details.
		 *
		 * @return void
		 */
		public function mo2f_remove_account_details() {
			delete_site_option( 'mo2f_customerKey' );
			delete_site_option( 'mo2f_api_key' );
			delete_site_option( 'mo2f_customer_token' );
			delete_site_option( 'mo_wpns_transactionId' );
			delete_site_option( 'mo_wpns_registration_status' );
			delete_site_option( 'mo_2factor_admin_registration_status' );
			delete_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
		}

		/**
		 * Shows message.
		 *
		 * @param boolean $is_ajax Ajax call.
		 * @return bool
		 */
		public function mo2f_ilvn( $is_ajax = true ) {
			$data         = apply_filters( 'mo2f_is_lv_needed', false );
			$show_message = new MoWpnsMessages();
			if ( $data && ! get_site_option( 'mo2fa_lk' ) ) {
				if ( current_user_can( 'manage_options' ) ) {
					$message = 'Please <a href="' . admin_url() . 'admin.php?page=mo_2fa_my_account" target="_blank">click here</a> to verify your license before configuring the plugin.';
				} else {
					$message = MoWpnsMessages::ERROR_DURING_PROCESS;
				}
				if ( $is_ajax ) {
					wp_send_json_error( MoWpnsMessages::lang_translate( $message ) );
				} else {
					$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $message ), 'ERROR' );
					return true;
				}
			}
		}

		/**
		 * Function to show Login Transactions
		 *
		 * @param array $usertranscations - Database entries that needs to be shown.
		 * @return void
		 */
		public function mo2f_show_login_transactions_report( $usertranscations ) {
			foreach ( $usertranscations as $usertranscation ) {
					echo '<tr><td>' . esc_html( $usertranscation->ip_address ) . '</td><td>' . esc_html( $usertranscation->username ) . '</td><td>';
				if ( MoWpnsConstants::FAILED === $usertranscation->status || MoWpnsConstants::PAST_FAILED === $usertranscation->status ) {
					echo '<span style=color:red>' . esc_html( MoWpnsConstants::FAILED ) . '</span>';
				} elseif ( MoWpnsConstants::SUCCESS === $usertranscation->status ) {
					echo '<span style=color:green>' . esc_html( MoWpnsConstants::SUCCESS ) . '</span>';
				} else {
					echo 'N/A';
				}
				echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $usertranscation->created_timestamp ) ) . '</td></tr>';
			}
		}

		/**
		 * This function redirect user to given url.
		 *
		 * @param object $user object containing user details.
		 * @param string $redirect_to redirect url.
		 * @return void
		 */
		public function mo2f_redirect_user_to( $user, $redirect_to ) {
			$roles            = $user->roles;
			$current_role     = array_shift( $roles );
			$redirection_type = get_site_option( 'mo2f_redirect_url_for_users', 'redirect_all' );
			if ( get_site_option( 'mo2f_enable_custom_redirect' ) ) {
				if ( 'redirect_all' === $redirection_type ) {
					$redirect_url = get_option( 'mo2f_custom_redirect_url', ! empty( $redirect_to ) ? $redirect_to : home_url() );
				} else {
					$redirect_url = isset( get_option( 'mo2f_custom_login_urls' )[ 'mo2fa_' . $current_role ] ) ? get_option( 'mo2f_custom_login_urls' )[ 'mo2fa_' . $current_role ] : ( ! empty( $redirect_to ) ? $redirect_to : home_url() );
				}
			} else {
				if ( is_multisite() && is_super_admin( $user->ID ) ) {
					$redirect_url = isset( $redirect_to ) && ! empty( $redirect_to ) ? $redirect_to : admin_url();
				} else {
					if ( 'administrator' === $current_role ) {
						$redirect_url = isset( $redirect_to ) && ! empty( $redirect_to ) ? $redirect_to : admin_url();
					} else {
						$redirect_url = isset( $redirect_to ) && ! empty( $redirect_to ) ? $redirect_to : home_url();
					}
				}
			}
			if ( MO2f_Utility::get_index_value( 'GLOBALS', 'mo2f_is_ajax_request' ) ) {
				$redirect = array(
					'redirect' => $redirect_url,
				);
				wp_send_json_success( $redirect );
			} else {
				wp_safe_redirect( $redirect_url );
				exit();
			}
		}

		/**
		 * CheckS url validation.
		 *
		 * @param string $url Url.
		 * @return bool
		 */
		public function mo2f_check_url_validation( $url ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			$path = rtrim( $path, '/' );
			$slug = basename( $path );
			$page = get_page_by_path( $slug );
			if ( $page || rtrim( home_url(), '/' ) === rtrim( $url, '/' ) || rtrim( admin_url(), '/' ) === rtrim( $url, '/' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Gets default page.
		 *
		 * @param bool $is_lv_needed LV needed.
		 * @return string
		 */
		public function mo2f_get_default_page( $is_lv_needed ) {
			$page = ( $is_lv_needed && ! get_site_option( 'mo2f_customerKey' ) ) || ! current_user_can( 'manage_options' ) ? 'mo_2fa_my_account' : 'mo_2fa_two_fa';
			return $page;
		}

		/**
		 * Function to show user details
		 *
		 * @return void
		 */
		public function mo2f_show_unregistered_user_details() {
			global $mo2fdb_queries;
			$users = get_users();
			echo ' <table  id="mo2f_unregistered_user_details" class="display" cellspacing="0" width="100%" style="display:none">
      <thead> 
        <tr>
			<th>' . esc_html__( 'Username', 'miniorange-2-factor-authentication' ) . '</th>
			<th>' . esc_html__( 'Email', 'miniorange-2-factor-authentication' ) . '</th>
			<th>' . esc_html__( 'Role', 'miniorange-2-factor-authentication' ) . '</th>
			<th>' . esc_html__( 'Method Selected', 'miniorange-2-factor-authentication' ) . '</th>
			<th>' . esc_html__( 'Blocked User', 'miniorange-2-factor-authentication' ) . '</th>             
        </tr>
      </thead><tbody>';
			$entries = false;
			foreach ( $users as $user ) {
				$user_role                     = $user->roles[0];
				$wp_user                       = get_user_by( 'id', $user->ID );
				$mo2f_user_registration_status = $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
				$mo2f_method_selected          = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
				if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' !== $mo2f_user_registration_status ) {
					$entries = true;
					echo '<tr><td>' . esc_html( $wp_user->user_login ) .
					'</td><td>' . esc_html( $user->user_email ) .
					'</td><td>' . esc_html( $user_role ) .
					'</td><td>' .
					'<span>';
					echo esc_html( ( empty( $mo2f_method_selected ) ) ? 'None' : $mo2f_method_selected );
					echo '</span>';
					echo '</td><td>';
					if ( get_site_option( 'mo2f_grace_period' ) && $this->mo2f_is_grace_period_expired( $user ) && 'block_user_login' === get_site_option( 'mo2f_graceperiod_action' ) ) {
						?>
					<button onclick="mo2f_unblock_user(<?php echo esc_js( $user->ID ); ?>)" id="unblock-button-<?php echo esc_attr( $user->ID ); ?>" class="mo2f-reset-settings-button"><?php echo esc_html__( 'Unblock User', 'miniorange-2-factor-authentication' ); ?></button>
						<?php
					}
					echo '</td> </tr>';
				} else {
					continue;
				}
			}
			echo '
	</tbody></table>';
		}

		/**
		 * Shows 2FA registered user entries.
		 *
		 * @return void
		 */
		public function mo2f_show_registered_user_details() {
			global $mo2fdb_queries;
			$users = get_users();
			echo ' <table  id="mo2f_registered_user_details" class="display" cellspacing="0" width="100%" style="display:none">
      <thead > 
        <tr>
			<th>Username</th>
			<th>Email</th>
			<th>Role</th>
			<th>Method Selected</th>
			<th>Reset 2-Factor</th>      
        </tr>
      </thead><tbody>';
			foreach ( $users as $user ) {
				$user_role                     = $user->roles[0];
				$wp_user                       = get_user_by( 'id', $user->ID );
				$mo2f_user_registration_status = $mo2fdb_queries->mo2f_get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
				if ( 'MO_2_FACTOR_PLUGIN_SETTINGS' === $mo2f_user_registration_status ) {
						$mo2f_method_selected = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
						echo '<tr class="mo2f-registered-row" ><td>' . esc_html( $wp_user->user_login ) .
						'</td><td>' . esc_html( $user->user_email ) .
						'</td><td>' . esc_html( $user_role ) .
						'</td><td>' .
						'<span>';
						echo esc_html( ( empty( $mo2f_method_selected ) ) ? 'None' : $mo2f_method_selected );
						echo '</span>';
						echo '</td><td>';
					?>
				<form action="<?php echo esc_url( wp_nonce_url( 'users.php?page=reset&amp;action=reset_edit&amp;user_id=' . esc_attr( $user->ID ), 'reset_edit', 'mo2f_reset-2fa' ) ); ?>" method="post" name="reset2fa" id="reset2fa">
					<input type="submit" name="mo2f_reset_2fa" id="mo2f_reset_2fa" value="Reset 2FA" class="mo2f-reset-settings-button" />
				</form>
					<?php
						echo '</td> </tr>';
				} else {
					continue;
				}
			}

			echo '
</tbody></table>';
		}

		/**
		 * Return Loader html.
		 *
		 * @return string
		 */
		public function mo2f_show_loader() {
			return 'jQuery("#mo2f_2fa_popup_dashboard_loader").html("<span class=\'mo2f_loader\' id=\'mo2f_loader\'></span>");
				jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "block");';
		}

		/**
		 * Return Loader html.
		 *
		 * @return string
		 */
		public function mo2f_hide_loader() {
			return 'jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "none");';
		}
	}

	new Mo2f_Common_Helper();
}
