<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package        miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\Mo2f_Api;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'MocURL' ) ) {
	/**
	 * This library is miniOrange Authentication Service.
	 * Contains Request Calls to Customer service.
	 **/
	class MocURL {

		use Instance;

		/**
		 * From-address used for emails relayed through the miniOrange cloud.
		 */
		const CLOUD_EMAIL_FROM_ADDRESS = 'no-reply@xecurify.com';

		/**
		 * Instantiation of Mo2f_Api class.
		 *
		 * @var object
		 */
		private $mo2f_api;

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			$this->mo2f_api = Mo2f_Api::instance();
		}

		/**
		 * This function is invoke to create the customer after registration
		 *
		 * @param string $email .
		 * @param string $company .
		 * @param string $password .
		 * @param string $phone .
		 * @param string $first_name .
		 * @param string $last_name .
		 * @return string
		 */
		public static function create_customer( $email, $company, $password, $phone = '', $first_name = '', $last_name = '' ) {
			$url          = MO_HOST_NAME . '/moas/rest/customer/add';
			$customer_key = MoWpnsConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = MoWpnsConstants::DEFAULT_API_KEY;
			$fields       = array(
				'companyName'    => $company,
				'areaOfInterest' => 'WordPress 2 Factor Authentication Plugin',
				'firstname'      => $first_name,
				'lastname'       => $last_name,
				'email'          => $email,
				'phone'          => $phone,
				'password'       => $password,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/**
		 * It will help to get customer key
		 *
		 * @param string $email It will get the customer key.
		 * @param string $password It will get the password.
		 * @return string
		 */
		public static function get_customer_key( $email, $password ) {
			$url      = MO_HOST_NAME . '/moas/rest/customer/key';
			$fields   = array(
				'email'    => $email,
				'password' => $password,
			);
			$json     = wp_json_encode( $fields );
			$response = self::call_api( $url, $json );

			return $response;
		}
		/**
		 * It will help to submit the contact form .
		 *
		 * @param  string  $q_email It is carrying the email address .
		 * @param  string  $q_phone .
		 * @param  string  $query .
		 * @param  boolean $call_setup .
		 * @return string
		 */
		public function submit_contact_us( $q_email, $q_phone, $query, $call_setup = false ) {
			$current_user      = wp_get_current_user();
			$url               = MO_HOST_NAME . '/moas/rest/customer/contact-us';
			$is_nc_with_1_user = MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' ) && MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NNC', 'site_option' );
			$is_ec_with_1_user = ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' );
			$onprem            = MO2F_IS_ONPREM ? 'O' : 'C';

			$customer_feature = '';

			if ( $is_ec_with_1_user ) {
				$customer_feature = 'V1';
			} elseif ( $is_nc_with_1_user ) {
				$customer_feature = 'V3';
			}
			global $mo2f_mo_wpns_utility;
			if ( $call_setup ) {
				$query = '[Call Request - WordPress 2 Factor Authentication Plugin: ' . $onprem . $customer_feature . ' - V ' . MO2F_VERSION . ' ]: ' . $query;
			} else {
				$query = '[WordPress 2 Factor Authentication Plugin: ' . $onprem . $customer_feature . ' - V ' . MO2F_VERSION . ' ]: ' . $query;
			}

			$fields       = array(
				'firstName' => $current_user->user_firstname,
				'lastName'  => $current_user->user_lastname,
				'company'   => isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
				'email'     => $q_email,
				'ccEmail'   => 'mfasupport@xecurify.com',
				'phone'     => $q_phone,
				'query'     => $query,
			);
			$field_string = wp_json_encode( $fields );
			$response     = self::call_api( $url, $field_string );
			set_transient( 'mo2f_query_sent', 1, 30 );
			return true;
		}
		/**
		 * Sends OTP/verification link to users SMS/Email.
		 *
		 * @param string $auth_type Authnetication method.
		 * @param string $phone Phone.
		 * @param string $email Email ID.
		 * @return mixed.
		 */
		public function send_otp_token( $auth_type, $phone = null, $email = null ) {
			$c_key       = get_site_option( 'mo2f_customerKey' );
			$api_key     = get_site_option( 'mo2f_api_key' );
			$url         = MO_HOST_NAME . '/moas/api/auth/challenge';
			$fields      = array(
				'customerKey'     => $c_key,
				'email'           => $email,
				'phone'           => $phone,
				'username'        => $email,
				'authType'        => $auth_type,
				'transactionName' => 'WordPress 2 Factor Authentication Plugin',
			);
			$json        = wp_json_encode( $fields );
			$auth_header = $this->create_auth_header( $c_key, $api_key );
			$response    = self::call_api( $url, $json, $auth_header );
			$content     = json_decode( $response, true );
			if ( 'SUCCESS' === ( $content['status'] ?? '' ) ) {
				if ( MoWpnsConstants::OTP_OVER_SMS === $auth_type ) {
					$cmvtywluaw5nt1rqsms = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' );
					if ( $cmvtywluaw5nt1rqsms > 0 ) {
						update_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', $cmvtywluaw5nt1rqsms - 1 );
					}
					if ( 5 === (int) get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ) {
						Miniorange_Authentication::mo2f_low_otp_alert( 'sms' );
					}
				} elseif ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_type || MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_type ) {
					MoWpnsUtility::mo2f_decrement_email_transaction();
				}
			}
			return $response;
		}

		/**
		 * Validates otp token.
		 *
		 * @param string $transaction_id Transaction id.
		 * @param mixed  $otp_token OTP token.
		 * @param string $username Username.
		 * @param string $auth_type Auth type.
		 * @return string JSON response body from API.
		 */
		public function validate_otp_token( $transaction_id, $otp_token, $username, $auth_type ) {
			$url          = MO_HOST_NAME . '/moas/api/auth/validate';
			$customer_key = get_site_option( 'mo2f_customerKey' );
			$api_key      = get_site_option( 'mo2f_api_key' );
			$fields       = array(
				'customerKey' => $customer_key,
				'username'    => $username,
				'authType'    => $auth_type,
				'txId'        => $transaction_id,
				'token'       => ! is_array( $otp_token ) ? $otp_token : null,
				'answers'     => is_array( $otp_token ) ? array(
					array(
						'question' => $otp_token[0],
						'answer'   => $otp_token[1],
					),
					array(
						'question' => $otp_token[2],
						'answer'   => $otp_token[3],
					),
				) : null,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = $this->create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/**
		 * It will check the customer.
		 *
		 * @param string $email .
		 * @return string
		 */
		public function check_customer( $email ) {
			$url      = MO_HOST_NAME . '/moas/rest/customer/check-if-exists';
			$fields   = array(
				'email' => $email,
			);
			$json     = wp_json_encode( $fields );
			$response = self::call_api( $url, $json );
			return $response;
		}
		/**
		 * Call in forgot password
		 *
		 * @return string
		 */
		public function mo_wpns_forgot_password() {
			$url          = MO_HOST_NAME . '/moas/rest/customer/password-reset';
			$email        = get_site_option( 'mo2f_email' );
			$customer_key = get_site_option( 'mo2f_customerKey' );
			$api_key      = get_site_option( 'mo2f_api_key' );

			$fields      = array(
				'email' => $email,
			);
			$json        = wp_json_encode( $fields );
			$auth_header = $this->create_auth_header( $customer_key, $api_key );
			$response    = self::call_api( $url, $json, $auth_header );
			return $response;
		}
		/**
		 * Send the email alert
		 *
		 * @param string $email email.
		 * @param string $phone phone.
		 * @param string $message message.
		 * @param string $feedback_option feedback_option.
		 * @param bool   $mo2f_contact_back Contact back.
		 * @return string
		 */
		public function mo2f_send_email_alert( $email, $phone, $message, $feedback_option, $mo2f_contact_back ) {
			global $mo2f_mo_wpns_utility;
			global $user;
			$url          = MO_HOST_NAME . '/moas/api/notify/send';
			$customer_key = MoWpnsConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = MoWpnsConstants::DEFAULT_API_KEY;
			$from_email   = self::CLOUD_EMAIL_FROM_ADDRESS;
			$di           = get_site_option( 'No_of_days_active_work' );
			$di           = intval( $di );
			if ( 'mo_wpns_skip_feedback' === $feedback_option ) {
				$subject = 'Deactivate [Feedback Skipped]: WordPress miniOrange 2-Factor Plugin :' . $di;

			} elseif ( 'mo_wpns_feedback' === $feedback_option ) {

				$subject = 'Feedback: WordPress miniOrange 2-Factor Plugin - ' . $email . ' : ' . $di;
			}

			$mo2f_user         = wp_get_current_user();
			$is_nc_with_1_user = MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' ) && MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NNC', 'site_option' );
			$is_ec_with_1_user = ! MoWpnsUtility::get_mo2f_db_option( 'mo2f_is_NC', 'site_option' );
			$onprem            = MO2F_IS_ONPREM ? 'O' : 'C';

			$customer_feature = '';

			if ( $is_ec_with_1_user ) {
				$customer_feature = 'V1';
			} elseif ( $is_nc_with_1_user ) {
				$customer_feature = 'V3';
			}
			$query = '[WordPress 2 Factor Authentication Plugin: ' . $onprem . $customer_feature . ' - V ' . MO2F_VERSION . ']: ' . $message;
			if ( $mo2f_contact_back ) {
				$query .= ' <strong>Contact me: Yes</strong>';
			} else {
				$query .= ' <strong>Contact me: No</strong>';
			}
			$company = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
			$content = '<div >Hello, <br><br>First Name :' . $mo2f_user->user_firstname . '<br><br>Last  Name :' . $mo2f_user->user_lastname . '   <br><br>Company :<a href="' . $company . '" target="_blank" >' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) . '</a><br><br>Phone Number :' . $phone . '<br><br>Email :<a href="mailto:' . esc_html( $email ) . '" target="_blank">' . esc_html( $email ) . '</a><br><br>Query :' . wp_kses_post( $query ) . '</div>';

			$fields       = array(
				'customerKey' => $customer_key,
				'sendEmail'   => true,
				'email'       => array(
					'customerKey' => $customer_key,
					'fromEmail'   => $from_email,
					'fromName'    => 'Xecurify',
					'toEmail'     => 'mfasupport@xecurify.com',
					'toName'      => 'mfasupport@xecurify.com',
					'subject'     => $subject,
					'content'     => $content,
				),
			);
			$field_string = wp_json_encode( $fields );
			$auth_header  = $this->create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $field_string, $auth_header );

			return $response;
		}

		/**
		 * Sends a fully-rendered subject/HTML email through the miniOrange cloud relay.
		 *
		 * @param string $to_email Recipient email address.
		 * @param string $subject Email subject.
		 * @param string $message Rendered HTML email body.
		 * @param string $from_name Display name for the From header.
		 * @param string $bcc_email Optional bcc recipient.
		 * @return bool
		 */
		public static function mo2f_send_cloud_email( $to_email, $subject, $message, $from_name = null, $bcc_email = '' ) {
			$from_name    = $from_name ? $from_name : get_bloginfo( 'name' );
			$url          = MO_HOST_NAME . '/moas/api/notify/send';
			$customer_key = get_site_option( 'mo2f_customerKey' );
			$api_key      = get_site_option( 'mo2f_api_key' );
			$fields       = array(
				'customerKey' => $customer_key,
				'sendEmail'   => true,
				'email'       => array(
					'customerKey' => $customer_key,
					'fromEmail'   => self::CLOUD_EMAIL_FROM_ADDRESS,
					'fromName'    => $from_name,
					'toEmail'     => $to_email,
					'toName'      => $to_email,
					'bccEmail'    => $bcc_email,
					'subject'     => $subject,
					'content'     => $message,
				),
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			$content      = json_decode( $response, true );
			$sent         = isset( $content['status'] ) && 'SUCCESS' === $content['status'];

			if ( ! $sent ) {
				MO2f_Utility::mo2f_debug_file( 'mo2f_send_cloud_email failed for ' . $to_email . ' - customerKey set: ' . ( $customer_key ? 'yes' : 'no' ) . ' - response: ' . $response );
			}

			return $sent;
		}

		/**
		 * It will help to creating header
		 *
		 * @param string $customer_key .
		 * @param string $api_key .
		 * @return string .
		 */
		private static function create_auth_header( $customer_key, $api_key ) {
			$current_timestamp_in_millis = round( microtime( true ) * 1000 );
			$current_timestamp_in_millis = number_format( $current_timestamp_in_millis, 0, '', '' );

			$string_to_hash = $customer_key . $current_timestamp_in_millis . $api_key;
			$auth_header    = hash( 'sha512', $string_to_hash );

			$header = array(
				'Content-Type'  => 'application/json',
				'Customer-Key'  => $customer_key,
				'Timestamp'     => $current_timestamp_in_millis,
				'Authorization' => $auth_header,
			);
			return $header;
		}

		/**
		 * The api function will be called for curl
		 *
		 * @param string $url .
		 * @param string $json_string .
		 * @param array  $http_header_array .
		 * @return string
		 */
		public static function call_api( $url, $json_string, $http_header_array = array(
			'Content-Type'  => 'application/json',
			'charset'       => 'UTF-8',
			'Authorization' => 'Basic',
		) ) {

			$args = array(
				'method'      => 'POST',
				'body'        => $json_string,
				'timeout'     => '10',
				'redirection' => '10',
				'sslverify'   => true,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $http_header_array,
			);

			$mo2f_api = new Mo2f_Api();
			$response = $mo2f_api->mo2f_wp_remote_post( $url, $args );
			return $response;
		}

		/**
		 * Function to update the user information.
		 *
		 * @param string  $email Email id of user.
		 * @param string  $auth_type Authentication method of user.
		 * @param int     $phone Phone number of user.
		 * @param string  $transaction_name Transaction name to verify the form of transaction.
		 * @param boolean $enable_admin_second_factor Second factor for user enabled by admin or not.
		 * @return mixed
		 */
		public function mo2f_update_user_info( $email, $auth_type, $phone, $transaction_name, $enable_admin_second_factor ) {
			$url          = MO_HOST_NAME . '/moas/api/admin/users/update';
			$customer_key = get_site_option( 'mo2f_customerKey' );

			$fields = array(
				'customerKey'            => $customer_key,
				'username'               => $email,
				'phone'                  => $phone,
				'authType'               => $auth_type,
				'transactionName'        => $transaction_name,
				'adminLoginSecondFactor' => $enable_admin_second_factor,
			);

			$http_header_array = $this->mo2f_api->get_http_header_array();

			$content = $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );

			return $content;
		}
		/**
		 * Function to get the information of user.
		 *
		 * @param string $email Email id of user.
		 * @return string
		 */
		public function mo2f_get_userinfo( $email ) {

			$url               = MO_HOST_NAME . '/moas/api/admin/users/get';
			$customer_key      = get_site_option( 'mo2f_customerKey' );
			$fields            = array(
				'customerKey' => $customer_key,
				'username'    => $email,
			);
			$mo2f_api          = new Mo2f_Api();
			$http_header_array = $mo2f_api->get_http_header_array();

			$data = $mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );

			if ( is_array( $data ) ) {
				return wp_json_encode( $data );
			} else {
				return $data;
			}
		}
		/**
		 * Function to check user email already exist with miniOrange or not.
		 *
		 * @param string $email Email id of user.
		 * @return string
		 */
		public function mo_check_user_already_exist( $email ) {

			$url               = MO_HOST_NAME . '/moas/api/admin/users/search';
			$customer_key      = get_site_option( 'mo2f_customerKey' );
			$fields            = array(
				'customerKey' => $customer_key,
				'username'    => $email,
			);
			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}

		/**
		 * Function to create user with miniOrange.
		 *
		 * @param object $currentuser Contains details of current user.
		 * @param string $email Email id of user.
		 * @return string
		 */
		public function mo_create_user( $currentuser, $email ) {

			$url               = MO_HOST_NAME . '/moas/api/admin/users/create';
			$customer_key      = get_site_option( 'mo2f_customerKey' );
			$fields            = array(
				'customerKey' => $customer_key,
				'username'    => $email,
				'firstName'   => $currentuser->user_firstname,
				'lastName'    => $currentuser->user_lastname,
			);
			$http_header_array = $this->mo2f_api->get_http_header_array();

			return $this->mo2f_api->mo2f_http_request( $url, $fields, $http_header_array );
		}

		/**
		 * Gets license details.
		 *
		 * @param string $application_name Api key of the user.
		 * @param string $license_type License type assigned by miniOrange to check whether the user is onPremise or cloud.
		 * @return string
		 */
		public function get_customer_transactions( $application_name, $license_type ) {
			$url = MO_HOST_NAME . '/moas/rest/customer/license';

			$customer_key = get_site_option( 'mo2f_customerKey' );
			$fields       = array(
				'customerId'      => $customer_key,
				'applicationName' => $application_name,
				'licenseType'     => $license_type,
			);

			$field_string = wp_json_encode( $fields );

			$headers = $this->mo2f_api->get_http_header_array();

			$content = $this->mo2f_api->mo2f_http_request( $url, $field_string, $headers );

			return $content;
		}

	}
}
