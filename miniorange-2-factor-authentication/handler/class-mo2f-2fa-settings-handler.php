<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/twofactor/loginsettings/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsMessages;

if ( ! class_exists( 'Mo2f_2fa_Settings_Handler' ) ) {

	/**
	 * Class Mo2f_2fa_Settings_Handler
	 */
	class Mo2f_2fa_Settings_Handler {

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
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_login_settings_ajax() {
			if ( ! check_ajax_referer( 'mo2f-login-settings-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			}
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
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
					update_option( 'mo2f_enable_custom_redirect', 0 );
					wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_disable_inline_2fa_option':
					$this->mo2f_disable_inline_2fa( $_POST );
					break;
				case 'mo2f_mfa_login_option':
					$this->mo2f_mfa_login( $_POST );
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
				case 'mo2f_save_custom_form_settings':
					$this->mo2f_save_custom_form_settings( $_POST );
					break;
				case 'mo2f_new_release_nofify':
					$this->mo2f_new_release_nofify( $_POST );
					break;
				case 'waf_settings_IP_mail_form':
					$this->waf_settings_i_p_mail_form( $_POST );
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
						update_option( 'mo2fa_' . $id, 0 );
					}
					foreach ( $value as $role ) {
						update_option( $role, 1 );
					}
				} else {
					update_option( $option_to_be_updated, $value );
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
				update_option( $option_to_be_updated, $value );
			}
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
			update_option( 'mo2f_enable_shortcodes', $mo2f_enable_shortcodes );
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
				update_option( 'admin_email_address', $email );
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
		public function waf_settings_i_p_mail_form( $post ) {
			$mo2f_mail_notifying_i_p = isset( $post['is_notification_enabled'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['is_notification_enabled'] ) ) : false;
			update_site_option( 'mo_wpns_enable_unusual_activity_email_to_user', $mo2f_mail_notifying_i_p );
			wp_send_json_success( MoWpnsMessages::lang_translate( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves settings for custom login forms.
		 *
		 * @param array $post Post data.
		 * @return mixed
		 */
		public function mo2f_save_custom_form_settings( $post ) {
			$custom_form = false;
			if ( isset( $post['enableShortcode'] ) && 'true' !== sanitize_text_field( wp_unslash( $post['enableShortcode'] ) ) ) {
				update_site_option( 'enable_form_shortcode', sanitize_text_field( wp_unslash( $post['enableShortcode'] ) ) );
				wp_send_json_success();
			}
			if ( isset( $post['submit_selector'] ) &&
			isset( $post['email_selector'] ) &&
			isset( $post['authType'] ) &&
			isset( $post['customForm'] ) &&
			isset( $post['form_selector'] ) &&

			sanitize_text_field( wp_unslash( $post['submit_selector'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $post['email_selector'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $post['customForm'] ) ) !== '' &&
			sanitize_text_field( wp_unslash( $post['form_selector'] ) ) !== '' ) {
				$submit_selector  = sanitize_text_field( wp_unslash( $post['submit_selector'] ) );
				$form_selector    = sanitize_text_field( wp_unslash( $post['form_selector'] ) );
				$email_selector   = sanitize_text_field( wp_unslash( $post['email_selector'] ) );
				$phone_selector   = isset( $post['phone_selector'] ) ? sanitize_text_field( wp_unslash( $post['phone_selector'] ) ) : '';
				$auth_type        = sanitize_text_field( wp_unslash( $post['authType'] ) );
				$custom_form      = sanitize_text_field( wp_unslash( $post['customForm'] ) );
				$enable_shortcode = isset( $post['enableShortcode'] ) ? sanitize_text_field( wp_unslash( $post['enableShortcode'] ) ) : '';
				$form_submit      = isset( $post['formSubmit'] ) ? sanitize_text_field( wp_unslash( $post['formSubmit'] ) ) : '';

				switch ( $form_selector ) {
					case '.bbp-login-form':
						update_site_option( 'mo2f_custom_reg_bbpress', true );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
						break;
					case '.woocommerce-form woocommerce-form-register':
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', true );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
						break;
					case '#pmpro_form':
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', false );
						update_site_option( 'mo2f_custom_reg_pmpro', true );
						update_site_option( 'mo2f_activate_plugin', false );
						break;
					default:
						update_site_option( 'mo2f_custom_reg_bbpress', false );
						update_site_option( 'mo2f_custom_reg_wocommerce', false );
						update_site_option( 'mo2f_custom_reg_custom', true );
						update_site_option( 'mo2f_custom_reg_pmpro', false );
				}

				update_site_option( 'mo2f_custom_form_name', $form_selector );
				update_site_option( 'mo2f_custom_email_selector', $email_selector );
				update_site_option( 'mo2f_custom_phone_selector', $phone_selector );
				update_site_option( 'mo2f_custom_submit_selector', $submit_selector );
				update_site_option( 'mo2f_custom_auth_type', $auth_type );
				update_site_option( 'mo2f_form_submit_after_validation', $form_submit );

				update_site_option( 'enable_form_shortcode', $enable_shortcode );
				$saved = true;
			} else {
				$submit_selector = 'NA';
				$form_selector   = 'NA';
				$email_selector  = 'NA';
				$auth_type       = 'NA';
				$saved           = false;
			}
			$return = array(
				'authType'        => $auth_type,
				'submit'          => $submit_selector,
				'emailSelector'   => $email_selector,
				'phone_selector'  => $phone_selector,
				'form'            => $form_selector,
				'saved'           => $saved,
				'customForm'      => $custom_form,
				'formSubmit'      => $form_submit,
				'enableShortcode' => $enable_shortcode,
			);

			return $saved ? wp_send_json_success() : wp_send_json_error( 'fields_empty' );
		}
	}
	new Mo2f_2fa_Settings_Handler();
}
