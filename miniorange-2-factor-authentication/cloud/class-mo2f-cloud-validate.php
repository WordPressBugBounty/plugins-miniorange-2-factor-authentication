<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2023  miniOrange
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
 * @package        miniorange-2-factor-authentication/cloud
 */

namespace TwoFA\Cloud;

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Api;
use TwoFA\Helper\TwoFAMoSessions;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */

if ( ! class_exists( 'Mo2f_Cloud_Validate' ) ) {

	/**
	 *  Class contains functions to validate 2FA.
	 */
	class Mo2f_Cloud_Validate {

		/**
		 * Cloud flow for validating Google Authenticator method
		 *
		 * @param string $useremail user email.
		 * @param string $otptoken google authenticator otp token.
		 * @param string $secret google authenticator secret.
		 * @return mixed
		 */
		public function mo2f_google_auth_validate( $useremail, $otptoken, $secret ) {
			$url          = MO_HOST_NAME . '/moas/api/auth/validate-google-auth-secret';
			$mo2f_api     = new Mo2f_Api();
			$customer_key = get_site_option( 'mo2f_customerKey' );
			$field_string = array(
				'customerKey'       => $customer_key,
				'username'          => $useremail,
				'secret'            => $secret,
				'otpToken'          => $otptoken,
				'authenticatorType' => MoWpnsConstants::GOOGLE_AUTHENTICATOR,
			);

			$http_header_array = $mo2f_api->get_http_header_array();
			return $mo2f_api->mo2f_http_request( $url, $field_string, $http_header_array );
		}

		/**
		 * KBA verificaion in cloud login flow
		 *
		 * @param string $user_id User ID.
		 * @param string $session_id session id.
		 * @param string $redirect_to redirect url.
		 * @return mixed
		 */
		public function mo2f_login_kba_verification( $user_id, $session_id, $redirect_to ) {
			global $mo2fdb_queries, $mo2f_onprem_cloud_obj;
			$pass2fa_login = new Miniorange_Password_2Factor_Login();
			$user_email    = $mo2fdb_queries->get_user_detail( 'mo2f_user_email', $user_id );
			$content       = $mo2f_onprem_cloud_obj->send_otp_token( null, $user_email, MoWpnsConstants::SECURITY_QUESTIONS );
			$response      = json_decode( $content, true );
			if ( JSON_ERROR_NONE === json_last_error() ) { /* Generate Qr code */
				if ( 'SUCCESS' === $response['status'] ) {
					MO2f_Utility::mo2f_set_transient( $session_id, 'mo2f_transactionId', $response['txId'] );
					$questions = array( $response['questions'][0]['question'], $response['questions'][1]['question'] );
					TwoFAMoSessions::add_session_var( 'mo_2_factor_kba_questions', $questions );
					return $questions;
				} elseif ( 'ERROR' === $response['status'] ) {
					$pass2fa_login->remove_current_activity( $session_id );
					$error = new WP_Error();
					$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.', 'miniorange-2-factor-authentication' ) );
					return $error;
				}
			} else {
				$pass2fa_login->remove_current_activity( $session_id );
				$error = new WP_Error();
				$error->add( 'empty_username', __( '<strong>ERROR</strong>: An error occured while processing your request. Please Try again.', 'miniorange-2-factor-authentication' ) );

				return $error;
			}
		}

	}
}
