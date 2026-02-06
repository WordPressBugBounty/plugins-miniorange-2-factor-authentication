<?php
/**
 * This file has function to set/ fetch transient variables.
 *
 * @package miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TwoFAMoSessions' ) ) {
	/**
	 * This class has function to set/ fetch transient functions
	 */
	class TwoFAMoSessions {

		use Instance;

		/**
		 * Set a secure cookie with proper security attributes.
		 *
		 * @param string $name Cookie name.
		 * @param string $value Cookie value.
		 * @param int    $expires Cookie expiration time.
		 * @param string $path Cookie path.
		 * @param string $domain Cookie domain.
		 * @return bool True on success, false on failure.
		 */
		private static function set_secure_cookie( $name, $value, $expires, $path = COOKIEPATH, $domain = COOKIE_DOMAIN ) {
			return setcookie(
				$name,
				$value,
				array(
					'expires'  => $expires,
					'path'     => $path,
					'domain'   => $domain,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		/**
		 * Set cookie and transient variable.
		 *
		 * @param string $key Key of the session and transient to be set.
		 * @param string $val Value of the session and transient to be set.
		 * @return void
		 */
		public static function add_session_var( $key, $val ) {
			if ( ! isset( $_COOKIE['transient_key'] ) ) {
				if ( ! wp_cache_get( 'transient_key' ) ) {
					$transient_key = MoWpnsUtility::rand();
					if ( ob_get_contents() ) {
						ob_clean();
					}
					self::set_secure_cookie( 'transient_key', $transient_key, time() + 12 * HOUR_IN_SECONDS );
					wp_cache_add( 'transient_key', $transient_key );
				} else {
					$transient_key = wp_cache_get( 'transient_key' );
				}
			} else {
				$transient_key = sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) );
			}
			set_site_transient( $transient_key . $key, $val, 12 * HOUR_IN_SECONDS );
		}
		/**
		 * Get cookie and transient variable
		 *
		 * @param string $key Key of the session and transient to fetch.
		 */
		public static function get_session_var( $key ) {
			$transient_key = isset( $_COOKIE['transient_key'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' );
			return get_site_transient( $transient_key . $key );
		}
		/**
		 * Unset cookie and transient variable.
		 *
		 * @param string $key Key of the session and transient to be unset.
		 */
		public static function unset_session( $key ) {
			$transient_key = isset( $_COOKIE['transient_key'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' );
			if ( ! MoWpnsUtility::check_empty_or_null( $transient_key ) ) {
				delete_site_transient( $transient_key . $key );
			}
		}
	}
}
