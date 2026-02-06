<?php
/**
 * File contains 2fa-network security ajax functions.
 *
 * @package miniorange-2-factor-authentication/controllers
 */

use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MocURL;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Wpns_Ajax' ) ) {
	/**
	 * Class Mo2f_Wpns_Ajax
	 */
	class Mo2f_Wpns_Ajax {

		/**
		 * Class Mo2f_Wpns_Ajax constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_login_security_ajax' ) );
			add_action( 'init', array( $this, 'mo2fa_elementor_ajax_fun' ) );
		}

		/**
		 * Contains hooks to call functions.
		 *
		 * @return void
		 */
		public function mo_login_security_ajax() {

			add_action( 'wp_ajax_wpns_login_security', array( $this, 'wpns_login_security' ) );
			add_action( 'wp_ajax_mo2f_ajax', array( $this, 'mo2f_ajax' ) );
			add_action( 'wp_ajax_nopriv_mo2f_ajax', array( $this, 'mo2f_ajax' ) );
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_ajax() {

			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			}
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['mo2f_ajax_option'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_ajax_option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_ajax_login':
					$this->mo2f_ajax_login();
					break;
			}
		}

		/**
		 * Handles the elementor login flow.
		 *
		 * @return void
		 */
		public function mo2fa_elementor_ajax_fun() {
			if ( isset( $_POST['miniorange_elementor_login_nonce'] ) ) {

				if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'miniorange_elementor_login_nonce', false ) ) {
					wp_send_json_error( 'class-wpns-ajax' );

				}
				if ( isset( $_POST['mo2fa_elementor_user_password'] ) && ! empty( $_POST['mo2fa_elementor_user_password'] ) && isset( $_POST['mo2fa_elementor_user_name'] ) ) {
					$info                  = array();
					$info['user_login']    = sanitize_user( wp_unslash( $_POST['mo2fa_elementor_user_name'] ) );
					$info['user_password'] = $_POST['mo2fa_elementor_user_password']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
					$info['remember']      = false;
					$user_signon           = wp_signon( $info, false );
					if ( is_wp_error( $user_signon ) ) {
						wp_send_json_error(
							array(
								'loggedin' => false,
								'message'  => __( 'Wrong username or password.', 'miniorange-2-factor-authentication' ),
							)
						);
					}
				}
			}
		}

		/**
		 * Calls the network security functions according to the switch case.
		 *
		 * @return void
		 */
		public function wpns_login_security() {
			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$option = isset( $_POST['wpns_loginsecurity_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['wpns_loginsecurity_ajax'] ) ) : '';
			switch ( $option ) {

				case 'wpns_all_plans':
					$this->wpns_all_plans();
					break;
				case 'update_plan':
					$this->update_plan();
					break;
			}
		}

		/**
		 * Updates plan name and plan type options in the options table.
		 *
		 * @return void
		 */
		public function update_plan() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$mo2f_all_plannames = isset( $_POST['planname'] ) ? sanitize_text_field( wp_unslash( $_POST['planname'] ) ) : '';
			$mo_2fa_plan_type   = isset( $_POST['planType'] ) ? sanitize_text_field( wp_unslash( $_POST['planType'] ) ) : '';
			update_site_option( 'mo2f_planname', $mo2f_all_plannames );
			if ( 'addon_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', 'addon_plan' );
				update_site_option( 'mo_2fa_addon_plan_type', $mo_2fa_plan_type );
			} elseif ( '2fa_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', '2fa_plan' );
				update_site_option( 'mo_2fa_plan_type', $mo_2fa_plan_type );
			}
		}

		/**
		 * Gets username and password from ajax login form.
		 *
		 * @return void
		 */
		public function mo2f_ajax_login() {
			if ( ! check_ajax_referer( 'miniorange-2-factor-login-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );
			} else {
				$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
				$password = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No need to sanitize password as Strong Passwords contain special symbol.
				apply_filters( 'authenticate', null, $username, $password ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook.
			}
		}

		/**
		 * Updates the plan names in the options table.
		 *
		 * @return void
		 */
		public function wpns_all_plans() {

			if ( ! check_ajax_referer( 'LoginSecurityNonce', 'nonce', false ) ) {
				wp_send_json_error( 'class-wpns-ajax' );

			}
			$mo2f_all_plannames = isset( $_POST['planname'] ) ? sanitize_text_field( wp_unslash( $_POST['planname'] ) ) : '';
			$mo_2fa_plan_type   = isset( $_POST['planType'] ) ? sanitize_text_field( wp_unslash( $_POST['planType'] ) ) : '';
			update_site_option( 'mo2f_planname', $mo2f_all_plannames );
			if ( 'addon_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', 'addon_plan' );
				update_site_option( 'mo_2fa_addon_plan_type', $mo_2fa_plan_type );
				update_site_option( 'mo2f_customer_selected_plan', $mo_2fa_plan_type );
			} elseif ( '2fa_plan' === $mo2f_all_plannames ) {
				update_site_option( 'mo2f_planname', '2fa_plan' );
				update_site_option( 'mo_2fa_plan_type', $mo_2fa_plan_type );
				update_site_option( 'mo2f_customer_selected_plan', $mo_2fa_plan_type );
			}
		}
	}
	new Mo2f_Wpns_Ajax();
}
