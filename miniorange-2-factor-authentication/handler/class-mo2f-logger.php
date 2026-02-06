<?php
/**
 * This file is controller for views/twofa/two-fa.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

namespace TwoFA\Handler;

use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Logger' ) ) {
	/**
	 * Class for log login transactions
	 */
	class Mo2f_Logger {

		use Instance;

		/**
		 * Cunstructor for Mo2f_Logger
		 */
		public function __construct() {
			add_action( 'log_403', array( $this, 'log_403' ) );
			add_action( 'template_redirect', array( $this, 'log_404' ) );
		}

		/**
		 * Log 403.
		 *
		 * @return void
		 */
		public function log_403() {
			global $mo2f_mo_wpns_utility;
			$mo_wpns_config = new MoWpnsHandler();
			$user_ip        = $mo2f_mo_wpns_utility->get_client_ip();
			$user_ip        = sanitize_text_field( $user_ip );
			$url            = $mo2f_mo_wpns_utility->get_current_url();
			$user           = wp_get_current_user();
			$username       = is_user_logged_in() ? $user->user_login : 'GUEST';
			if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
				global $mo2f_wpns_db_queries;
				$mo2f_wpns_db_queries->mo2f_insert_transaction_audit( $user_ip, $username, MoWpnsConstants::ERR_403, MoWpnsConstants::ACCESS_DENIED, $url );
			}
		}

		/**
		 * Log 404.
		 *
		 * @return void
		 */
		public function log_404() {
			global $mo2f_mo_wpns_utility;

			if ( ! is_404() ) {
				return;
			}
			$mo_wpns_config = new MoWpnsHandler();
			$user_ip        = $mo2f_mo_wpns_utility->get_client_ip();
			$user_ip        = sanitize_text_field( $user_ip );
			$url            = $mo2f_mo_wpns_utility->get_current_url();
			$user           = wp_get_current_user();
			$username       = is_user_logged_in() ? $user->user_login : 'GUEST';
			if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
				global $mo2f_wpns_db_queries;
				$mo2f_wpns_db_queries->mo2f_insert_transaction_audit( $user_ip, $username, MoWpnsConstants::ERR_403, MoWpnsConstants::ACCESS_DENIED, $url );
			}
		}
	}
	new Mo2f_Logger();
}
