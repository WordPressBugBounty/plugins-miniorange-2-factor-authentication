<?php
/**
 * File contains function related to login flow.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LoginHandler' ) ) {
	/**
	 * Class LoginHandler
	 */
	class LoginHandler {

		use Instance;

		/**
		 * Class LoginHandler constructor
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'mo_wpns_init' ) );
			if ( get_site_option( 'mo2f_restrict_restAPI' ) ) {
				add_action( 'rest_api_init', array( $this, 'mo_block_rest_api' ) );
			}

			add_action( 'wp_login', array( $this, 'mo_wpns_login_success' ) );
			add_action( 'wp_login_failed', array( $this, 'mo_wpns_login_failed' ) );

			if ( get_site_option( 'mo_wpns_activate_recaptcha_for_woocommerce_registration' ) ) {
				add_action( 'woocommerce_register_post', array( $this, 'wooc_validate_user_captcha_register' ), 1, 3 );
			}
		}

		/**
		 * Blocks the rest api and show 403 error screen.
		 *
		 * @return void
		 */
		public function mo_block_rest_api() {
			if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/wp/v2/users' ) ) {
				include_once 'mo-block.html';
				exit;
			}
		}

		/**
		 * Initiates network security flow.
		 *
		 * @return void
		 */
		public function mo_wpns_init() {
			add_action( 'show_user_profile', array( $this, 'twofa_on_user_profile' ), 10, 3 );
			add_action( 'edit_user_profile', array( $this, 'twofa_on_user_profile' ), 10, 3 );
			add_action( 'personal_options_update', array( $this, 'user_two_factor_options_update' ), 10, 3 );
			add_action( 'edit_user_profile_update', array( $this, 'user_two_factor_options_update' ), 10, 3 );
			global $mo2f_mo_wpns_utility, $mo2f_dir_name;
			$w_a_f_enabled = get_site_option( 'WAFEnabled' );
			$waflevel      = get_site_option( 'WAF' );
			if ( 1 === $w_a_f_enabled ) {
				if ( 'PluginLevel' === $waflevel ) {
					if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'WAF' . DIRECTORY_SEPARATOR . 'mo-waf-plugin.php' ) ) {
						include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'WAF' . DIRECTORY_SEPARATOR . 'mo-waf-plugin.php';
					}
				}
			}

			$user_ip        = $mo2f_mo_wpns_utility->get_client_ip();
			$user_ip        = sanitize_text_field( $user_ip );
			$mo_wpns_config = new MoWpnsHandler();
			$is_whitelisted = $mo_wpns_config->mo2f_is_whitelisted( $user_ip );
			$is_ip_blocked  = false;
			if ( ! $is_whitelisted ) {
				$is_ip_blocked = $mo_wpns_config->is_ip_blocked_in_anyway( $user_ip );
			}
			if ( $is_ip_blocked ) {
				include_once 'mo-block.html';
				exit;
			}
		}

		/**
		 * Includes user-profile-2fa.php file if exists.
		 *
		 * @param object $user User object.
		 * @return void
		 */
		public function twofa_on_user_profile( $user ) {
			global $mo2f_dir_name;
			$main_handler  = new Mo2f_Main_Handler();
			$twofa_enabled = $main_handler->mo2f_check_if_twofa_is_enabled( $user );
			if ( $twofa_enabled && file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa.php' ) ) {
				include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa.php';
			}
		}

		/**
		 * Includes user-profile-2fa-update.php file if exists.
		 *
		 * @param integer $user_id User Id.
		 * @return void
		 */
		public function user_two_factor_options_update( $user_id ) {
			global $mo2f_dir_name;
			if ( file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa-update.php' ) ) {
				include_once $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'user-profile-2fa-update.php';
			}
		}

		/**
		 * Adds transaction report to network transaction table and updates the users with password option in the options table.
		 *
		 * @param string $username Username of the user.
		 * @return mixed
		 */
		public function mo_wpns_login_success( $username ) {
			global $mo2f_mo_wpns_utility, $mo2fdb_queries;
			$mo_wpns_config = new MoWpnsHandler();
			$user_ip        = $mo2f_mo_wpns_utility->get_client_ip();
			$mo_wpns_config->move_failed_transactions_to_past_failed( $user_ip );
			$user              = get_user_by( 'login', $username );
			$user_roles        = get_userdata( $user->ID )->roles;
			$user_role_enabled = 0;
			foreach ( $user_roles as $user_role ) {
				if ( get_site_option( 'mo2fa_' . $user_role ) ) {
					$user_role_enabled = 1;
					break;
				}
			}
			$is_customer_registered = 'SUCCESS' === $mo2fdb_queries->mo2f_get_user_detail( 'user_registration_with_miniorange', $user->ID );
			if ( $user_role_enabled && $is_customer_registered && get_site_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) ) {
				$mo2f_mo_wpns_utility->send_notification_to_user_for_unusual_activities( $username, $user_ip, MoWpnsConstants::LOGGED_IN_FROM_NEW_IP );
			}
			if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
				global $mo2f_wpns_db_queries;
				$mo2f_wpns_db_queries->mo2f_insert_transaction_audit( $user_ip, $username, MoWpnsConstants::LOGIN_TRANSACTION, MoWpnsConstants::SUCCESS );
			}
		}

		/**
		 * Adds the failed login entry in network transactions table and sends the notification regarding the same on administrator's email ID.
		 *
		 * @param string $username Username of the user.
		 * @return void
		 */
		public function mo_wpns_login_failed( $username ) {
			global $mo2f_mo_wpns_utility, $mo2fdb_queries;
			$user_ip = $mo2f_mo_wpns_utility->get_client_ip();
			if ( empty( $user_ip ) || empty( $username ) || ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_enable_brute_force', 'site_option' ) ) {
				return;
			}
			$mo_wpns_config = new MoWpnsHandler();
			$is_whitelisted = $mo_wpns_config->mo2f_is_whitelisted( $user_ip );
			if ( 'true' === get_site_option( 'mo2f_enable_login_report' ) ) {
				global $mo2f_wpns_db_queries;
				$mo2f_wpns_db_queries->mo2f_insert_transaction_audit( $user_ip, $username, MoWpnsConstants::LOGIN_TRANSACTION, MoWpnsConstants::FAILED );
			}
			if ( ! $is_whitelisted ) {
				$user              = get_user_by( 'login', $username );
				$user_roles        = get_userdata( $user->ID )->roles;
				$user_role_enabled = 0;
				foreach ( $user_roles as $user_role ) {
					if ( get_site_option( 'mo2fa_' . $user_role ) ) {
						$user_role_enabled = 1;
						break;
					}
				}
				$is_customer_registered = 'SUCCESS' === $mo2fdb_queries->mo2f_get_user_detail( 'user_registration_with_miniorange', $user->ID );
				if ( get_site_option( 'mo_wpns_enable_unusual_activity_email_to_user' ) && $user_role_enabled && $is_customer_registered ) {
					$mo2f_mo_wpns_utility->send_notification_to_user_for_unusual_activities( $username, $user_ip, MoWpnsConstants::FAILED_LOGIN_ATTEMPTS_FROM_NEW_IP );
				}
			}
		}
	}
}
