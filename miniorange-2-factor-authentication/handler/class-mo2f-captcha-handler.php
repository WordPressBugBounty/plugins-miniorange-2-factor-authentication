<?php
/**
 * This file contains the CAPTCHA-on-login-screen feature.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Traits\Instance;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Captcha_Handler' ) ) {

	/**
	 * Adds an optional CAPTCHA challenge (Google reCAPTCHA v2 or Cloudflare Turnstile)
	 * to the native wp-login.php form, to blunt automated brute-force login attempts.
	 */
	class Mo2f_Captcha_Handler {

		use Instance;

		const PROVIDER_RECAPTCHA = 'recaptcha';
		const PROVIDER_TURNSTILE = 'turnstile';

		/**
		 * Mo2f_Captcha_Handler constructor.
		 */
		public function __construct() {
			if ( ! get_site_option( 'mo2f_enable_captcha' ) ) {
				return;
			}
			add_action( 'login_enqueue_scripts', array( $this, 'mo2f_captcha_enqueue_assets' ) );
			add_action( 'login_form', array( $this, 'mo2f_render_captcha_widget' ) );
			add_filter( 'authenticate', array( $this, 'mo2f_verify_captcha_on_login' ), 10 );
		}

		/**
		 * The configured provider, falling back to reCAPTCHA for an unrecognized value.
		 *
		 * @return string
		 */
		private function mo2f_get_provider() {
			$provider = get_site_option( 'mo2f_captcha_provider', self::PROVIDER_RECAPTCHA );
			return self::PROVIDER_TURNSTILE === $provider ? self::PROVIDER_TURNSTILE : self::PROVIDER_RECAPTCHA;
		}

		/**
		 * @return bool
		 */
		private function mo2f_is_wp_login_request() {
			return isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'];
		}

		/**
		 * Enqueues the provider's widget script on wp-login.php.
		 *
		 * @return void
		 */
		public function mo2f_captcha_enqueue_assets() {
			$site_key = get_site_option( 'mo2f_captcha_site_key' );
			if ( empty( $site_key ) || ! $this->mo2f_is_wp_login_request() ) {
				return;
			}
			$src = self::PROVIDER_TURNSTILE === $this->mo2f_get_provider()
				? 'https://challenges.cloudflare.com/turnstile/v0/api.js' // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Cloudflare Turnstile's widget script can't be self-hosted; it performs live bot-detection tied to Cloudflare's own backend.
				: 'https://www.google.com/recaptcha/api.js';
			wp_enqueue_script( 'mo2f-captcha', $src, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Third-party CDN script; a plugin version number doesn't apply to it.
			wp_add_inline_style(
				'login',
				'#login form div.mo2f-captcha-wrap{margin:0 0 24px;text-align:center}'
				. '#login form div.mo2f-captcha-wrap-turnstile{margin-bottom:34px}'
				. '.mo2f-captcha-scale-turnstile{width:255px;height:64px;margin:0 auto;overflow:hidden}'
				. '.mo2f-captcha-scale-turnstile .cf-turnstile{transform:scale(.85);transform-origin:0 0}'
				. '.mo2f-captcha-scale{width:256px;height:66px;margin:0 auto;overflow:hidden}'
				. '.mo2f-captcha-scale .g-recaptcha{transform:scale(.84);transform-origin:0 0}'
			);
		}

		/**
		 * Renders the CAPTCHA widget inside the login form.
		 *
		 * @return void
		 */
		public function mo2f_render_captcha_widget() {
			$site_key = get_site_option( 'mo2f_captcha_site_key' );
			if ( empty( $site_key ) || ! $this->mo2f_is_wp_login_request() ) {
				return;
			}
			if ( self::PROVIDER_TURNSTILE === $this->mo2f_get_provider() ) {
				echo '<div class="mo2f-captcha-wrap mo2f-captcha-wrap-turnstile"><div class="mo2f-captcha-scale-turnstile"><div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" data-size="flexible"></div></div></div>';
				return;
			}
			echo '<div class="mo2f-captcha-wrap"><div class="mo2f-captcha-scale"><div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div></div></div>';
		}

		/**
		 * @param WP_User|WP_Error|null $user Current authentication result.
		 * @return WP_User|WP_Error|null
		 */
		public function mo2f_verify_captcha_on_login( $user ) {
			if ( $user instanceof WP_User || ! $this->mo2f_is_wp_login_request() ) {
				return $user;
			}
			$secret_key = get_site_option( 'mo2f_captcha_secret_key' );
			$site_key   = get_site_option( 'mo2f_captcha_site_key' );
			if ( empty( $secret_key ) || empty( $site_key ) ) {
				return $user;
			}
			$is_turnstile = self::PROVIDER_TURNSTILE === $this->mo2f_get_provider();
			$field_name   = $is_turnstile ? 'cf-turnstile-response' : 'g-recaptcha-response';
			$token        = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This *is* the anti-automation check for the login form; a nonce doesn't apply.
			if ( '' === $token ) {
				return new WP_Error(
					'mo2f_captcha_missing',
					__( '<strong>Error:</strong> Please complete the CAPTCHA challenge.', 'miniorange-2-factor-authentication' )
				);
			}
			$verify_url = $is_turnstile
				? 'https://challenges.cloudflare.com/turnstile/v0/siteverify' // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Server-to-server token verification against Cloudflare's own API; this is the service itself, not an offloadable static asset.
				: 'https://www.google.com/recaptcha/api/siteverify';
			$response   = wp_remote_post(
				$verify_url,
				array(
					'timeout' => 10,
					'body'    => array(
						'secret'   => $secret_key,
						'response' => $token,
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				return $user;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $body['success'] ) ) {
				return new WP_Error(
					'mo2f_captcha_failed',
					__( '<strong>Error:</strong> CAPTCHA verification failed. Please try again.', 'miniorange-2-factor-authentication' )
				);
			}
			return $user;
		}
	}
}
