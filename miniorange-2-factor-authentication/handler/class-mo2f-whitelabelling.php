<?php
/**
 * File contains user's feedback related functions at the time of deactivation of plugin.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Onprem\MO2f_Utility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Whitelabelling' ) ) {
	/**
	 * Class Mo2f_Whitelabelling
	 */
	class Mo2f_Whitelabelling {

		/**
		 * Mo2f_Whitelabelling class constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo2f_whitelabeling_action' ) );
			add_action( 'wp_ajax_mo2f_white_labelling_ajax', array( $this, 'mo2f_white_labelling_ajax' ) );

		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public function mo2f_white_labelling_ajax() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
			if ( ! wp_verify_nonce( $nonce, 'mo2f-white-labelling-ajax-nonce' ) ) {
				wp_send_json_error();
			}
			$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_google_app_name':
					$this->mo2f_google_app_name( $_POST );
					break;
			}
		}

		/**
		 * Save GAuth App name.
		 *
		 * @param array $post $_POST array.
		 * @return void
		 */
		public function mo2f_google_app_name( $post ) {
			$gauth_appname = isset( $post['mo2f_google_auth_appname'] ) ? sanitize_text_field( wp_unslash( $post['mo2f_google_auth_appname'] ) ) : '';
			update_option( 'mo2f_google_appname', $gauth_appname );
			wp_send_json_success();
		}

		/**
		 * Checks for post option value in the switch case.
		 *
		 * @return mixed
		 */
		public function mo2f_whitelabeling_action() {
			$show_message = new MoWpnsMessages();
			$nonce        = isset( $_POST['mo2f_whitelabelling_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_whitelabelling_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-whitelabelling-nonce' ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_otp_over_email_template':
				case 'mo2f_out_of_band_email_template':
				case 'mo2f_reconfig_link_email_template':
				case 'mo2f_backup_code_email_template':
				case 'mo2f_new_ip_detected_email_template':
					$this->mo2f_save_custom_email_template( $_POST );
					break;

				case 'mo2f_otp_over_email_reset':
				case 'mo2f_out_of_band_email_reset':
				case 'mo2f_reconfig_link_email_reset':
				case 'mo2f_backup_code_email_reset':
				case 'mo2f_new_ip_detected_email_reset':
					$this->mo2f_reset_custom_email_template( $_POST );
					break;

			}
		}

		/**
		 * Saves custom email templates.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_save_custom_email_template( $post ) {
			$show_message  = new MoWpnsMessages();
			$email_content = stripslashes( $post[ $post['option'] . '_config_message' ] );
			if ( strpos( $email_content, 'xecurify-logo.png' ) ) {
				update_site_option( $post['subject_name'], stripslashes( $post['mo2f_email_subject'] ) );
				update_site_option( $post['option'], stripslashes( $post[ $post['option'] . '_config_message' ] ) );
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::EMAIL_TEMPLATE_SAVED ), 'SUCCESS' );
			} else {
				$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( 'Custom logos are available in premium plans. Please upgrade to access.' ), 'ERROR' );
			}

		}

		/**
		 * Resets email templates.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_reset_custom_email_template( $post ) {
			$show_message = new MoWpnsMessages();
			$option_name  = str_replace( 'reset', 'template', $post['option'] );
			update_site_option( $post['subject_name'], stripslashes( $GLOBALS[ $post['subject_name'] ] ) );
			update_site_option( str_replace( 'reset', 'template', $option_name ), $GLOBALS[ str_replace( 'reset', 'template', $option_name ) ] );
			$show_message->mo2f_show_message( MoWpnsMessages::lang_translate( MoWpnsMessages::EMAIL_TEMPLATE_RESET ), 'SUCCESS' );
		}

	}new Mo2f_Whitelabelling();
}
