<?php
/**
 * File contains user's feedback related functions at the time of deactivation of plugin.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MocURL;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Whitelabelling' ) ) {
	/**
	 * Class Mo2f_Whitelabelling
	 */
	class Mo2f_Whitelabelling {

		use Instance;

		/**
		 * Mo2f_Whitelabelling class constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo2f_whitelabeling_action' ) );
			add_action( 'wp_ajax_mo2f_white_labelling_ajax', array( $this, 'mo2f_white_labelling_ajax' ) );
		}

		/**
		 * Handles AJAX requests for white-labelling settings in the plugin.
		 *
		 * @return void
		 */
		public function mo2f_white_labelling_ajax() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
			if ( ! wp_verify_nonce( $nonce, 'mo2f-white-labelling-ajax-nonce' ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::SOMETHING_WENT_WRONG ) );
			}
			$common_helper = new Mo2f_Common_Helper();
			$common_helper->mo2f_ilvn();
			$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			switch ( $option ) {
				case 'mo2f_google_app_name':
					$this->mo2f_google_app_name( $_POST );
					break;
				case 'mo2f_custom_security_questions_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_save_custom_security_questions_settings', $_POST );
					wp_send_json_success( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ) );
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
			update_site_option( 'mo2f_google_appname', $gauth_appname );
			wp_send_json_success();
		}

		/**
		 * Checks for post option value in the switch case.
		 *
		 * @return mixed
		 */
		public function mo2f_whitelabeling_action() {
			$show_message = new MoWpnsUtility();
			$nonce        = isset( $_POST['mo2f_whitelabelling_nonce'] ) ? sanitize_key( wp_unslash( $_POST['mo2f_whitelabelling_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f-whitelabelling-nonce' ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$common_helper = new Mo2f_Common_Helper();
			if ( $common_helper->mo2f_ilvn( false ) ) {
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
				case 'mo2f_add_custom_logo':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_add_custom_logo', $_FILES );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_reset_custom_logo':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_reset_custom_logo', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_login_popup_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_save_login_popup_setttings', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_reset_login_popup_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_reset_save_login_popup_setttings', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_custom_email_verification_response_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_save_custom_email_verification_response_settings', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_reset_save_custom_email_verification_response_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_reset_accept_deny_email_verification_settings', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
				case 'mo2f_reset_custom_security_questions_settings':
					do_action( 'mo2f_enterprise_plan_settings_action', 'mo2f_reset_custom_security_questions_settings', $_POST );
					$show_message->mo2f_show_upgrade_message( 'mo2f_enterprise_plan_settings_action' );
					break;
			}
		}

		/**
		 * Checks whether enterprise premium settings are available.
		 *
		 * @return bool
		 */
		private function mo2f_is_enterprise_plan_active() {
			$premium_file = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-enterprise-premium-settings.php';
			return file_exists( $premium_file );
		}

		/**
		 * Saves custom email templates.
		 *
		 * @param array $post Post data.
		 * @return void
		 */
		public function mo2f_save_custom_email_template( $post ) {
			$show_message      = new MoWpnsMessages();
			$subject_name      = isset( $post['subject_name'] ) ? sanitize_text_field( wp_unslash( $post['subject_name'] ) ) : '';
			$template_option   = isset( $post['option'] ) ? sanitize_text_field( wp_unslash( $post['option'] ) ) : '';
			$subject_value     = isset( $post['mo2f_email_subject'] ) ? stripslashes( wp_unslash( $post['mo2f_email_subject'] ) ) : '';
			$template_raw_key  = $template_option . '_config_message';
			$template_content  = isset( $post[ $template_raw_key ] ) ? stripslashes( wp_unslash( $post[ $template_raw_key ] ) ) : '';
			$existing_template = (string) MoWpnsUtility::get_mo2f_db_option( $template_option, 'site_option' );

			if ( ! $this->mo2f_is_enterprise_plan_active() ) {
				$existing_images = $this->mo2f_get_template_image_sources( $existing_template );
				$new_images      = $this->mo2f_get_template_image_sources( $template_content );
				if ( $existing_images !== $new_images ) {
					$show_message->mo2f_show_message(
						esc_html__( 'Updating email template logo is available in the Enterprise plan.', 'miniorange-2-factor-authentication' ),
						'ERROR'
					);
					return;
				}
			}

			update_site_option( $subject_name, $subject_value );
			update_site_option( $template_option, $template_content );
			$show_message->mo2f_show_message( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::EMAIL_TEMPLATE_SAVED ), 'SUCCESS' );
		}

		/**
		 * Gets normalized image src values from a template.
		 *
		 * @param string $template_content Template HTML content.
		 * @return array<int, string>
		 */
		private function mo2f_get_template_image_sources( $template_content ) {
			$image_sources = array();
			if ( empty( $template_content ) ) {
				return $image_sources;
			}

			if ( preg_match_all( '/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/i', $template_content, $matches ) && ! empty( $matches[2] ) ) {
				foreach ( $matches[2] as $src ) {
					$image_sources[] = trim( html_entity_decode( wp_strip_all_tags( (string) $src ) ) );
				}
			}

			return $image_sources;
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
			$show_message->mo2f_show_message( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::EMAIL_TEMPLATE_RESET ), 'SUCCESS' );
		}
	}
}
