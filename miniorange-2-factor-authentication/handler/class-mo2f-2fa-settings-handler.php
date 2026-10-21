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
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_2fa_Settings_Handler' ) ) {

	/**
	 * Class Mo2f_2fa_Settings_Handler
	 */
	class Mo2f_2fa_Settings_Handler {

		use Instance;

		/**
		 * Upper bounds for a grace period value -- block mistyped huge numbers (e.g.
		 * an extra digit) that would otherwise silently bypass 2FA enforcement for
		 * years with no indication anything is wrong. Hours gets 365 days' worth so
		 * both units represent the same real-world maximum duration.
		 */
		const MAX_GRACE_PERIOD_DAYS  = 365;
		const MAX_GRACE_PERIOD_HOURS = 8760;

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
			$current_user = wp_get_current_user();
			$show_message  = new MoWpnsMessages();
			if ( get_transient( 'mo2f_backupcode_generated' . $current_user->ID ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::TRANSIENT_ACTIVE ), 'ERROR' );
				return;
			}
			$backup_code_handler = new Mo2f_Backup_Codes();
			$codes                = $backup_code_handler->mo2f_generate_local_backup_codes( $current_user->ID );
			$codes                = apply_filters( 'mo2f_basic_plan_settings_filter', $codes, 'generate_backup_codes', array( 'user_id' => $current_user->ID ) );
			update_user_meta( $current_user->ID, 'mo_backup_code_generated', 1 );
			update_user_meta( $current_user->ID, 'mo_backup_code_downloaded', 1 );
			set_transient( 'mo2f_backupcode_generated' . $current_user->ID, 1, 30 );
			MO2f_Utility::mo2f_download_backup_codes( $current_user->ID, $codes );
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_login_settings_ajax() {
			$required_cap = is_multisite() ? 'manage_network_options' : 'manage_options';
			if ( ! check_ajax_referer( 'mo2f-login-settings-ajax-nonce', 'nonce', false ) || ! current_user_can( $required_cap ) ) {
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
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable2FA_disable':
					update_site_option( 'mo2f_activate_plugin', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_custom_redirect_option':
					$this->mo2f_enable_custom_redirect( $_POST );
					break;
				case 'mo2f_enable_custom_redirect_disable':
					update_site_option( 'mo2f_enable_custom_redirect', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_digest_email_option':
					$this->mo2f_enable_digest_email_save_settings( $_POST );
					break;
				case 'mo2f_enable_digest_email_disable':
					update_site_option( 'mo2f_enable_digest_email', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_captcha_option':
					$this->mo2f_enable_captcha_save_settings( $_POST );
					break;
				case 'mo2f_enable_captcha_disable':
					update_site_option( 'mo2f_enable_captcha', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_disable_inline_2fa_option':
					$this->mo2f_disable_inline_2fa( $_POST );
					break;
				case 'mo2f_magic_link_login_save_option':
					$this->mo2f_magic_link_login_save_option( $_POST );
					break;
				case 'mo2f_enable_magic_link_login_disable':
					update_site_option( 'mo2f_enable_magic_link_login', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_trusted_device_option':
					$this->mo2f_enable_trusted_device( $_POST );
					break;
				case 'mo2f_enable_2fa_change_alerts_option':
					$this->mo2f_enable_2fa_change_alerts( $_POST );
					break;
				case 'mo2f_enable_2fa_reminder_emails_option':
					$this->mo2f_enable_2fa_reminder_emails( $_POST );
					break;
				case 'mo2f_mfa_login_option':
					$this->mo2f_mfa_login( $_POST );
					break;
				case 'mo2f_new_ip_login_notification':
					$this->mo2f_new_ip_login_notification( $_POST );
					break;
				case 'mo2f_enable_xmlrpc_option':
					$this->mo2f_enable_xmlrpc_protection( $_POST );
					break;
				case 'mo2f_enable_shortcodes_option':
					$this->mo2f_enable_shortcodes( $_POST );
					break;
				case 'mo2f_enable_backup_methods':
					$this->mo2f_enable_backup_methods( $_POST );
					break;
				case 'mo2f_enable_backup_methods_disable':
					update_site_option( 'mo2f_enable_backup_methods', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_new_release_nofify':
					$this->mo2f_new_release_nofify( $_POST );
					break;
				case 'mo2f_save_custom_registration_form_settings':
					do_action( 'mo2f_basic_plan_settings_action', 'mo2f_save_custom_registration_form_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_select_methods_for_users_disable':
					update_site_option( 'mo2f_select_methods_for_users', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_save_login_form_settings':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_save_login_form_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_enable_disable_login_form':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_enable_disable_login_form', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_save_selected_2fa_methods':
					do_action( 'mo2f_basic_plan_settings_action', 'save_selected_2fa_methods', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_custom_registration_form':
					do_action( 'mo2f_basic_plan_settings_action', 'mo2f_enable_custom_registration_form_shortcodes', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_debug_log_disable':
					update_site_option( 'mo2f_enable_debug_log', 0 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_debug_log_enable':
					update_site_option( 'mo2f_enable_debug_log', 1 );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
					break;
				case 'mo2f_enable_page_protection':
					do_action( 'mo2f_page_protection_addon_settings_action', 'mo2f_enable_page_protection', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
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
			$bypass_on = false;
			if ( isset( $post['mo2f_admin_login_bypass_2fa'] ) ) {
				$raw_bypass = wp_unslash( $post['mo2f_admin_login_bypass_2fa'] );
				if ( is_scalar( $raw_bypass ) ) {
					$bypass_str = strtolower( sanitize_text_field( (string) $raw_bypass ) );
					$bypass_on  = in_array( $bypass_str, array( 'true', '1', 'yes', 'on' ), true );
				}
			}
			update_site_option( 'mo2f_admin_login_bypass_2fa', $bypass_on ? 1 : 0 );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}


		/**
		 * Saves Grace period settings
		 *
		 * @param array $post $_POST data.
		 * @return void
		 */
		public function mo2f_graceperiod_save_option( $post ) {
			$raw_graceperiod_value = isset( $post['mo2f_graceperiod_value'] ) ? trim( sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_value'] ) ) ) : '1';
			if ( '' !== $raw_graceperiod_value && ! $this->mo2f_is_whole_number( $raw_graceperiod_value ) ) {
				// floor()/(int) below would otherwise silently turn e.g. "3.6" into 3 with no indication to the admin.
				wp_send_json_error( __( 'Please enter a whole number for the grace period value.', 'miniorange-2-factor-authentication' ) );
			}
			$raw_graceperiod_type = isset( $post['mo2f_graceperiod_type'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_type'] ) ) : MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS;
			$max_graceperiod_value = MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS === $raw_graceperiod_type ? self::MAX_GRACE_PERIOD_HOURS : self::MAX_GRACE_PERIOD_DAYS;
			if ( (float) $raw_graceperiod_value > $max_graceperiod_value ) {
				/* translators: %d: maximum allowed grace period value. */
				wp_send_json_error( sprintf( __( 'Please enter a grace period value of %d or less.', 'miniorange-2-factor-authentication' ), $max_graceperiod_value ) );
			}
			$enable_2fa_settings = array(
				'mo2f_grace_period'       => isset( $post['mo2f_enable_graceperiod_settings'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_graceperiod_settings'] ) ) : null,
				'mo2f_grace_period_value' => isset( $post['mo2f_graceperiod_value'] ) ? floor( sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_value'] ) ) ) : 1,
				'mo2f_grace_period_type'  => isset( $post['mo2f_graceperiod_type'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_type'] ) ) : MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS,
				'mo2f_graceperiod_action' => isset( $post['mo2f_graceperiod_action'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_graceperiod_action'] ) ) : MoWpnsConstants::GRACE_PERIOD_ACTION_ENFORCE_2FA,
			);
			if ( 1 > (int) $enable_2fa_settings['mo2f_grace_period_value'] ) {
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::EXPECTED_GRACE_PERIOD_VALUE ) );
			}
			$role_graceperiod_error = $this->mo2f_validate_role_graceperiod_overrides( $post );
			if ( null !== $role_graceperiod_error ) {
				wp_send_json_error( $role_graceperiod_error );
			}
			foreach ( $enable_2fa_settings as $option_to_be_updated => $value ) {
				update_site_option( $option_to_be_updated, $value );
			}
			$this->mo2f_save_role_graceperiod_overrides( $post );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * True if $value is numeric with no fractional part -- used to reject decimal grace
		 * period input instead of letting floor()/(int) casts silently truncate it to a
		 * different number than what the admin typed.
		 *
		 * @param string $value Raw, trimmed numeric string.
		 * @return bool
		 */
		private function mo2f_is_whole_number( $value ) {
			return is_numeric( $value ) && (float) $value === floor( (float) $value );
		}

		/**
		 * Validates $post['mo2f_role_graceperiod'] before anything is saved. Catches mistakes
		 * that were previously silently ignored instead of erroring, leaving the "Settings
		 * saved" response not reflecting what was actually persisted:
		 * - a role's override checkbox is checked but its value is empty/zero/negative/decimal.
		 * - a value was entered for a role but its override checkbox isn't checked, so the
		 *   value would be discarded without the admin realizing it never took effect.
		 *
		 * @param array $post $_POST data.
		 * @return string|null Error message if any row is invalid, else null.
		 */
		private function mo2f_validate_role_graceperiod_overrides( $post ) {
			if ( ! isset( $post['mo2f_role_graceperiod'] ) || ! is_array( $post['mo2f_role_graceperiod'] ) ) {
				return null;
			}
			global $wp_roles;
			$role_names = $wp_roles ? $wp_roles->role_names : array();
			foreach ( wp_unslash( $post['mo2f_role_graceperiod'] ) as $role_id => $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed above; fields sanitized individually below.
				$role_id = sanitize_text_field( $role_id );
				if ( ! is_array( $row ) || ! isset( $role_names[ $role_id ] ) ) {
					continue;
				}
				$enabled   = isset( $row['enabled'] ) && 'true' === sanitize_text_field( $row['enabled'] );
				$raw_value = isset( $row['value'] ) ? trim( sanitize_text_field( $row['value'] ) ) : '';
				if ( $enabled && '' !== $raw_value && ! $this->mo2f_is_whole_number( $raw_value ) ) {
					/* translators: %s: WordPress role name. */
					return sprintf( __( 'Please enter a whole number for the %s role grace period value.', 'miniorange-2-factor-authentication' ), $role_names[ $role_id ] );
				}
				if ( $enabled && 1 > (int) $raw_value ) {
					/* translators: %s: WordPress role name. */
					return sprintf( __( 'Please enter a grace period value greater than 0 for the %s role.', 'miniorange-2-factor-authentication' ), $role_names[ $role_id ] );
				}
				$role_max_value = isset( $row['type'] ) && MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS === sanitize_text_field( $row['type'] ) ? self::MAX_GRACE_PERIOD_HOURS : self::MAX_GRACE_PERIOD_DAYS;
				if ( $enabled && (float) $raw_value > $role_max_value ) {
					/* translators: 1: WordPress role name, 2: maximum allowed grace period value. */
					return sprintf( __( 'Please enter a grace period value of %2$d or less for the %1$s role.', 'miniorange-2-factor-authentication' ), $role_names[ $role_id ], $role_max_value );
				}
				if ( ! $enabled && '' !== $raw_value ) {
					/* translators: %s: WordPress role name. */
					return sprintf( __( 'A grace period value was entered for the %s role, but its checkbox isn\'t checked, so it won\'t be saved. Check the box to apply it, or clear the value.', 'miniorange-2-factor-authentication' ), $role_names[ $role_id ] );
				}
			}
			return null;
		}

		/**
		 * Saves per-role grace period overrides from $post['mo2f_role_graceperiod'].
		 *
		 * @param array $post $_POST data.
		 * @return void
		 */
		private function mo2f_save_role_graceperiod_overrides( $post ) {
			if ( ! isset( $post['mo2f_role_graceperiod'] ) || ! is_array( $post['mo2f_role_graceperiod'] ) ) {
				return;
			}
			global $wp_roles;
			$valid_roles = $wp_roles ? array_keys( $wp_roles->role_names ) : array();
			$overrides   = array();
			foreach ( wp_unslash( $post['mo2f_role_graceperiod'] ) as $role_id => $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed above; each field is sanitized individually below.
				$role_id = sanitize_text_field( $role_id );
				if ( ! in_array( $role_id, $valid_roles, true ) || ! is_array( $row ) ) {
					continue;
				}
				$enabled = isset( $row['enabled'] ) && 'true' === sanitize_text_field( $row['enabled'] );
				$value   = isset( $row['value'] ) ? (int) $row['value'] : 0;
				$type    = isset( $row['type'] ) && MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS === sanitize_text_field( $row['type'] ) ? MoWpnsConstants::GRACE_PERIOD_UNIT_HOURS : MoWpnsConstants::GRACE_PERIOD_UNIT_DAYS;
				if ( $enabled && $value > 0 ) {
					$override = array(
						'value' => $value,
						'type'  => $type,
					);
					if ( isset( $row['action'] ) && MoWpnsConstants::GRACE_PERIOD_ACTION_BLOCK === sanitize_text_field( $row['action'] ) ) {
						$override['action'] = MoWpnsConstants::GRACE_PERIOD_ACTION_BLOCK;
					} elseif ( isset( $row['action'] ) && MoWpnsConstants::GRACE_PERIOD_ACTION_ENFORCE_2FA === sanitize_text_field( $row['action'] ) ) {
						$override['action'] = MoWpnsConstants::GRACE_PERIOD_ACTION_ENFORCE_2FA;
					}
					$overrides[ $role_id ] = $override;
				}
			}
			update_site_option( 'mo2f_role_grace_period_overrides', $overrides );
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
						wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::INVALIDE_REDIRECTION_URL ) );
					}
				} else {
					update_site_option( $option_to_be_updated, $value );
				}
			}
			do_action( 'mo2f_enterprise_plan_settings_action', 'save_custom_redirection_urls', $post );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the "Activity Digest Email" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_digest_email_save_settings( $post ) {
			$enabled   = isset( $post['mo2f_enable_digest_email'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_digest_email'] ) ) : false;
			$frequency = isset( $post['mo2f_digest_email_frequency'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_digest_email_frequency'] ) ) : 'weekly';
			if ( ! in_array( $frequency, array( 'daily', 'weekly' ), true ) ) {
				$frequency = 'weekly';
			}
			$raw_recipients = isset( $post['mo2f_digest_email_recipients'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_digest_email_recipients'] ) ) : '';
			$recipients     = array_filter( array_map( 'trim', explode( ',', $raw_recipients ) ) );
			foreach ( $recipients as $recipient ) {
				if ( ! is_email( $recipient ) ) {
					/* translators: %s: the invalid recipient address as typed. */
					wp_send_json_error( sprintf( __( '"%s" is not a valid email address.', 'miniorange-2-factor-authentication' ), $recipient ) );
				}
			}
			update_site_option( 'mo2f_enable_digest_email', $enabled );
			update_site_option( 'mo2f_digest_email_frequency', $frequency );
			update_site_option( 'mo2f_digest_email_recipients', implode( ', ', $recipients ) );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the Email Magic Link Login setting: whether it is enabled, and
		 * whether it runs alongside password login ('optional') or replaces it
		 * ('mandatory').
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_magic_link_login_save_option( $post ) {
			$mode = isset( $post['mo2f_magic_link_login_mode'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_magic_link_login_mode'] ) ) : 'optional';
			if ( ! in_array( $mode, array( 'optional', 'mandatory' ), true ) ) {
				$mode = 'optional';
			}
			update_site_option( 'mo2f_enable_magic_link_login', isset( $post['mo2f_enable_magic_link_login'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_magic_link_login'] ) ) : false );
			update_site_option( 'mo2f_magic_link_login_mode', $mode );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
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
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the free-tier "Trust This Device After Successful 2FA" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_trusted_device( $post ) {
			$enabled = isset( $post['mo2f_enable_trusted_device'] ) ? ( 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_trusted_device'] ) ) ? 1 : null ) : null;
			update_site_option( 'mo2f_enable_trusted_device', $enabled );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the "Security Alert on 2FA Change" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_2fa_change_alerts( $post ) {
			$enabled = isset( $post['mo2f_enable_2fa_change_alerts'] ) ? ( 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_2fa_change_alerts'] ) ) ? 1 : null ) : null;
			update_site_option( 'mo2f_enable_2fa_change_alerts', $enabled );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the "Remind Users to Set Up 2FA" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_2fa_reminder_emails( $post ) {
			$enabled = isset( $post['mo2f_enable_2fa_reminder_emails'] ) ? ( 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_2fa_reminder_emails'] ) ) ? 1 : null ) : null;
			update_site_option( 'mo2f_enable_2fa_reminder_emails', $enabled );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
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
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
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
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}


		/**
		 * Enable Backup methods
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_backup_methods( $post ) {
			global $wp_roles;
			$enable_backup_login    = isset( $post['mo2f_enable_backup_login'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_backup_login'] ) ) : false;
			$enabled_backup_methods = isset( $post['mo2f_enabled_backup_methods'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post['mo2f_enabled_backup_methods'] ) ) : array();
			$enabled_backup_methods = apply_filters( 'mo2f_basic_plan_settings_filter', array_diff( $enabled_backup_methods, array( 'backup_kba' ) ), 'get_backup_methods', $post );
			update_site_option( 'mo2f_enable_backup_methods', $enable_backup_login );
			update_site_option( 'mo2f_enabled_backup_methods', $enabled_backup_methods );
			if ( in_array( 'mo2f_reconfig_link_show', $enabled_backup_methods, true ) ) {
				$allowed_roles  = isset( $post['mo2f_reconfig_link_allowed_roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $post['mo2f_reconfig_link_allowed_roles'] ) ) : array();
				$all_roles      = $wp_roles ? array_keys( $wp_roles->role_names ) : array();
				$excluded_roles = array_values( array_diff( $all_roles, $allowed_roles ) );
				update_site_option( 'mo2f_reconfig_link_excluded_roles', $excluded_roles );
				$admin_alert_enabled = isset( $post['mo2f_enable_self_service_reset_admin_alert'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_self_service_reset_admin_alert'] ) ) : false;
				update_site_option( 'mo2f_enable_self_service_reset_admin_alert', $admin_alert_enabled );
			}
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
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
				wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
			} else {
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::INVALID_EMAIL ) );
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
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the "Add CAPTCHA to the Login Screen" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_captcha_save_settings( $post ) {
			$enabled  = isset( $post['mo2f_enable_captcha'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_captcha'] ) ) : false;
			$provider = isset( $post['mo2f_captcha_provider'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_captcha_provider'] ) ) : 'recaptcha';
			if ( ! in_array( $provider, array( 'recaptcha', 'turnstile' ), true ) ) {
				$provider = 'recaptcha';
			}
			$site_key   = isset( $post['mo2f_captcha_site_key'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_captcha_site_key'] ) ) : '';
			$secret_key = isset( $post['mo2f_captcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_captcha_secret_key'] ) ) : '';
			// Enabling with a missing key would silently fail open (no CAPTCHA actually enforced) -- stop that before it gives a false sense of security.
			if ( $enabled && ( '' === $site_key || '' === $secret_key ) ) {
				wp_send_json_error( __( 'Please enter both a Site Key and a Secret Key before enabling the CAPTCHA.', 'miniorange-2-factor-authentication' ) );
			}
			update_site_option( 'mo2f_enable_captcha', $enabled );
			update_site_option( 'mo2f_captcha_provider', $provider );
			update_site_option( 'mo2f_captcha_site_key', $site_key );
			update_site_option( 'mo2f_captcha_secret_key', $secret_key );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}

		/**
		 * Saves the "Disable XML-RPC" setting.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_enable_xmlrpc_protection( $post ) {
			$enabled = isset( $post['mo2f_enable_xmlrpc'] ) ? 'true' === sanitize_text_field( wp_unslash( $post['mo2f_enable_xmlrpc'] ) ) : false;
			update_site_option( 'mo2f_enable_xmlrpc', $enabled );
			wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SETTINGS_SAVED_SUCCESSFULLY ) );
		}
	}
	new Mo2f_2fa_Settings_Handler();
}
