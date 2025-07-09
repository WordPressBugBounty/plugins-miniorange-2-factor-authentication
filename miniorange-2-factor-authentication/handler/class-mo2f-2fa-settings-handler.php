<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/twofactor/loginsettings/handler
 */

namespace TwoFA\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MocURL;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_2fa_Settings_Handler' ) ) {

	/**
	 * Class Mo2f_2fa_Settings_Handler
	 */
	class Mo2f_2fa_Settings_Handler {

		use Instance;

		/**
		 * Class Mo2f_Notifications_Save object
		 *
		 * @var object
		 */
		private $show_message;

		/**
		 * Mo2f_2fa_Settings_Handler class custructor.
		 */
		public function __construct() {
			$this->show_message = new MoWpnsMessages();
			add_action( 'wp_ajax_mo2f_login_settings_ajax', array( $this, 'mo2f_login_settings_ajax' ) );
			add_action( 'admin_init', array( $this, 'mo2f_twofa_admin_settings' ) );
		}


		/**
		 * Calls the admin login settings functions according to switch cases.
		 *
		 * @return void
		 */
		public function mo2f_twofa_admin_settings() {

			$nonce = isset( $_POST['mo2f_login_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_login_settings_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-login-settings-nonce' ) ) {
				return;
			}
			$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_download_backup_codes_dashboard':
					$this->mo2f_download_backup_codes_dashboard();
					break;
			}

		}

		/**
		 * Downloads backup code from dashboard.
		 *
		 * @return void
		 */
		public function mo2f_download_backup_codes_dashboard() {
			global $mo2fdb_queries;
			$current_user         = wp_get_current_user();
			$generate_backup_code = new MocURL();
			$mo2f_user_email      = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $current_user->ID ) ?? $current_user->user_email;
			$codes                = $generate_backup_code->mo2f_get_backup_codes( $mo2f_user_email, site_url() );
			$codes                = apply_filters( 'mo2f_basic_plan_settings_filter', $codes, 'generate_backup_codes', array( 'user_id' => $current_user->ID ) );
			$show_message         = new MoWpnsMessages();
			if ( get_transient( 'mo2f_backupcode_generated' . $current_user->ID ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::TRANSIENT_ACTIVE ), 'ERROR' );
			}
			$common_helper = new Mo2f_Common_Helper();
			$mo2f_message  = $common_helper->mo2f_check_backupcode_status( $codes, $current_user->ID );
			if ( $mo2f_message ) {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( $mo2f_message ), 'ERROR' );
			} else {
				update_user_meta( $current_user->ID, 'mo_backup_code_generated', 1 );
				update_user_meta( $current_user->ID, 'mo_backup_code_downloaded', 1 );
				set_transient( 'mo2f_backupcode_generated' . $current_user->ID, 1, 30 );
				MO2f_Utility::mo2f_download_backup_codes( $current_user->ID, $codes );
			}

		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_login_settings_ajax() {
			if ( ! check_ajax_referer( 'mo2f-login-settings-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			}
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			$common_helper                   = new Mo2f_Common_Helper();
			$common_helper->mo2f_ilvn();
			switch ( $option ) {
				case 'mo2f_enable2FA_save_option':
					$this->mo2f_enable2fa_save_settings( $_POST );
					break;
				case 'mo2f_graceperiod_save_option':
					$this->mo2f_graceperiod_save_option( $_POST );
					break;
				case 'mo2f_enable_graceperiod_disable':
					update_site_option( 'mo2f_grace_period', null );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable2FA_disable':
					update_site_option( 'mo2f_activate_plugin', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_custom_redirect_option':
					$this->mo2f_enable_custom_redirect( $_POST );
					break;
				case 'mo2f_enable_custom_redirect_disable':
					update_site_option( 'mo2f_enable_custom_redirect', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_disable_inline_2fa_option':
					$this->mo2f_disable_inline_2fa( $_POST );
					break;
				case 'mo2f_mfa_login_option':
					$this->mo2f_mfa_login( $_POST );
					break;
				case 'mo2f_new_ip_login_notification':
					$this->mo2f_new_ip_login_notification( $_POST );
					break;
				case 'mo2f_enable_shortcodes_option':
					$this->mo2f_enable_shortcodes( $_POST );
					break;
				case 'mo2f_enable_backup_methods':
					$this->mo2f_enable_backup_methods( $_POST );
					break;
				case 'mo2f_enable_backup_methods_disable':
					update_site_option( 'mo2f_enable_backup_methods', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_new_release_nofify':
					$this->mo2f_new_release_nofify( $_POST );
					break;
				case 'mo2f_save_custom_registration_form_settings':
					do_action( 'mo2f_basic_plan_settings_action', 'mo2f_save_custom_registration_form_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_select_methods_for_users_disable':
					update_site_option( 'mo2f_select_methods_for_users', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_save_login_form_settings':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_save_login_form_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_enable_disable_login_form':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_enable_disable_login_form', $_POST );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_save_selected_2fa_methods':
					do_action( 'mo2f_basic_plan_settings_action', 'save_selected_2fa_methods', $_POST );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_custom_registration_form':
					do_action( 'mo2f_basic_plan_settings_action', 'mo2f_enable_custom_registration_form_shortcodes', $_POST );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_debug_log_disable':
					update_site_option( 'mo2f_enable_debug_log', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_debug_log_enable':
					update_site_option( 'mo2f_enable_debug_log', 1 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
			}
		}

		/**
		 * Enable 2FA save settings.
		 *
		 * @param array $post $_POST data.
		 * @return void
		 */
		public function mo2f_enable2fa_save_settings( $post ) {
			$enable_2fa_settings = array(
				'mo2f_activate_plugin' => isset( $post['mo2f_enable_2fa_settings'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_2fa_settings'] ) ) : false,
				'enabledrole'          => isset( $post['enabledrole'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post['enabledrole'] ) ) : array(),
			);
			foreach ( $enable_2fa_settings as $option_to_be_updated => $value ) {
				if ( 'enabledrole' === $option_to_be_updated ) {
					global $wp_roles;
					foreach ( $wp_roles->role_names as $id => $name ) {
						update_site_option( 'mo2fa_' . $id, 0 );
					}
					foreach ( $value as $role ) {
						update_site_option( $role, 1 );
					}
				} else {
					update_site_option( $option_to_be_updated, $value );
				}
			}
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}


		/**
		 * Saves Grace period settings
		 *
		 * @param array $post $_POST data.
		 * @return void
		 */
		public function mo2f_graceperiod_save_option( $post ) {
			$enable_2fa_settings = array(
				'mo2f_grace_period'       => isset( $post['mo2f_enable_graceperiod_settings'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_graceperiod_settings'] ) ) : null,
				'mo2f_grace_period_value' => isset( $post['mo2f_graceperiod_value'] ) ? floor( sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_value'] ) ) ) : 1,
				'mo2f_grace_period_type'  => isset( $post['mo2f_graceperiod_type'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_type'] ) ) : 'hours',
				'mo2f_graceperiod_action' => isset( $post['mo2f_graceperiod_action'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_action'] ) ) : 'enforce_2fa',
			);
			if ( 1 > (int) $enable_2fa_settings['mo2f_grace_period_value'] ) {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::EXPECTED_GRACE_PERIOD_VALUE ) );
			}
			foreach ( $enable_2fa_settings as $option_to_be_updated => $value ) {
				update_site_option( $option_to_be_updated, $value );
			}
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Enable Custom Redirect
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_custom_redirect( $post ) {
			$enable_custom_url_settings = array(
				'mo2f_enable_custom_redirect' => isset( $post['mo2f_enable_custom_redirect'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_custom_redirect'] ) ) : false,
				'mo2f_redirect_url_for_users' => isset( $post['mo2f_redirect_url_for_users'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_redirect_url_for_users'] ) ) : 'redirect_all',
				'mo2f_custom_redirect_url'    => isset( $post['mo2f_custom_redirect_url'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_custom_redirect_url'] ) ) : '',
			);
			foreach ( $enable_custom_url_settings as $option_to_be_updated => $value ) {
				if ( 'mo2f_custom_redirect_url' === $option_to_be_updated ) {
					$common_helper = new Mo2f_Common_Helper();
					$is_valid_url  = $common_helper->mo2f_check_url_validation( $value );
					if ( $is_valid_url ) {
						update_option( $option_to_be_updated, $value );
					} else {
						wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALIDE_REDIRECTION_URL ) );
					}
				} else {
					update_site_option( $option_to_be_updated, $value );
				}
			}
			do_action( 'mo2f_enterprise_plan_settings_action', 'save_custom_redirection_urls', $post );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );

		}

		/**
		 * Disable Inline 2FA
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_disable_inline_2fa( $post ) {
			$mo2f_disable_inline_2fa = isset( $post['mo2f_disable_inline_2fa'] ) ? ( 'true' === sanitize_text_field( wp_unslash( $post['mo2f_disable_inline_2fa'] ) ) ? 1 : null ) : null;
			update_site_option( 'mo2f_disable_inline_registration', $mo2f_disable_inline_2fa );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Enable MFA
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_mfa_login( $post ) {
			$mo2f_mfa_login = isset( $post['mo2f_mfa_login'] ) ? ( 'true' === sanitize_text_field( wp_unslash( $post['mo2f_mfa_login'] ) ) ) : false;
			update_site_option( 'mo2f_multi_factor_authentication', $mo2f_mfa_login );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Enable shortcodes
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_shortcodes( $post ) {
			$mo2f_enable_shortcodes = isset( $post['mo2f_enable_shortcodes'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_shortcodes'] ) ) : 0;
			update_site_option( 'mo2f_enable_shortcodes', $mo2f_enable_shortcodes );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}


		/**
		 * Enable Backup methods
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_backup_methods( $post ) {
			$enable_backup_login    = isset( $post['mo2f_enable_backup_login'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_backup_login'] ) ) : false;
			$enabled_backup_methods = isset( $post['mo2f_enabled_backup_methods'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post['mo2f_enabled_backup_methods'] ) ) : array();
			$enabled_backup_methods = apply_filters( 'mo2f_basic_plan_settings_filter', array_diff( $enabled_backup_methods, array( 'backup_kba' ) ), 'get_backup_methods', $post );
			update_site_option( 'mo2f_enable_backup_methods', $enable_backup_login );
			update_site_option( 'mo2f_enabled_backup_methods', $enabled_backup_methods );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Used to save admin email.
		 *
		 * @param object $post contails admin email address.
		 * @return void
		 */
		public function mo2f_new_release_nofify( $post ) {
			$email                   = isset( $post['mo2f_email'] ) ? sanitize_email( wp_unslash( $post['mo2f_email'] ) ) : '';
			$mo2f_all_mail_noyifying = isset( $post['is_notification_enabled'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['is_notification_enabled'] ) ) : false;
			update_site_option( 'mo2f_mail_notify_new_release', $mo2f_all_mail_noyifying );
			if ( is_email( $email ) ) {
				update_site_option( 'admin_email_address', $email );
				wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
			} else {
				wp_send_json_error( MoWpnsMessages::lang_translate( MoWpnsMessages::INVALID_EMAIL ) );
			}
		}

		/**
		 * Handles new ip detect notifications settings.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_new_ip_login_notification( $post ) {
			$mo2f_mail_notifying_i_p = isset( $post['is_notification_enabled'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['is_notification_enabled'] ) ) : false;
			update_site_option( 'mo_wpns_enable_unusual_activity_email_to_user', $mo2f_mail_notifying_i_p );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}
	}
	new Mo2f_2fa_Settings_Handler();
}
