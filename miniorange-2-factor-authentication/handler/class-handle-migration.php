<?php
/**
 * File contains migration handling.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Handle_Migration' ) ) {
	/**
	 * Class Handle_Migration
	 */
	class Handle_Migration {

		use Instance;

		/**
		 * Handle_Migration class constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo2f_handle_migration' ), 10 );
		}

		/**
		 * Handle different settings migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_migration() {
			if ( ! get_site_option( 'mo2f_handle_migration_status' ) ) {
				update_site_option( 'mo2f_handle_migration_status', 1 );
				$this->mo2f_handle_enable_2fa_migration();
				$this->mo2f_handle_grace_period_migration();
				$this->mo2f_handle_mfa_migration();
				$this->mo2f_handle_new_release_email_migration();
				$this->mo2f_handle_backup_code_enable();
				$this->mo2f_handle_session_management_migration();
			}
		}

		/**
		 * Handle Session Management migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_session_management_migration() {
			$session_type = get_site_option( 'mo2f_session_allowed_type', 1 );
			update_site_option( 'mo2f_session_allowed_type', $session_type ? 'allow_access' : 'deny_access' );
		}

		/**
		 * Handle enable 2fa migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_enable_2fa_migration() {
			if ( get_site_option( 'mo2f_activate_plugin' ) === false ) {
				update_site_option( 'mo2f_activate_plugin', 1 );
			}
		}

		/**
		 * Handle grace period migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_grace_period_migration() {
			$grace_period_option = get_site_option( 'mo2f_grace_period' );
			update_site_option( 'mo2f_grace_period', 'on' === $grace_period_option ? 1 : null );
			update_site_option( 'mo2f_graceperiod_action', 'enforce_2fa' );
		}

		/**
		 * Handle mfa migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_mfa_migration() {
			if ( get_site_option( 'mo2f_nonce_enable_configured_methods' ) ) {
				$mfa_option = get_site_option( 'mo2f_nonce_enable_configured_methods' );
				update_site_option( 'mo2f_multi_factor_authentication', $mfa_option );
			}
		}

		/**
		 * Handle new release email migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_new_release_email_migration() {
			$new_release_email_option = get_site_option( 'mo2f_mail_notify_new_release' );
			update_site_option( 'mo2f_mail_notify_new_release', 'on' === $new_release_email_option ? 1 : null );
		}

		/**
		 * Handle backup code enable migration.
		 *
		 * @return void
		 */
		public function mo2f_handle_backup_code_enable() {
			add_option( 'mo2f_enable_backup_methods', true );
			add_option( 'mo2f_enabled_backup_methods', array( 'mo2f_reconfig_link_show', 'mo2f_back_up_codes' ) );
		}
	}
}
