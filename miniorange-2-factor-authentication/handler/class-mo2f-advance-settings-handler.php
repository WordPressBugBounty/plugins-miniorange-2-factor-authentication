<?php
/**
 * This file contains the ajax request handler.
 *
 * @package miniorange-2-factor-authentication/twofactor/customloginforms/handler
 */

namespace TwoFA\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_Advance_Settings_Handler' ) ) {

	/**
	 * Class Mo2f_Advance_Settings_Handler
	 */
	class Mo2f_Advance_Settings_Handler {

		use Instance;

		/**
		 * Mo2f_Advance_Settings_Handler class custructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_2f_two_factor' ) );
		}
		/**
		 * Function for handling ajax requests.
		 *
		 * @return void
		 */
		public function mo_2f_two_factor() {
			add_action( 'wp_ajax_mo2f_advance_settings_ajax', array( $this, 'mo2f_advance_settings_ajax' ) );
		}

		/**
		 * Calls the function according to the switch case.
		 *
		 * @return void
		 */
		public function mo2f_advance_settings_ajax() {

			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SOMETHING_WENT_WRONG ) );
			}
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_ilvn();
			$GLOBALS['mo2f_is_ajax_request'] = true;
			$option                          = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo_wpns_manual_clear':
					$this->mo_wpns_manual_clear();
					break;
				case 'mo2f_enable_transactions_report':
					$this->mo2f_enable_transactions_report( $_POST );
					break;
				case 'mo2f_passwordless_login_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_passwordless_login_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_save_rba_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_save_rba_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_remove_remembered_device':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_remove_remembered_device', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_save_session_management_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_save_session_management_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_enable_disable_remember_ip':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_enable_disable_remember_ip', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
				case 'mo2f_whatsapp_settings_ajax':
					do_action( 'mo2f_all_inclusive_plan_settings_action', 'mo2f_whatsapp_settings_ajax', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
					break;
			}
		}

		/**
		 * Clears login report.
		 *
		 * @return mixed
		 */
		public function mo_wpns_manual_clear() {
			global $mo2f_wpns_db_queries;
			$mo2f_wpns_db_queries->mo_wpns_clear_login_report();
			wp_send_json_success();
		}


		/**
		 * Enable/disables the login transactions report.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_enable_transactions_report( $post ) {

			$is_transaction_report_enabled = isset( $post['mo2f_enable_transaction_report'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_enable_transaction_report'] ) ) : 0;
			update_site_option( 'mo2f_enable_login_report', $is_transaction_report_enabled );
			wp_send_json( $is_transaction_report_enabled );
		}
	}
	new Mo2f_Advance_Settings_Handler();
}
