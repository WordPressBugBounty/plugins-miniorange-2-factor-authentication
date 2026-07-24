<?php
/** The miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 *
 * @package      miniorange-2-factor-authentication/helper
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;
use TwoFA\Helper\MoWpnsHandler;
use TwoFA\Handler\Twofa\Miniorange_Authentication;
use WP_Session_Tokens;

require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'class-instance.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoWpnsUtility' ) ) {
	/**
	 * This class has all functions used throughout the plugin to log in through mobile authentication as an additional layer of security over password.
	 */
	class MoWpnsUtility {

		use Instance;

		/**
		 * To check whether the customer has registered a miniOrange account.
		 *
		 * @return boolean
		 */
		public static function icr() {
			$email        = get_option( 'mo2f_email' );
			$customer_key = get_option( 'mo2f_customerKey' );
			if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
				return 0;
			} else {
				return 1;
			}
		}

		/**
		 * To check whether the variable is empty or null
		 *
		 * @param string $value variable that needs to chekc if empty or null.
		 * @return boolean
		 */
		public static function check_empty_or_null( $value ) {
			if ( ! isset( $value ) || null === $value || '' === $value ) {
				return true;
			}
			return false;
		}
		/**
		 * To generate the random string.
		 *
		 * @return string
		 */
		public static function rand() {
			return wp_generate_password( 32, false, false );
		}
		/**
		 * To check if the curl extension in installed.
		 *
		 * @return boolean
		 */
		public static function is_curl_installed() {
			if ( in_array( 'curl', get_loaded_extensions(), true ) ) {
				return 1;
			} else {
				return 0;
			}
		}
		/**
		 * TO check if the given Ip is valid or not.
		 *
		 * @param string $ip Ip that needs to be checked.
		 * @return boolean
		 */
		public static function mo2f_is_valid_ip( $ip ) {
			$new_ip = explode( ',', $ip );
			if ( is_array( $new_ip ) ) {
				$ip = $new_ip[0];
			}
			return filter_var( self::get_unique_ip( $ip ), FILTER_VALIDATE_IP ) !== false;
		}
		/**
		 * To get the IP of the user.
		 *
		 * @return string
		 */
		public static function get_client_ip() {
			if ( ( isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) && self::mo2f_is_valid_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) ) ) {
				return self::get_unique_ip( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
			} else {
				return 'UNKNOWN';
			}
		}
		/**
		 * Get single or first IP in the REMOTE_ADDR paramter of the SERVER.
		 *
		 * @param string $ip Available IP in the REMOTE_ADDR.
		 * @return string
		 */
		public static function get_unique_ip( $ip ) {
			$ip = explode( ',', $ip );
			if ( is_array( $ip ) ) {
				return sanitize_text_field( $ip[0] );
			}
			return sanitize_text_field( $ip );
		}
		/**
		 * To check if the email ID is valid or not.
		 *
		 * @param string $email Email that needs to be checked.
		 * @return boolean
		 */
		public static function check_if_valid_email( $email ) {
			$emailarray = explode( '@', $email );
			if ( 2 === count( $emailarray ) ) {
				return in_array( trim( $emailarray[1] ), MoWpnsConstants::$domains, true );
			} else {
				return false;
			}
		}
		/**
		 * To get the URL of the page
		 *
		 * @return string
		 */
		public static function get_current_url() {
			$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== sanitize_text_field( sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ) ) || ( isset( $_SERVER['SERVER_PORT'] ) && 443 === sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) ) ) ? 'https://' : 'http://';
			$url      = $protocol . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
			return $url;
		}
		/**
		 * To fetch option from options table or sitemeta.
		 *
		 * @param string $value option vaue to be checked in the table.
		 * @param string $type To distinguish between site_option and get_option.
		 * @return string
		 */
		public static function get_mo2f_db_option( $value, $type ) {
			// Check for both the original key and the prefixed version.
			$prefixed_key  = 'mo2f_' . $value;
			$default_value = null;
			if ( isset( $GLOBALS[ $value ] ) ) {
				$default_value = $GLOBALS[ $value ];
			} elseif ( isset( $GLOBALS[ $prefixed_key ] ) ) {
				$default_value = $GLOBALS[ $prefixed_key ];
			}
			if ( 'site_option' === $type ) {
				$db_value = get_site_option( $value, $default_value );
			} else {
				$db_value = get_option( $value, $default_value );
			}
			// Neutralize email templates on read too, in case they reached the DB some other way.
			if ( 'site_option' === $type && in_array( $value, self::mo2f_email_template_options(), true ) ) {
				$db_value = self::mo2f_neutralize_email_template( (string) $db_value );
			}
			return $db_value;
		}

		/**
		 * Site option names for the plugin's editable email templates.
		 *
		 * @return array
		 */
		public static function mo2f_email_template_options() {
			return array(
				'mo2f_otp_over_email_template',
				'mo2f_out_of_band_email_template',
				'mo2f_reconfig_link_email_template',
				'mo2f_backup_code_email_template',
				'mo2f_new_ip_detected_email_template',
			);
		}

		/**
		 * Allowed HTML for stored email templates.
		 *
		 * @return array
		 */
		public static function mo2f_email_template_allowed_html() {
			return array(
				'table'  => array(
					'cellpadding' => true,
					'cellspacing' => true,
					'width'       => true,
					'style'       => true,
					'border'      => true,
				),
				'tbody'  => array(),
				'thead'  => array(),
				'tfoot'  => array(),
				'tr'     => array(),
				'td'     => array(
					'style' => true,
					'width' => true,
				),
				'th'     => array(
					'style' => true,
					'width' => true,
				),
				'p'      => array( 'style' => true ),
				'div'    => array(
					'style' => true,
					'class' => true,
					'id'    => true,
				),
				'span'   => array(
					'style' => true,
					'class' => true,
				),
				'b'      => array(),
				'i'      => array(),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
				'hr'     => array(),
				'img'    => array(
					'src'    => true,
					'alt'    => true,
					'style'  => true,
					'width'  => true,
					'height' => true,
				),
				'a'      => array(
					'href'   => true,
					'target' => true,
					'style'  => true,
					'rel'    => true,
				),
				'input'  => array(
					'type'  => true,
					'name'  => true,
					'id'    => true,
					'value' => true,
				),
			);
		}

		/**
		 * Sanitizes stored email template HTML, preserving inline base64 image data URIs in <img src>.
		 *
		 * @param string $template_content Raw template HTML.
		 * @return string Sanitized template HTML.
		 */
		public static function mo2f_sanitize_email_template( $template_content ) {
			$data_uris         = array();
			$template_content  = preg_replace_callback(
				'/(<img\b[^>]*\bsrc\s*=\s*)(["\'])(data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+\/=]+)\2/i',
				function ( $matches ) use ( &$data_uris ) {
					$placeholder               = '##MO2F_DATA_URI_' . count( $data_uris ) . '##';
					$data_uris[ $placeholder ]  = $matches[3];
					return $matches[1] . $matches[2] . $placeholder . $matches[2];
				},
				$template_content
			);

			$template_content = wp_kses( $template_content, self::mo2f_email_template_allowed_html() );

			if ( $data_uris ) {
				$template_content = strtr( $template_content, $data_uris );
			}

			return $template_content;
		}

		/**
		 * Sensitive one-time placeholder names that must not appear in off-site links/images.
		 *
		 * @return array
		 */
		private static function mo2f_sensitive_template_tokens() {
			return array( 'otp_token', 'code1', 'code2', 'code3', 'code4', 'code5', 'accept_token', 'deny_token', 'denie_token', 'txid' );
		}

		/**
		 * Whether a decoded href/src value smuggles a sensitive placeholder outside a ##url## link.
		 *
		 * @param string $decoded_value Decoded attribute value.
		 * @return bool
		 */
		private static function mo2f_value_has_sensitive_placeholder( $decoded_value ) {
			if ( 0 === strpos( $decoded_value, '##url##' ) ) {
				return false;
			}
			foreach ( self::mo2f_sensitive_template_tokens() as $token ) {
				if ( false !== strpos( $decoded_value, '##' . $token . '##' ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Whether a decoded href points to a different host, port, or path prefix than this site.
		 *
		 * @param string $decoded_href Decoded href value.
		 * @return bool
		 */
		private static function mo2f_href_is_offsite( $decoded_href ) {
			if ( '' === $decoded_href || 0 === strpos( $decoded_href, '##' ) || '#' === $decoded_href[0] || 0 === stripos( $decoded_href, 'mailto:' ) ) {
				return false;
			}
			$link_parts = wp_parse_url( $decoded_href );
			if ( empty( $link_parts['host'] ) ) {
				return false;
			}
			$site_parts = wp_parse_url( home_url() );
			$site_host  = isset( $site_parts['host'] ) ? strtolower( $site_parts['host'] ) : '';
			$site_port  = isset( $site_parts['port'] ) ? (string) $site_parts['port'] : '';
			$site_path  = isset( $site_parts['path'] ) ? untrailingslashit( $site_parts['path'] ) : '';
			$link_port  = isset( $link_parts['port'] ) ? (string) $link_parts['port'] : '';
			$link_path  = isset( $link_parts['path'] ) ? $link_parts['path'] : '';
			$same_host  = strtolower( $link_parts['host'] ) === $site_host && $link_port === $site_port;
			$same_path  = '' === $site_path || $link_path === $site_path || 0 === strpos( $link_path, $site_path . '/' );
			return ! ( $same_host && $same_path );
		}

		/**
		 * Detects a sensitive placeholder inside a link/image that doesn't point back at this site.
		 *
		 * @param string $template_content Template HTML content.
		 * @return bool
		 */
		public static function mo2f_template_has_unsafe_placeholder_link( $template_content ) {
			if ( empty( $template_content ) || ! preg_match_all( '/\b(?:href|src)\s*=\s*(["\'])(.*?)\1/i', $template_content, $matches ) ) {
				return false;
			}
			foreach ( $matches[2] as $attr_value ) {
				if ( self::mo2f_value_has_sensitive_placeholder( html_entity_decode( $attr_value ) ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Detects an <a href> in the template that points somewhere other than this exact site.
		 *
		 * @param string $template_content Template HTML content.
		 * @return bool
		 */
		public static function mo2f_template_has_offsite_anchor( $template_content ) {
			if ( empty( $template_content ) || ! preg_match_all( '/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/i', $template_content, $matches ) ) {
				return false;
			}
			foreach ( $matches[2] as $href ) {
				if ( self::mo2f_href_is_offsite( html_entity_decode( $href ) ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Neutralizes an email template for safe sending, regardless of how it reached the database.
		 *
		 * @param string $template_content Raw template HTML.
		 * @return string Safe-to-send template HTML.
		 */
		public static function mo2f_neutralize_email_template( $template_content ) {
			if ( empty( $template_content ) ) {
				return $template_content;
			}
			$template_content = self::mo2f_sanitize_email_template( $template_content );

			$template_content = preg_replace_callback(
				'/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1[^>]*\/?>/i',
				function ( $matches ) {
					return self::mo2f_value_has_sensitive_placeholder( html_entity_decode( $matches[2] ) ) ? '' : $matches[0];
				},
				$template_content
			);

			$template_content = preg_replace_callback(
				'/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is',
				function ( $matches ) {
					$decoded_href = html_entity_decode( $matches[2] );
					$unsafe       = self::mo2f_value_has_sensitive_placeholder( $decoded_href ) || self::mo2f_href_is_offsite( $decoded_href );
					return $unsafe ? $matches[3] : $matches[0];
				},
				$template_content
			);

			return $template_content;
		}
		/**
		 * Collect data for the plugin configurations.
		 *
		 * @return string
		 */
		public static function mo_2fa_send_configuration() {
			global $mo2fdb_queries;
			$user_object   = wp_get_current_user();
			$other_methods = $mo2fdb_queries->get_all_user_2fa_methods();
			$method_count  = array_count_values( $other_methods );
			$show_methods  = array();
			foreach ( $method_count as $method => $count ) {
				if ( ! empty( $method ) ) {
					array_push( $show_methods, ' ' . $method . ' (' . $count . ')' );
				}
			}
			$show_methods                 = implode( ',', $show_methods );
			$key                          = get_option( 'mo2f_customerKey' );
			$is_plugin_active_for_network = is_plugin_active_for_network( MoWpnsConstants::TWO_FACTOR_SETTINGS );
			$is_onprem                    = MO2F_IS_ONPREM;
			$pricing_page_visits          = get_site_option( 'mo2fa_visit', 0 );
			$no_of_2fa_users              = $mo2fdb_queries->mo2f_get_no_of_2fa_users();
			$is_inline_used               = get_site_option( 'mo2f_is_inline_used' );
			$login_with_mfa_use           = get_site_option( 'mo2f_login_with_mfa_use' );
			$email_transactions           = self::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
			$sms_transactions             = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) ? get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z' ) : 0;
			$space                        = '<span>&nbsp;&nbsp;&nbsp;</span>';
			$user_count                   = isset( count_users()  ['total_users'] ) ? count_users()  ['total_users'] : '';
			$specific_plugins             = array(
				'UM_Functions'   => 'Ultimate Member',
				'wc_get_product' => 'WooCommerce',
				'pmpro_gateways' => 'Paid MemberShip Pro',
				'MoOTP'          => 'OTP',
			);
			$backup_codes_remaining       = get_user_meta( $user_object->ID, 'mo2f_backup_codes', true );

			if ( is_array( $backup_codes_remaining ) ) {
				$backup_codes_remaining = count( $backup_codes_remaining );
			} else {
				$backup_codes_remaining = 0;
			}
			$plugin_configuration = '<br><br><I>Plugin Configuration :-</I>' . $space . 'On-premise:' . ( $is_onprem ? 'Yes' : 'No' ) . $space . 'Login with MFA:' . ( (int) $login_with_mfa_use ? 'Yes' : 'No' ) . $space . 'Inline Registration:' . ( (int) $is_inline_used ? 'Yes' : 'No' ) . $space . 'No. of 2FA users :' . $no_of_2fa_users . $space . 'Total users : ' . $user_count . $space . 'Methods of users:' . ( '' === $show_methods ? 'NONE' : $show_methods ) . $space . 'Email transactions:' . $email_transactions . $space . 'SMS Transactions:' . $sms_transactions . $space . ( is_multisite() ? 'Multisite:Yes' : 'Single-site:Yes' ) . ( ( Miniorange_Authentication::mo2f_is_customer_registered() ) ? ( $space . 'Customer Key:' . $key ) : ( $space . "Customer Registered:'No" ) );

			if ( get_user_meta( $user_object->ID, 'mo_backup_code_generated', true ) || get_user_meta( $user_object->ID, 'mo_backup_code_downloaded', true ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Backup Codes:' . $backup_codes_remaining . '/5';
			}

			$plugins = '';

			foreach ( $specific_plugins as $class_name => $plugin_name ) {
				if ( class_exists( $class_name ) || function_exists( $class_name ) ) {
					$plugins = $plugins . "<span>&nbsp;</span>'" . $plugin_name . "'";
				}
			}

			$plugin_configuration = $plugin_configuration . ( '' !== $plugins ? $space . 'Installed Plugins:' . $plugins : '' );

			if ( is_multisite() ) {
				$plugin_configuration = $plugin_configuration . $space . ( $is_plugin_active_for_network ? "Network activated:'Yes" : "Site activated:'Yes" );
			}

			$plugin_configuration = $plugin_configuration . $space . 'Pricing Page visits : ' . $pricing_page_visits;

			if ( time() - get_site_option( 'mo_2fa_pnp' ) < 2592000 && ( get_site_option( 'mo_2fa_plan_type' ) || get_site_option( 'mo_2fa_addon_plan_type' ) ) ) {
				$plugin_configuration = $plugin_configuration . $space . "Checked plans:'";

				if ( get_site_option( 'mo_2fa_plan_type' ) ) {
					$plugin_configuration = $plugin_configuration . get_site_option( 'mo_2fa_plan_type' ) . "'";
				}

				if ( get_site_option( 'mo_2fa_addon_plan_type' ) ) {
					$plugin_configuration = $plugin_configuration . "<span>&nbsp;</span>'" . get_site_option( 'mo_2fa_addon_plan_type' ) . "'";
				}
			}

			$plugin_configuration = $plugin_configuration . $space . 'PHP_version:' . phpversion();
			if ( get_site_option( 'mo2f_grace_period' ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Grace Period: ' . esc_html( get_site_option( 'mo2f_grace_period_value' ) ) . '<span>&nbsp;</span>' . esc_html( get_site_option( 'mo2f_grace_period_type' ) );
			}
			$mo2f_wizard_skipped = get_option( 'mo2f_wizard_selected_method' ) ? esc_html( get_option( 'mo2f_wizard_selected_method' ) ) : esc_html( get_option( 'mo2f_wizard_skipped' ) );
			if ( get_option( 'mo2f_wizard_skipped' ) ) {
				$plugin_configuration = $plugin_configuration . $space . 'Setup Wizard Skipped: ' . $mo2f_wizard_skipped;
			} else {
				$plugin_configuration = $plugin_configuration . $space . 'Setup Wizard Skipped: No';
			}

			return $plugin_configuration;
		}

		/**
		 * Sends unusual activity email to the users.
		 *
		 * @param string $username username of the user.
		 * @param string $ip_adress ip adress of the user.
		 * @param string $reason Reason of unusual activity.
		 * @return mixed
		 */
		public function send_notification_to_user_for_unusual_activities( $username, $ip_adress, $reason ) {
			$content = '';

			if ( get_option( $ip_adress . $reason ) ) {
				return wp_json_encode(
					array(
						'status'        => 'SUCCESS',
						'statusMessage' => 'SUCCESS',
					)
				);
			}
			$user = get_user_by( 'login', $username );
			if ( $user && ! empty( $user->user_email ) ) {
				$to_email = $user->user_email;
			} else {
				return;
			}

			$from_email = get_option( 'mo2f_email' );
			$subject    = self::get_mo2f_db_option( 'mo2f_2fa_new_ip_detected_email_subject', 'site_option' );
			if ( get_option( 'custom_user_template' ) ) {
				$content = get_option( 'custom_user_template' );
				$content = str_replace( '##ipaddress##', $ip_adress, $content );
				$content = str_replace( '##username##', $username, $content );
			} else {
				$content = $this->get_message_content( $reason, $ip_adress, $username, $from_email );
			}

			$status = $this->wp_mail_send_notification( $to_email, $subject, $content, $from_email );
			return $status;
		}

		/**
		 * Gets the content for the email according to the reason.
		 *
		 * @param string $reason Reason of sending notification.
		 * @param string $ip_adress ip adress of the user.
		 * @param string $username username of the user.
		 * @param string $from_email Email adress of the administrator.
		 * @return string
		 */
		public function get_message_content( $reason, $ip_adress, $username = null, $from_email = null ) {
			switch ( $reason ) {
				case MoWpnsConstants::LOGIN_ATTEMPTS_EXCEEDED:
					$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has exceeded allowed failed login attempts on your website <b>' . get_bloginfo() . '</b> and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::IP_RANGE_BLOCKING:
					$content = "Hello,<br><br>The user's IP Address <b>" . $ip_adress . '</b> was found in IP Range specified by you in Advanced IP Blocking and we have blocked his IP address for further access to your website <b>' . get_bloginfo() . '</b>.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::BLOCKED_BY_ADMIN:
					$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has blocked by admin and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					return $content;
				case MoWpnsConstants::LOGGED_IN_FROM_NEW_IP:
					$content = self::get_mo2f_db_option( 'mo2f_new_ip_detected_email_template', 'site_option' );
					$content = str_replace( '##ipaddress##', $ip_adress, $content );
					return $content;
				case MoWpnsConstants::FAILED_LOGIN_ATTEMPTS_FROM_NEW_IP:
					$subject = 'Someone trying to access you account | ' . get_bloginfo();
					$content = 'Hello ' . $username . ',<br><br>Someone tried to login to your account from new IP Address <b>' . $ip_adress . '</b> on website <b>' . get_bloginfo() . "</b> with failed login attempts. Please <a href='mailto:" . $from_email . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();
					return $content;
				default:
					if ( is_null( $username ) ) {
						$content = 'Hello,<br><br>The user with IP Address <b>' . $ip_adress . '</b> has exceeded allowed trasaction limit on your website <b>' . get_bloginfo() . '</b> and we have blocked his IP address for further access to website.<br><br>You can login to your WordPress dashaboard to check more details.<br><br>Thanks,<br>miniOrange';
					} else {
						$content = 'Hello ' . $username . ',<br><br>Your account was logged in from new IP Address <b>' . $ip_adress . '</b> on website <b>' . get_bloginfo() . "</b>. Please <a href='mailto:" . $from_email . "'>contact us</a> if you don't recognise this activity.<br><br>Thanks,<br>" . get_bloginfo();
					}
					return $content;
			}
		}

		/**
		 * Sends email.
		 *
		 * @param string $to_email The email adress to whom the email will be sent.
		 * @param string $subject The subject of the email.
		 * @param string $content Content in the email.
		 * @return void
		 */
		public function wp_mail_send_notification( $to_email, $subject, $content ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $to_email, $subject, $content, $headers );
		}

		/**
		 * Sends HTML email and returns true only when delivery is not reported as failed.
		 *
		 * @param string       $to_email Recipient email.
		 * @param string       $subject Email subject.
		 * @param string       $message Email body.
		 * @param string|array $headers Optional email headers.
		 * @return bool
		 */
		public static function mo2f_send_html_email( $to_email, $subject, $message, $headers = array() ) {
			if ( empty( $headers ) ) {
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			}

			$mail_failed     = false;
			$on_mail_failed  = static function () use ( &$mail_failed ) {
				$mail_failed = true;
			};
			add_action( 'wp_mail_failed', $on_mail_failed );
			$sent = wp_mail( $to_email, $subject, $message, $headers );
			remove_action( 'wp_mail_failed', $on_mail_failed );

			if ( $sent && ! $mail_failed && isset( $GLOBALS['phpmailer'] ) && is_object( $GLOBALS['phpmailer'] ) ) {
				$phpmailer = $GLOBALS['phpmailer'];
				if ( is_a( $phpmailer, 'PHPMailer\PHPMailer\PHPMailer' ) || is_a( $phpmailer, 'PHPMailer' ) ) {
					if ( ! empty( $phpmailer->ErrorInfo ) ) {
						$sent = false;
					}
				}
			}

			return $sent && ! $mail_failed;
		}

		/**
		 * Decrements remaining on-prem email transactions after a successful send.
		 *
		 * @return void
		 */
		public static function mo2f_decrement_email_transaction() {
			if ( apply_filters( 'mo2f_is_lv_needed', false ) ) {
				return;
			}

			$remaining = (int) self::get_mo2f_db_option( 'cmVtYWluaW5nT1RQ', 'site_option' );
			if ( $remaining <= 0 ) {
				return;
			}

			if ( MO2F_IS_ONPREM && 5 === $remaining ) {
				Miniorange_Authentication::mo2f_low_otp_alert( 'email' );
			}

			update_site_option( 'cmVtYWluaW5nT1RQ', $remaining - 1 );
		}

		/**
		 * Check session details at login.
		 *
		 * @param object $user User.
		 * @param bool   $is_ajax_request is_ajax_request.
		 * @return mixed
		 */
		public function mo2f_check_session_management_details( $user, $is_ajax_request = true ) {
			$session_allowed = get_site_option( 'mo2f_maximum_allowed_session', 1 );
			$session_details = WP_Session_Tokens::get_instance( $user->ID );
			$session_count   = count( $session_details->get_all() );
			if ( $session_count >= (int) $session_allowed ) {
				$session_action = get_site_option( 'mo2f_session_allowed_type', 'allow_access' );
				if ( 'allow_access' === $session_action ) {
					$previous_session_details = WP_Session_Tokens::get_instance( $user->ID );
					$result                   = $previous_session_details->destroy_all();
				} elseif ( $is_ajax_request ) {
					wp_send_json_error( MoWpnsConstants::SESSION_LIMIT_REACHED );
				} else {
					return MoWpnsConstants::SESSION_LIMIT_REACHED;
				}
			}
		}

		/**
		 * Shows upgradation message.
		 *
		 * @param string $action Action.
		 * @return void
		 */
		public function mo2f_show_upgrade_message( $action ) {
			$show_message = new MoWpnsMessages();
			if ( ! has_action( $action ) ) {
				$show_message->mo2f_show_message( MoWpnsMessages::mo2f_get_message( MoWpnsMessages::GET_YOUR_PLAN_UPGRADED ), 'ERROR' );
			}
		}

		/**
		 * Returns remaining transactions.
		 *
		 * @return array Associative array containing email and SMS transactions.
		 */
		public function mo2f_check_remaining_transactions() {
			$email_transactions = apply_filters( 'mo2f_is_lv_needed', false ) ? 'Unlimited' : get_site_option( 'cmVtYWluaW5nT1RQ', 30 );
			$sms_transactions   = get_site_option( 'cmVtYWluaW5nT1RQVHJhbnNhY3Rpb25z', 0 );
			if ( 'Unlimited' !== $email_transactions && ( ! is_numeric( $email_transactions ) || empty( $email_transactions ) || $email_transactions < 0 ) ) {
				$email_transactions = 0;
			}

			if ( ! is_numeric( $sms_transactions ) || empty( $sms_transactions ) || $sms_transactions < 0 ) {
				$sms_transactions = 0;
			}

			return array(
				'email_transactions' => $email_transactions,
				'sms_transactions'   => $sms_transactions,
			);
		}

		/**
		 * Handle attempt validation logic.
		 *
		 * @param string $error_type Error type to send.
		 * @param string $session_id_encrypt Session id.
		 * @return void
		 */
		public function mo2f_handle_attempt_validation( $error_type, $session_id_encrypt ) {
			$common_helper = new Mo2f_Common_Helper();
			$user_id       = $common_helper->mo2f_get_current_user_id( $session_id_encrypt );

			$attempts = get_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect' );
			if ( is_numeric( $attempts ) && $attempts > 1 ) {
				set_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect', $attempts - 1, 300 );
				wp_send_json_error( $error_type );
			} else {
				delete_transient( $session_id_encrypt . 'mo2f_attempts_before_redirect' );
				if ( $user_id ) {
					$this->mo2f_set_login_lockout( $user_id );
				}
				wp_send_json_error(
					array(
						'status'    => MoWpnsConstants::MO2F_ACCOUNT_LOCKED,
						'remaining' => MoWpnsConstants::MO2F_LOGIN_LOCKOUT_DURATION,
					)
				);
			}
		}

		/**
		 * Locks a user's 2FA login validation for a fixed duration, keyed to the account
		 * rather than the client-suppliable session id, so re-initiating the login flow
		 * does not grant a fresh attempt quota.
		 *
		 * @param int $user_id User id to lock out.
		 * @return void
		 */
		public function mo2f_set_login_lockout( $user_id ) {
			$duration = MoWpnsConstants::MO2F_LOGIN_LOCKOUT_DURATION;
			set_transient( 'mo2f_login_lockout_' . $user_id, time() + $duration, $duration );
		}

		/**
		 * Returns the number of seconds remaining in a user's 2FA login lockout, or 0 if not locked.
		 *
		 * @param int $user_id User id to check.
		 * @return int
		 */
		public function mo2f_get_login_lockout_remaining( $user_id ) {
			$locked_until = get_transient( 'mo2f_login_lockout_' . $user_id );
			if ( $locked_until && $locked_until > time() ) {
				return (int) ( $locked_until - time() );
			}
			return 0;
		}
	}
}
