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
use TwoFA\Onprem\Mo2f_Main_Handler;
use TwoFA\Onprem\MO2f_Utility;
use TwoFA\Onprem\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MocURL;
use TwoFA\Onprem\Mo2f_Inline_Popup;
if ( ! class_exists( 'Mo2f_Common_Helper' ) ) {
	/**
	 * Class Mo2f_Common_Helper
	 */
	class Mo2f_Common_Helper {

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
		 * Creates and sends backupcodes.
		 *
		 * @param string $session_id_encrypt Session Id.
		 * @return array
		 */
		public function mo2f_create_and_send_backupcodes_inline( $session_id_encrypt ) {
			global $mo2fdb_queries;
			$id = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'mo2f_current_user_id' );
			update_site_option( 'mo2f_is_inline_used', '1' );
			$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $id );
			if ( empty( $mo2f_user_email ) ) {
				$currentuser     = get_user_by( 'id', $id );
				$mo2f_user_email = $currentuser->user_email;
			}
			$generate_backup_code = new MocURL();
			$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );
			$codes                = explode( ' ', $codes );
			$result               = MO2f_Utility::mo2f_email_backup_codes( $codes, $mo2f_user_email );
			update_user_meta( $id, 'mo_backup_code_generated', 1 );
			update_user_meta( $id, 'mo_backup_code_screen_shown', 1 );
			return $codes;
		}

		/**
		 * This function used to include css and js files.
		 *
		 * @return void
		 */
		public function mo2f_echo_js_css_files() {
			wp_register_style( 'mo2f_style_settings', plugins_url( 'includes/css/twofa_style_settings.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_print_styles( 'mo2f_style_settings' );

			wp_register_script( 'mo2f_bootstrap_js', plugins_url( 'includes/js/bootstrap.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'mo2f_bootstrap_js' );
			if ( get_site_option( 'mo2f_enable_login_popup_customization' ) ) {
				wp_register_style( 'mo2f_custom-login-popup', plugins_url( 'includes/css/mo2f_login_popup_ui.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
				wp_print_styles( 'mo2f_custom-login-popup' );
			}
		}

		/**
		 * Return the handler object for selected method.
		 *
		 * @param string $selected_method Twofa method name.
		 * @return object
		 */
		public function mo2f_get_object( $selected_method ) {
			$class_name = 'Mo2f_' . str_replace( ' ', '', $selected_method ) . '_Handler';
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
		 * Checks if the 2FA is set for this user.
		 *
		 * @param int $current_user_id user id.
		 * @return bool
		 */
		public function mo2f_is_2fa_set( $current_user_id ) {
			global $mo2fdb_queries;
			return MoWpnsConstants::MO_2_FACTOR_PLUGIN_SETTINGS === $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $current_user_id );
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
			if ( get_site_option( 'mo2f_enable_backup_methods' ) && in_array( 'mo2f_back_up_codes', $backup_methods, true ) ) {
				$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $current_user_id );
				if ( empty( $mo2f_user_email ) ) {
					$currentuser     = get_user_by( 'id', $current_user_id );
					$mo2f_user_email = $currentuser->user_email;
				}
				$generate_backup_code = new MocURL();
				$codes                = $generate_backup_code->mo_2f_generate_backup_codes( $mo2f_user_email, site_url() );
				$code_generate        = get_user_meta( $current_user_id, 'mo_backup_code_generated', false );
				if ( empty( $code_generate ) && 'InternetConnectivityError' !== $codes && 'DBConnectionIssue' !== $codes && 'UnableToFetchData' !== $codes && 'UserLimitReached' !== $codes && 'ERROR' !== $codes && 'LimitReached' !== $codes && 'AllUsed' !== $codes && 'invalid_request' !== $codes ) {
					$inline_popup = new Mo2f_Inline_Popup();
					$codes        = $this->mo2f_create_and_send_backupcodes_inline( $session_id );
					$inline_popup->mo2f_show_generated_backup_codes_inline( $redirect_to, $session_id, $codes );
				}
			}
			$pass2fa = new Mo2f_Main_Handler();
			$pass2fa->mo2fa_pass2login( $redirect_to, $session_id );
			exit;
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
			$mo2fdb_queries->insert_user( $current_user_id, array( 'user_id' => $current_user_id ) );
			$mo2fdb_queries->update_user_details(
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
		 * This function will return the configured method value
		 *
		 * @param string $currentuserid It will carry the current user id .
		 * @return array
		 */
		public function mo2fa_return_methods_value( $currentuserid ) {
			global $mo2fdb_queries;
			$count_methods          = $mo2fdb_queries->get_user_configured_methods( $currentuserid );
			$value                  = empty( $count_methods ) ? array() : get_object_vars( $count_methods[0] );
			$configured_methods_arr = array();
			foreach ( $value as $config_status_option => $config_status ) {
				if ( strpos( $config_status_option, 'config_status' ) ) {
					$config_status_string_array = explode( '_', $config_status_option );
					$config_method              = MoWpnsConstants::mo2f_convert_method_name( $config_status_string_array[1], 'pascal_to_cap' );
					if ( '1' === $value[ $config_status_option ] ) {
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
			$error_status_and_message = array(
				'InternetConnectivityError' => MoWpnsMessages::BACKUP_CODE_INTERNET_ISSUE,
				'AllUsed'                   => MoWpnsMessages::BACKUP_CODE_ALL_USED,
				'UserLimitReached'          => MoWpnsMessages::BACKUP_CODE_DOMAIN_LIMIT_REACH,
				'LimitReached'              => MoWpnsMessages::BACKUP_CODE_LIMIT_REACH,
				'invalid_request'           => MoWpnsMessages::BACKUP_CODE_INVALID_REQUEST,
			);
			foreach ( $error_status_and_message as $error_status => $error_message ) {
				if ( $status === $error_status ) {
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
			$grace_period = get_option( 'mo2f_grace_period_value' );
			if ( get_option( 'mo2f_grace_period_type' ) === 'hours' ) {
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
			<input type="hidden" name="twofa_status" value="' . esc_attr( $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $current_user_id ) ) . '"/> 
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
			jQuery("a[href=\'#mo2f_validation_screen\']").click(function() {
				jQuery("#mo2f_backto_2fa_validation").submit();
			});
			jQuery("a[href=\'#mo2f_inline_form\']").click(function() {
				jQuery("#mo2f_backto_inline_registration").submit();
			});
			jQuery("a[href=\'#mo2f_mfa_form\']").click(function() {
				jQuery("#mo2f_backto_mfa_form").submit();
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
				<input type="hidden" name="twofa_status" value="' . esc_attr( $mo2fdb_queries->get_user_detail( 'mo_2factor_user_registration_status', $user_id ) ) . '"/>    
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
			$mo2f_user_phone = $mo2fdb_queries->get_user_detail( 'mo2f_user_phone', $user_id );
			$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : '';
			$skeleton        = array(
				'##input_field##'  => '<br><span style="font-size:17px;"><i>Enter your Phone:</i></span><br><br><input class="mo2f_table_textbox mb-mo-4" style="width:200px;" type="text" name="mo2f_phone_email_telegram" id="mo2f_phone_field"
                                    value="' . esc_attr( $user_phone ) . '" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}"
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
			$mo2f_user_email = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
			$email           = $mo2f_user_email ? $mo2f_user_email : get_user_by( 'id', $user_id )->user_email;
			$skeleton        = array(
				'##input_field##'  => '<br><div class="modal-body" style="height:auto;">
                                    <span style="font-size:17px;">Enter your Email:</span>
                                    <input type ="text" style="height:25px;margin-left:10px;" id="emailEntered" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" name="mo2f_phone_email_telegram"  size="30" required value="' . esc_attr( $email ) . '"/><br>
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
				'##input_field##'  => '<input class="mo2f_table_textbox" style="width:200px;height:25px;" type="text" name="mo2f_phone_email_telegram" id="mo2f_telegram"
                                    value="' . esc_attr( $chat_id ) . '" pattern="[0-9]+" 
                                    title="' . esc_attr__( 'Enter Chat ID recieved on your Telegram without any space or dashes', 'miniorange-2-factor-authentication' ) . '"/><br></h4>',
				'##instructions##' => '<h4 class="mo_wpns_not_bold">' . esc_html__( '1. Open the telegram app and search for \'miniorange2fa\'. Click on start button or send \'/start\' message.', 'miniorange-2-factor-authentication' ) . '</h4>
                                    <h4 class="mo_wpns_not_bold">' . esc_html__( '2. Enter the recieved chat id in the below box.', 'miniorange-2-factor-authentication' ) . '</h4>',

			);
			return $skeleton;

		}

		/**
		 * Gets script for dashboard.
		 *
		 * @return string
		 */
		public function mo2f_get_dashboard_script_for_otp_based_methods() {
			$script = '<script>
			jQuery("#verify").click(function() {
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
			$script           = '<script>	jQuery(document).ready(function($){
				jQuery(function(){
				var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";
				var selected_2FA_method = jQuery("input[name=mo2f_otp_based_method]").val();
				jQuery("#verify").click(function()
				{  
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
						if( response["success"] ){
							if( selected_2FA_method == "' . esc_js( MoWpnsConstants::OUT_OF_BAND_EMAIL ) . '"){
								jQuery("#showPushImage").css("display","block");
								jQuery("#verify").css("display","none");
								emailVerificationPoll();
							} else{
								jQuery("#go_back_verify").css("display","none");
								jQuery("#mo2f_validateotp_form").css("display","block");
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
			{   
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
			$show_validation_form = TwoFAMoSessions::get_session_var( 'mo2f_otp_send_true' ) ? 'block' : 'none';
			$common_helper        = new Mo2f_Common_Helper();
			$html                 = '<div class="mo2f-setup-popup-dashboard">';
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
						<div id="otpMessaghide" style="display: none;">
							<p class="mo2fa_display_message_frontend" style="text-align: left !important; ">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
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
								<input type="button" id ="verify" name="verify" class="button button-primary button-large" value="' . esc_attr__( 'Send ' . ( MoWpnsConstants::OUT_OF_BAND_EMAIL !== $current_selected_method ? 'OTP' : 'Link' ), 'miniorange-2-factor-authentication' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string literal.
								$html .= '" />
								<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
							</form>
						</div>  

						<form name="f" method="post" action="" id="mo2f_validateotp_form" style="display:' . esc_attr( $show_validation_form ) . '">
							<input type="hidden" name="option" value="mo2f_validate_otp_for_configuration"/>
							<input type="hidden" name="mo2f_session_id" value="' . esc_attr( $session_id ) . '"/>
							<input type="hidden" name="mo2f_otp_based_method" value="' . esc_attr( $current_selected_method ) . '"/>
							<input type="hidden" name="mo2f_validate_otp_for_configuration_nonce" value=" ' . esc_attr( wp_create_nonce( 'mo2f-configure-otp-based-methods-validate-nonce' ) ) . '"/> <p>' . esc_html__( 'Enter One Time Passcode', 'miniorange-2-factor-authentication' ) . '</p>
							<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '" style="width:95%;"/> <a href="#resendsmslink" style="color:#a7a7a8 ;text-decoration:none;" >' . esc_html__( 'Resend OTP', 'miniorange-2-factor-authentication' ) . '</a>
							<br><br>
							<input type="button" name="validate" id="validate" class="button button-primary button-large" value="' . esc_attr__( 'Validate OTP', 'miniorange-2-factor-authentication' ) . '"/>
						</form>
						';
						$html         .= ' 	<div id="showPushImage" style="display:none;">
						<div class="mo2fa_text-align-center">We are waiting for your approval...</div>
<div class="mo2fa_text-align-center">
   <img src="' . esc_url( plugins_url( 'includes/images/ajax-loader-login.gif', dirname( __FILE__ ) ) ) . '"/>
</div></div><br>';
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
			var html = '<div id=\"otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
			jQuery('#otpMessage').empty();
			jQuery('#otpMessaghide').after(html);
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
			$success_response                  = array( $this, 'mo2f_get_mo_login_registration_success_response_' . $prev_screen . '_script' );
			$error_response                    = array( $this, 'mo2f_get_mo_login_registration_error_response_' . $prev_screen . '_script' );
			$html                              = '<div>';
			$html                             .= $skeleton['##crossbutton##'];
			$html                             .= $skeleton['##pagetitle##'];
			$html                             .= '<div>';
				$html                         .= '	<div id="otpMessaghide" class="hidden">
				<p class="" style="">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
			</div>';
								$html         .= '<form name="mo2f_inline_register_form" id="mo2f_inline_register_form" method="post" action="">
								<input type="hidden" name="option" value="miniorange_inline_register" />
								<input type="hidden" name="mo2f_inline_register_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-inline-register-nonce' ) ) . '"/>
								<p>This method requires you to have an account with miniOrange.</p>
								<table class="w-full">
									<tr>
									<td><b><span>*</span>Email:</b></td>
									<td><input class="w-full" type="email" name="email"
									required placeholder="person@example.com"/></td>
									</tr>
									<tr>
										<td><b><span>*</span>Password:</b></td>
										<td><input class="w-full" required type="password"
									name="password" placeholder="Choose your password (Min. length 6)" /></td>
									</tr>
									<tr>
										<td><b><span>*</span>Confirm Password:</b></td>
										<td><input class="w-full" required type="password"
									name="confirmPassword" placeholder="Confirm your password" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><br>';
										$html .= '<input type="button" name="submit" value="Create Account" id="mo2f_register"
									class="mo2f-save-settings-button" />';
									$html     .= '&nbsp&nbsp&nbsp&nbsp<a href="#mo2f_account_exist"><button class="mo2f-reset-settings-button">Already have an account?</button></a>
									</tr>
								</table>
							</form>
				<form name="f" id="mo2f_inline_login_form" method="post" action="" hidden>
					<p><b>It seems you already have an account with miniOrange. Please enter your miniOrange email and password.<br></b><a target="_blank" href="' . esc_url( MO_HOST_NAME . '/moas/idp/resetpassword' ) . '"> Click here if you forgot your password?</a></p>
					<input type="hidden" name="option" value="miniorange_inline_login"/>
					<input type="hidden" name="mo2f_inline_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f-inline-login-nonce' ) ) . '"/>
					<table class="w-full">
						<tr>
						<td><b><span>*</span>Email:</b></td>
						<td><input class="w-full" type="email" name="miniorange_email" id="miniorange_email"
						required placeholder="person@example.com"/></td>
						</tr>
						<tr>
							<td><b><span>*</span>Password:</b></td>
							<td><input class="w-full" required type="password"
							name="miniorange_password" placeholder="Enter your miniOrange password" /></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>';
						$html                 .= '<br><input type="button" id="mo2f_login" class="mo2f-save-settings-button" value="' . esc_attr__( 'Sign In', 'miniorange-2-factor-authentication' ) . '" />';
						$html                 .= '&nbsp&nbsp&nbsp&nbsp<input type="button" id="cancel_link" class="mo2f-reset-settings-button" value="' . esc_attr__( 'Go Back To Registration', 'miniorange-2-factor-authentication' ) . '" />
						</tr>
					</table>
				</form>
							<br>';
							$html             .= $skeleton['##miniorangelogo##'];
						$html                 .= '</div>
				</div>
				<div id="mo2f_2fa_popup_dashboard_loader" class="modal" hidden></div>
			<form name="f" method="post" action="" id="mo2f_goto_two_factor_form" >              
				<input type="hidden" name="option" value="miniorange_back_inline"/>
				<input type="hidden" name="miniorange_inline_two_factor_setup" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-setup-nonce' ) ) . '" />
			</form>
         
		<script>';
			$html                             .= 'myaccount' === $prev_screen ? 'jQuery("#mo_2fa_my_account").addClass("side-nav-active");' : '';
			$html                             .= 'jQuery("#mo2f_inline_back_btn").click(function() {  
					jQuery("#mo2f_goto_two_factor_form").submit();
			});
			jQuery("a[href=\'#mo2f_account_exist\']").click(function (e) {
				e.preventDefault();
				jQuery("#mo2f_inline_login_form").show();
				var input = jQuery("input[name=miniorange_email]");
				var len = input.val().length;
				input[0].focus();
				jQuery("#mo2f_inline_register_form").hide();
				jQuery("#otpMessage").hide();
			});
			jQuery("#cancel_link").click(function(){                               
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
				jQuery("#mo2f_2fa_popup_dashboard_loader").html("<span class=\'mo2f_loader\' id=\'mo2f_loader\'></span>");
				jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "block");
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_miniorange_sign_in",
					email: jQuery("input[name=\'miniorange_email\']").val(),
					password: jQuery("input[name=\'miniorange_password\']").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "none");
					if (response.success) {
						' . call_user_func( $success_response ) . '
					} else {
					 ' . call_user_func( $error_response ) . '
					}
				});
			});';
			$html                             .= 'jQuery("#mo2f_register").click(function() {
				var nonce = "' . wp_create_nonce( 'mo-two-factor-ajax-nonce' ) . '";
				jQuery("#mo2f_2fa_popup_dashboard_loader").html("<span class=\'mo2f_loader\' id=\'mo2f_loader\'></span>");
				jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "block");
				var data = {
					action: "mo_two_factor_ajax",
					mo_2f_two_factor_ajax: "mo2f_miniorange_sign_up",
					email: jQuery("input[name=\'email\']").val(),
					password: jQuery("input[name=\'password\']").val(),
					confirmPassword: jQuery("input[name=\'confirmPassword\']").val(),
					nonce: nonce,
				};
				jQuery.post(ajaxurl, data, function(response) {
				    jQuery("#mo2f_2fa_popup_dashboard_loader").css("display", "none");
					if (response.success) {
						' . call_user_func( $success_response ) . '
					} else {
					 ' . call_user_func( $error_response ) . '
					}
				});
			});';
			$html                             .= "
			function mo2f_show_message(response) {
				var html = '<div id=\"otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
				jQuery('#otpMessage').empty();
				jQuery('#otpMessaghide').after(html);
			}";
			$html                             .= '</script>';
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
			$html      = '<div class="mo2f_kba_setup_popup_dashboard">';
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
				$html .= '	<div id="otpMessaghide" style="display: none;">
				<p class="mo2fa_display_message_frontend" style="text-align: left !important; ">' . wp_kses( $login_message, array( 'b' => array() ) ) . '</p>
			</div>';

			$html         .= '<form name="f" method="post" action="">
                ' . $this->mo2f_configure_kba_questions() . '
                <br/>
                <div class="row">
                    <div style="margin: 0 auto; width: 100px;">
                        <input type="button" name="validate" id="mo2f_save_kba" class="button button-primary button-large" value="' . esc_attr__( 'Save', 'miniorange-2-factor-authentication' ) . '" />
                    </div>
                </div>
                <input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"/>
                <input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
            </form>';
			$common_helper = new Mo2f_Common_Helper();
			if ( 'mo2f_inline_form' === $prev_screen ) {
				$prev_screen = 'mo2f_inline_form';
				$html       .= $common_helper->mo2f_go_back_link_form( $prev_screen );
			}
			$html .= $common_helper->mo2f_customize_logo();
			$html .= '</div></div></div>';
			$html .= '<script>';
			$html .= "
		function mo2f_show_message(response) {
			var html = '<div id=\"otpMessage\"><p class=\"mo2fa_display_message_frontend\">' + response + '</p></div>';
			jQuery('#otpMessage').empty();
			jQuery('#otpMessaghide').after(html);
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
			$html  = '<div class="mo2f_kba_header">' . esc_html__( 'Please choose 3 questions', 'miniorange-2-factor-authentication' ) . '</div>';
			$html .= '<br>';
			$html .= '<table id="mo2f_configure_kba" cellspacing="10">';
			$html .= '<thead>';
			$html .= '<tr class="mo2f_kba_header">';
			$html .= '<th>' . esc_html__( 'Sr. No.', 'miniorange-2-factor-authentication' ) . '</th>';
			$html .= '<th class="mo2f_kba_tb_data">' . esc_html__( 'Questions', 'miniorange-2-factor-authentication' ) . '</th>';
			$html .= '<th>' . esc_html__( 'Answers', 'miniorange-2-factor-authentication' ) . '</th>';
			$html .= '</tr>';
			$html .= '</thead>';

			for ( $i = 1; $i <= 3; $i++ ) {
				$html .= '<tr class="mo2f_kba_body">';
				$html .= '<td class="mo2f_align_center">' . $i . '.</td>';

				if ( $i < 3 ) {
					$html .= '<td class="mo2f_kba_tb_data">' . $this->mo2f_kba_question_set( $i ) . '</td>';
				} else {
					$html .= '<td class="mo2f_kba_tb_data">';
					$html .= '<input class="mo2f_kba_ques" type="text" style="width: 100%;" name="mo2f_kbaquestion_3" id="mo2f_kbaquestion_3" required="true" placeholder="' . esc_attr__( 'Enter your custom question here', 'miniorange-2-factor-authentication' ) . '"/>';
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
			$html .= 'var mo_option_to_hide1;';
			$html .= 'var mo_option_to_hide2;';
			$html .= 'function mo_option_hide(list) {';
			$html .= 'var list_selected = document.getElementById("mo2f_kbaquestion_" + list).selectedIndex;';
			$html .= 'if (mo_option_to_hide1 && list == 2) { mo_option_to_hide1.style.display = "block"; }';
			$html .= 'if (mo_option_to_hide2 && list == 1) { mo_option_to_hide2.style.display = "block"; }';
			$html .= 'if (list == 1 && list_selected != 0) {';
			$html .= 'mo_option_to_hide2 = document.getElementById("mq" + list_selected + "_2");';
			$html .= 'mo_option_to_hide2.style.display = "none";';
			$html .= '}';
			$html .= 'if (list == 2 && list_selected != 0) {';
			$html .= 'mo_option_to_hide1 = document.getElementById("mq" + list_selected + "_1");';
			$html .= 'mo_option_to_hide1.style.display = "none";';
			$html .= '}';
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
			$question_set = array(
				'What is your first company name?',
				'What was your childhood nickname?',
				'In what city did you meet your spouse/significant other?',
				'What is the name of your favorite childhood friend?',
				'What school did you attend for sixth grade?',
				'In what city or town was your first job?',
				'What is your favorite sport?',
				'Who is your favorite sports player?',
				'What is your grandmother\'s maiden name?',
				'What was your first vehicle\'s registration number?',
			);

			$html  = '<select name="mo2f_kbaquestion_' . esc_attr( $question_no ) . '" id="mo2f_kbaquestion_' . esc_attr( $question_no ) . '" class="mo2f_kba_ques" required onchange="mo_option_hide(' . esc_attr( $question_no ) . ')">';
			$html .= '<option value="" selected>' . esc_html__( 'Select your question', 'miniorange-2-factor-authentication' ) . '</option>';

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
						update_option( 'mo_wpns_admin_phone', $customer_key['phone'] );
					}
					update_option( 'mo2f_email', $email );
					$id         = isset( $customer_key['id'] ) ? $customer_key['id'] : '';
					$api_key    = isset( $customer_key['apiKey'] ) ? $customer_key['apiKey'] : '';
					$token      = isset( $customer_key['token'] ) ? $customer_key['token'] : '';
					$app_secret = isset( $customer_key['appSecret'] ) ? $customer_key['appSecret'] : '';
					$this->mo2f_save_customer_configurations( $id, $api_key, $token, $app_secret );
					update_site_option( base64_encode( 'totalUsersCloud' ), get_site_option( base64_encode( 'totalUsersCloud' ) ) + 1 ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to obfuscate the option as it will be stored in database.
					$mocurl  = new MocURL();
					$content = json_decode( $mocurl->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'PREMIUM' ), true );
					if ( 'SUCCESS' !== $content['status'] ) {
						$content = json_decode( $mocurl->get_customer_transactions( get_option( 'mo2f_customerKey' ), get_option( 'mo2f_api_key' ), 'DEMO' ), true );
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
			update_option( 'mo2f_customerKey', $id );
			update_option( 'mo2f_api_key', $api_key );
			update_option( 'mo2f_customer_token', $token );
			update_option( 'mo2f_app_secret', $app_secret );
			update_option( 'mo2f_miniorange_admin', $id );
			update_site_option( 'mo_2factor_admin_registration_status', 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' );
		}

		/**
		 * Gets the script for testing.
		 *
		 * @return string
		 */
		public function mo2f_get_test_script() {
			$script = '<script>			jQuery("#mo2f_validate").click(function() {
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

			$script = '<script>		
			var twofa_method = "' . esc_js( $twofa_method ) . '";
			var attemptleft = 3;	
			jQuery("#mo2f_validate").click(function() {
				
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
					if (response.success) {
						jQuery("#mo2f_inline_otp_validated_form").submit();
					} else if(response.data == "LIMIT_EXCEEDED"){
						mologinback();
					} else if( response.data == \'INVALID_ANSWERS\') {
						jQuery("#mo2f_answer_1").val("");
						jQuery("#mo2f_answer_2").val("");
						jQuery("input[name=mo2f_answer_1]").focus();
						mo2f_show_message("Invalid answers. Please enter the correct answers.");	
					} else{
						jQuery("#mo2fa_softtoken").val("");
						attemptleft = attemptleft - 1;
						var span =   document.getElementById("mo2f_attempt_span");
						span.textContent = attemptleft;
						mo2f_show_message("Invalid OTP. Please enter the correct OTP.");
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
			$html = '<div style="float:right;"><img
							alt="logo"
							src="' . esc_url( plugins_url( 'includes/images/miniOrange2.png', dirname( __FILE__ ) ) ) . '"/></div>';
			return $html;

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
			require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-google-auth-app-links.php';
			$html          = '<div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
				<div class="mo2f-modal-backdrop">
				</div>
				<div class="mo2f_modal-dialog mo2f_modal-lg">
					<div class="login mo_customer_validation-modal-content">';
					$html .= '<h4>';

						$html                 .= '<button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close"
						title="' . esc_attr__( 'Back to login', 'miniorange-2-factor-authentication' ) . '"
						onclick="mologinback();"><span aria-hidden="true">&times;</span></button>';
						$html                 .= esc_html__( 'Configure Google/Authy/Microsoft Authenticator', 'miniorange-2-factor-authentication' ) .
						'</h4>';
					$html                     .= '<hr>';
					$html                     .= '<table class="mo2f_configure_ga">
								<tr>
									<td class="mo2f_google_authy_step2">';
									$html     .= '<div id="otpMessage" style="display:none;"><p id="mo2f_gauth_inline_message" class="mo2fa_display_message_frontend" style="text-align: left !important;"></p>
										</div>';
										$html .= '<div style="line-height: 4; margin-left:20px;" id = "mo2f_choose_app_tour">
												<label for="authenticator_type"><b>1. Choose an Authenticator app:</b></label>
						
												<select id="authenticator_type">';
			foreach ( $auth_app_links as $auth_app => $auth_app_link ) {
				$html .= '<option data-apptype="' . esc_attr( $auth_app ) . '" data-playstorelink="' . esc_attr( $auth_app_link['Android'] ) . '" data-appstorelink="' . esc_attr( $auth_app_link['Ios'] ) . '">' . esc_html( $auth_app_link['app_name'] ) . '</option>';
			}
						$html .= '</select>
											</div>';

											$html .= '<h4 style="margin-left:20px;">';

											$html .= esc_html__( '2. Scan the QR code from the Authenticator App.', 'miniorange-2-factor-authentication' );

											$html .= '</h4>';
											$html .= '<div style="margin-left:29px;">
											<ol>';

											$html                     .= '<div class="mo2f_gauth" id= "mo2f_google_auth_qr_code" style="background: white;" data-qrcode="' . $data . '" ></div>';
											$html                     .= '<div class="mo2f_gauth_microsoft" id= "mo2f_microsoft_auth_qr_code" style="background:white;display:none" data-qrcode="' . esc_html( $microsoft_url ) . '" ></div>
														</div>
													</div>
													<hr>
													<div style="display: block;width: 110%;">
														<form name="mo2f_validate_code_form" id="mo2f_validate_code_form" method="post" style="margin: 0px;">
															<span><b>';
															$html     .= esc_html__( 'Enter the code from authenticator app:', 'miniorange-2-factor-authentication' );
															$html     .= '</b>
															<input class="mo2f_table_textbox" style="width:230px;margin: 2% 0%;" id="google_auth_code" autofocus="true" required="true"
																type="text" name="google_token" placeholder="' . esc_attr__( 'Enter OTP', 'miniorange-2-factor-authentication' ) . '"
																style="width:95%;"/></span><br><input type="hidden" name="option" value="mo2f_inline_validation_success">
																<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect_to ) . '"/>
																<input type="hidden" name="session_id" value="' . esc_attr( $session_id ) . '"/>
																<input type="hidden" name="miniorange_inline_save_2factor_method_nonce" value="' . esc_attr( wp_create_nonce( 'miniorange-2-factor-inline-save-2factor-method-nonce' ) ) . '" />
																</form>';
																$html .= '<div style="display:flex;">';

													$html .= '<button name="mo2f_validate_gauth" id="mo2f_save_otp_ga_tour" class="button button-primary button-large" style="margin-left:5px;height: 10%;"/>Verify</button>';
													$html .= '</div>';
													$html .= '</div>';
													$html .= '</div>';
													$html .= '	</ol>
										</div>';
			if ( 'dashboard' !== $prev_screen ) {
				$html .= '<br>' . $common_helper->mo2f_go_back_link_form( $prev_screen ) . '<br>';
			}
													$html .= '<br>
									</td>';
													$html .= '<td class="mo2f_vertical_line" ></td>
									<td class="mo2f_google_authy_step3">';
													$html .= '<div><a href="#mo2f_scanbarcode_a">';
													$html .= esc_html__( 'Can\'t scan the QR code? ', 'miniorange-2-factor-authentication' );
													$html .= '</a>';
													$html .= '</div>';
													$html .= '<div  id="mo2f_secret_key" style="background: white;display:none;">
											<ol style="padding-left: 20px;">
												<li>' .
													esc_html__( 'Tap on Menu and select', 'miniorange-2-factor-authentication' ) . '<b>' .
													esc_html__( ' Set up account ', 'miniorange-2-factor-authentication' ) . '</b>.
												</li>
												<li>' .
													esc_html__( 'Select', 'miniorange-2-factor-authentication' ) . '<b>' .
													esc_html__( ' Enter provided key ', 'miniorange-2-factor-authentication' ) . '</b>.
												</li>
												<li>' .
													esc_html__( 'For the', 'miniorange-2-factor-authentication' ) . '<b>' .
													esc_html__( ' Enter account name ', 'miniorange-2-factor-authentication' ) . '</b>' .
													esc_html__( 'field, type your preferred account name.', 'miniorange-2-factor-authentication' ) . '</li>
												<li>' .
													esc_html__( 'For the', 'miniorange-2-factor-authentication' ) .
													'<b>' . esc_html__( ' Enter your key ', 'miniorange-2-factor-authentication' ) . '</b>' .
													esc_html__( 'field, type the below secret key', 'miniorange-2-factor-authentication' ) . ':
												</li>
						
												<div class="mo2f_google_authy_secret_outer_div">
													<div class="mo2f_google_authy_secret_inner_div">
														' . esc_html( $secret ) . '
													</div>
													<div class="mo2f_google_authy_secret">' .
														esc_html__( 'Spaces do not matter', 'miniorange-2-factor-authentication' ) . '.
													</div>
												</div>
												<li>' .
													esc_html__( 'Key type: make sure', 'miniorange-2-factor-authentication' ) . '<b>' .
													esc_html__( ' Time-based ', 'miniorange-2-factor-authentication' ) . '</b>' .
													esc_html__( ' is selected', 'miniorange-2-factor-authentication' ) . '.
												</li>
						
												<li>' . esc_html__( 'Tap Add.' ) . '</li>
											</ol>
										</div><br>';
													$html .= '<div>
											<a href="https://faq.miniorange.com/knowledgebase/sync-mobile-app/" target="_blank">Sync your server time with authenticator app time</a>
											<h4 style="color: red; text-align: center;">Current Server Time: <span id="mo2f_server_time">--</span></h4>
										</div>';
													$html .= '<div id="links_to_apps_tour" style="background-color:white;padding:5px;width:90%;">
											<span id="links_to_apps"></span>
										</div>';
										$html             .= '<div class="mo2f_customize_logo">';
			if ( 'dashboard' !== $prev_screen ) {
				$html .= $common_helper->mo2f_customize_logo();
			}
										$html             .= '</div>';
													$html .= '</td>
								</tr>
							</table>';
													$html .= '</div>
						</div>
					</div><br>';
			$server_time                                   = isset( $_SERVER['REQUEST_TIME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_TIME'] ) ) * 1000 : null;
			$html .= '<script>
						jQuery("a[href=\"#mo2f_scanbarcode_a\"]").click(function(e){
							jQuery("#mo2f_secret_key").slideToggle();
						});
						jQuery(document).ready (function () {
							var serverTime = new Date(Number(' . esc_js( $server_time ) . '));
							var server_time = serverTime.toLocaleTimeString();
							var nonce = "' . esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ) . '";
							var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
							var ms_url = "' . esc_js( $microsoft_url ) . '";
							var gu_url ="' . esc_js( $data ) . '";
							var ga_secret = "' . esc_js( $secret ) . '";
							var session_id = "' . esc_js( $session_id ) . '";
							var redirect_to = "' . esc_js( $redirect_to ) . '";
							document.getElementById("mo2f_server_time").innerHTML = server_time;
							jQuery("#google_auth_code").keypress(function(event) {
								if (event.which === 13) {
									event.preventDefault();
									mo2f_validate_gauth(nonce, ga_secret);
								}
							});
							jQuery("#mo2f_save_otp_ga_tour").click(function(){
								mo2f_validate_gauth(nonce, ga_secret);
							}); 
							jQuery("#authenticator_type").change (function () {
								var selectedAuthenticator = jQuery(this).children("option:selected").data("apptype");
								var playStoreLink = jQuery(this).children("option:selected").data("playstorelink");
								var appStoreLink = jQuery(this).children("option:selected").data("appstorelink");
								jQuery("#links_to_apps").html("<p style=\'background-color:#e8e4e4;padding:5px;width:100%\'>" +
									"Get the Authenticator App - <br><a href=" + playStoreLink + " target=\'_blank\'>Android Play Store</a> &emsp;" +
									"<a href=" + appStoreLink + " target=\'_blank\'>iOS App Store&nbsp;</p>");
								jQuery("#links_to_apps").show();
								var data = {
									"action"  : "mo_two_factor_ajax",
									"mo_2f_two_factor_ajax" : "mo2f_google_auth_set_transient",
									"auth_name"             : selectedAuthenticator,
									"micro_soft_url"        : ms_url,
									"g_auth_url"            : ga_url,
									"session_id"            : session_id,
									"nonce"                 : nonce,	
								};
								jQuery.post(ajaxurl, data, function(response) {
									var prev_screen = "' . esc_js( $prev_screen ) . '";
									if( ! response["success"] && prev_screen == "dashboard" ){
										error_msg("Unknown error occured. Please try again!");
									}
								});
								if( selectedAuthenticator == "msft_authenticator" ){
									jQuery("#mo2f_microsoft_auth_qr_code").css("display","block");
									jQuery("#mo2f_google_auth_qr_code").css("display","none");
								}else{
									jQuery("#mo2f_microsoft_auth_qr_code").css("display","none");
									jQuery("#mo2f_google_auth_qr_code").css("display","block");
								}
								mo2f_show_auth_methods(selectedAuthenticator);
							});
							jQuery(".mo2f_gauth").qrcode({
								"render": "image",
								size: 175,
								"text": jQuery(".mo2f_gauth").data("qrcode")
							});
							jQuery(".mo2f_gauth_microsoft").qrcode({
								"render": "image",
								size: 175,
								"text": jQuery(".mo2f_gauth_microsoft").data("qrcode")
							});
							jQuery(this).scrollTop(0);
							jQuery("#links_to_apps").html("<p style=\'background-color:#e8e4e4;padding:5px;\'>" +
								"Get the Authenticator App - <br><a href=\'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2\' target=\'_blank\'>Android Play Store</a> &emsp;" +
								"<a href=\'http://itunes.apple.com/us/app/google-authenticator/id388497605\' target=\'_blank\'>iOS App Store&nbsp;</p>");
							jQuery("#mo2f_change_app_name").show();
							jQuery("#links_to_apps").show();
							jQuery(\'#mo2f_next_step3\').css(\'display\',\'none\');	
						}); 
					function mo2f_show_auth_methods( selected_method ) {
						var auth_methods = ["google_authenticator", "msft_authenticator", "authy_authenticator", "last_pass_auth", "free_otp_auth", "duo_auth" ];
						auth_methods.forEach ( function( method ) {
						if ( method == selected_method ) {
								jQuery( "#mo2f_" + method + "_instructions" ) . css( "display", "block" );
						} else {
								jQuery( "#mo2f_" + method + "_instructions" ) . css( "display", "none" );
						}
						} ); 
					}
					function mologinback(){
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
				$mo2f_configured_2_f_a_method = $mo2fdb_queries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
				wp_print_scripts( 'jquery' );
				echo '<div id="twoFAtestAlertModal" class="modal" role="dialog">
		<div class="mo2f_modal-dialog">
			<div class="modal-content" style="width:660px !important;">
			<div class="mo2fa_text-align-center">
				<div class="modal-header">
					<h2 class="mo2f_modal-title" style="color: #2271b1;">2FA Setup Successful</h2>
					<span type="button" id="test-methods" class="modal-span-close" data-dismiss="modal">&times;</span>
				</div>
				<div class="mo2f_modal-body">
					<p style="font-size:14px;"><b>' . esc_attr( MoWpnsConstants::mo2f_convert_method_name( $mo2f_configured_2_f_a_method, 'cap_to_small' ) ) . '</b> has been set as your 2-factor authentication method.
					<br>
					<br>Please test the login flow once with 2nd factor in another browser or in an incognito window of the same browser to ensure you don\'t get locked out of your site.</p>
				</div>
				<div class="mo2f_modal-footer">
					<button type="button" id="test-methods-button" class="button button-primary button-large" data-dismiss="modal">Test it!</button>
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
			wp_register_style( 'mo2f_hide-login', plugins_url( 'includes/css/hide-login.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
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

		}

		/**
		 * This function returns array of methods
		 *
		 * @param object $current_user object containing user details.
		 * @return array
		 */
		public function fetch_methods( $current_user = null ) {
			$methods = array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OUT_OF_BAND_EMAIL, MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM );
			if ( ! is_null( $current_user ) && ( 'administrator' !== $current_user->roles[0] ) && ! get_option( 'mo2f_email' ) || ! get_option( 'mo2f_customerKey' ) ) {
				$methods = array( MoWpnsConstants::GOOGLE_AUTHENTICATOR, MoWpnsConstants::SECURITY_QUESTIONS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OTP_OVER_TELEGRAM, MoWpnsConstants::OUT_OF_BAND_EMAIL );
			}
			if ( get_site_option( 'duo_credentials_save_successfully' ) ) {
				array_push( $methods, 'DUO' );
			}
			return $methods;
		}
	}

	new Mo2f_Common_Helper();
}
