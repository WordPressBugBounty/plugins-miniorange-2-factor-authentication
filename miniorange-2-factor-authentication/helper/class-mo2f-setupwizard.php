<?php
/**
 * This file contains Setup wizard functions.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

namespace TwoFA\Helper;

use TwoFA\Handler\Twofa\Google_Auth_Onpremise;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Traits\Instance;
use WP_Error;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Setupwizard' ) ) {

	/**
	 * Class Mo2f_Setupwizard
	 */
	class Mo2f_Setupwizard {

		use Instance;

		/**
		 * Constructor of class.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'mo_2f_two_factor_setuwizard' ) );
		}

		/**
		 * Function for handling ajax requests.
		 *
		 * @return void
		 */
		public function mo_2f_two_factor_setuwizard() {
			add_action( 'wp_ajax_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax_setupwizard' ) );
			add_action( 'wp_ajax_nopriv_mo_two_factor_ajax', array( $this, 'mo_two_factor_ajax_setupwizard' ) );
		}


		/**
		 * Call functions as per ajax requests.
		 *
		 * @return void
		 */
		public function mo_two_factor_ajax_setupwizard() {
			$GLOBALS['mo2f_is_ajax_request'] = true;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'Unknown error occured. Please try again!' );
			}
			switch ( isset( $_POST['mo_2f_two_factor_ajax'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_2f_two_factor_ajax'] ) ) : '' ) {
				case 'mo_2fa_verify_GA_setup_wizard':
					$this->mo_2fa_verify_ga_setup_wizard();
					break;
				case 'mo_2fa_verify_KBA_setup_wizard':
					$this->mo_2fa_verify_kba_setup_wizard();
					break;
				case 'mo2f_skiptwofactor_wizard':
					$this->mo2f_skiptwofactor_wizard();
					break;
			}
		}

		/**
		 * Verify and register Security Questions for user.
		 *
		 * @return void
		 */
		public function mo_2fa_verify_kba_setup_wizard() {
			global $mo2fdb_queries;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			}
			$default_question_count = get_site_option( 'mo2f_default_kbaquestions_users', 2 );
			$custom_question_count  = get_site_option( 'mo2f_custom_kbaquestions_users', 1 );
			$total_questions        = $default_question_count + $custom_question_count;
			$kba_ques_ans           = array();
			for ( $i = 1; $i <= $total_questions; $i++ ) {
				$kba_ques_ans[ 'kba_q' . $i ] = isset( $post[ 'mo2f_kbaquestion_' . $i ] ) ? sanitize_text_field( wp_unslash( $post[ 'mo2f_kbaquestion_' . $i ] ) ) : '';
				$kba_ques_ans[ 'kba_a' . $i ] = isset( $post[ 'mo2f_kba_ans' . $i ] ) ? sanitize_text_field( wp_unslash( $post[ 'mo2f_kba_ans' . $i ] ) ) : '';
			}
			$user = wp_get_current_user();
			$this->mo2f_check_and_create_user( $user->ID );
			foreach ( $kba_ques_ans as $key => $value ) {
				if ( MO2f_Utility::mo2f_check_empty_or_null( $value ) ) {
					wp_send_json_error( 'Invalid Questions or Answers' );
				}
			}
			$questions        = array_keys( $kba_ques_ans );
			$unique_questions = array_unique( array_map( 'strtolower', $kba_ques_ans ) );
			if ( count( $questions ) !== count( $unique_questions ) ) {
				wp_send_json_error( 'The questions you select must be unique.' );
			}
			foreach ( $kba_ques_ans as $key => $value ) {
				$kba_ques_ans[ $key ] = addcslashes( stripslashes( $value ), '"\\' );
			}
			$email            = $user->user_email;
			$kba_registration = new MO2f_Cloud_Onprem_Interface();
			$mo2fdb_queries->update_user_details(
				$user->ID,
				array(
					'mo2f_SecurityQuestions_config_status' => true,
					'mo_2factor_user_registration_status'  => 'MO_2_FACTOR_PLUGIN_SETTINGS',
					'mo2f_user_email'                      => $email,
				)
			);
			$kba_reg_reponse = json_decode( $kba_registration->mo2f_register_kba_details( $email, $kba_ques_ans, $user->ID ), true );

			if ( 'SUCCESS' === $kba_reg_reponse['status'] ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( 'An error has occured while saving KBA details. Please try again.' );
			}
		}


		/**
		 * Function for verifying OTP for Google Authenticator in setup wizard.
		 *
		 * @return void
		 */
		public function mo_2fa_verify_ga_setup_wizard() {
			global $mo2fdb_queries;
			$path = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . 'twofa' . DIRECTORY_SEPARATOR . 'class-google-auth-onpremise.php';
			include_once $path;
			$obj_google_auth = new Google_auth_onpremise();
			$user_id         = wp_get_current_user()->ID;
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error( 'mo2f-ajax' );
			}
			$otp_token          = isset( $_POST['mo2f_google_auth_code'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_google_auth_code'] ) ) : null;
			$session_id_encrypt = isset( $_POST['mo2f_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mo2f_session_id'] ) ) : null;
			$secret             = $obj_google_auth->mo_a_auth_get_secret( $user_id );
			if ( $session_id_encrypt ) {
				$secret = MO2f_Utility::mo2f_get_transient( $session_id_encrypt, 'secret_ga' );
			}
			$content = $obj_google_auth->mo2f_verify_code( $secret, $otp_token );
			$content = json_decode( $content );
			if ( 'false' === $content->status ) {
				wp_send_json_error( 'Invalid One time Passcode. Please enter again' );
			} else {
				$obj_google_auth->mo_g_auth_set_secret( $user_id, $secret );
				$this->mo2f_check_and_create_user( $user_id );
				$mo2fdb_queries->update_user_details(
					$user_id,
					array(
						'mo2f_GoogleAuthenticator_config_status' => true,
						'mo2f_AuthyAuthenticator_config_status' => false,
						'mo2f_configured_2FA_method' => MoWpnsConstants::GOOGLE_AUTHENTICATOR,
						'user_registration_with_miniorange' => 'SUCCESS',
						'mo2f_user_email'            => wp_get_current_user()->user_email,
						'mo_2factor_user_registration_status' => 'MO_2_FACTOR_PLUGIN_SETTINGS',
					)
				);

				wp_send_json_success();
			}
			exit;
		}

		/**
		 * Function to skip 2-factor on setup wizard.
		 *
		 * @return void
		 */
		public function mo2f_skiptwofactor_wizard() {
			if ( ! check_ajax_referer( 'mo-two-factor-ajax-nonce', 'nonce', false ) ) {
				$error = new WP_Error();
				$error->add( 'empty_username', '<strong>' . esc_html__( 'ERROR', 'miniorange-2-factor-authentication' ) . '</strong>: ' . esc_html__( 'Invalid Request.', 'miniorange-2-factor-authentication' ) );
				wp_send_json_error( 'mo2f-ajax' );
				exit;
			} else {
				$skip_wizard_2fa_stage = isset( $_POST['twofactorskippedon'] ) ? sanitize_text_field( wp_unslash( $_POST['twofactorskippedon'] ) ) : null;

				update_site_option( 'mo2f_wizard_skipped', $skip_wizard_2fa_stage );
			}
		}

		/**
		 * Function to check and create user
		 *
		 * @param int $user_id User ID.
		 * @return void
		 */
		public function mo2f_check_and_create_user( $user_id ) {
			global $mo2fdb_queries;
			$twofactor_transactions = new Mo2fDB();
			$exceeded               = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $user_id ), 'is_user_limit_exceeded', array() );
			if ( $exceeded ) {
				echo 'User Limit has been exceeded';
				exit;
			}
			$mo2fdb_queries->insert_user( $user_id );
		}

	}
	new Mo2f_Setupwizard();
}
