<?php
/**
 * Shows a dismissible admin notice inviting satisfied, established admins to
 * leave a WordPress.org review.
 *
 * @package miniorange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Review_Notice' ) ) {

	/**
	 * Class Mo2f_Review_Notice
	 */
	class Mo2f_Review_Notice {

		use Instance;

		const REVIEW_URL              = 'https://wordpress.org/support/plugin/miniorange-2-factor-authentication/reviews/#new-post';
		const ELIGIBLE_AFTER_DAYS     = 14;
		const SNOOZE_DAYS             = 14;

		/**
		 * Mo2f_Review_Notice class constructor.
		 */
		private function __construct() {
			add_action( 'admin_init', array( $this, 'mo2f_handle_review_notice_action' ) );
			add_action( 'admin_notices', array( $this, 'mo2f_maybe_render_review_notice' ) );
		}

		/**
		 * Processes a click on one of the notice's three links. All three require
		 * the same capability + nonce; only the target option/redirect differs.
		 *
		 * @return void
		 */
		public function mo2f_handle_review_notice_action() {
			if ( ! isset( $_GET['mo2f_review_notice_action'] ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mo2f_review_notice_nonce' ) ) {
				return;
			}
			switch ( sanitize_text_field( wp_unslash( $_GET['mo2f_review_notice_action'] ) ) ) {
				case 'reviewed':
					update_site_option( 'mo2f_review_notice_dismissed', 1 );
					wp_redirect( self::REVIEW_URL ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Destination is a hardcoded constant, not user input.
					exit;
				case 'later':
					update_site_option( 'mo2f_review_notice_remind_after', time() + self::SNOOZE_DAYS * DAY_IN_SECONDS );
					break;
				case 'dismiss':
					update_site_option( 'mo2f_review_notice_dismissed', 1 );
					break;
				default:
					return;
			}
			wp_safe_redirect( remove_query_arg( array( 'mo2f_review_notice_action', '_wpnonce' ) ) );
			exit;
		}

		/**
		 * Renders the notice, if the admin is eligible to see it:
		 * - never saw it and clicked "no thanks" / "I left a review"
		 * - not currently snoozed
		 * - the plugin has been active for at least ELIGIBLE_AFTER_DAYS
		 * - at least one user actually has 2FA configured (so this asks people
		 *   who have gotten real value, not day-one installs that never finished setup)
		 *
		 * @return void
		 */
		public function mo2f_maybe_render_review_notice() {
			if ( ! current_user_can( 'manage_options' ) || get_site_option( 'mo2f_review_notice_dismissed' ) ) {
				return;
			}
			$remind_after = (int) get_site_option( 'mo2f_review_notice_remind_after', 0 );
			if ( $remind_after && time() < $remind_after ) {
				return;
			}
			$activated_time = (int) get_site_option( 'mo2f_activated_time', 0 );
			if ( ! $activated_time || ( time() - $activated_time ) < self::ELIGIBLE_AFTER_DAYS * DAY_IN_SECONDS ) {
				return;
			}
			global $mo2fdb_queries;
			if ( ! $mo2fdb_queries || ! $mo2fdb_queries->mo2f_get_no_of_2fa_users() ) {
				return;
			}

			$args        = array(
				'_wpnonce' => wp_create_nonce( 'mo2f_review_notice_nonce' ),
			);
			$reviewed_url = add_query_arg( array_merge( $args, array( 'mo2f_review_notice_action' => 'reviewed' ) ) );
			$later_url    = add_query_arg( array_merge( $args, array( 'mo2f_review_notice_action' => 'later' ) ) );
			$dismiss_url  = add_query_arg( array_merge( $args, array( 'mo2f_review_notice_action' => 'dismiss' ) ) );
			?>
			<div class="notice notice-info mo2f-review-notice" style="padding:14px 16px;">
				<p style="margin:0 0 10px;">
					<strong><?php esc_html_e( 'Enjoying miniOrange 2FA?', 'miniorange-2-factor-authentication' ); ?></strong>
					<?php esc_html_e( "It's been protecting your logins for a couple of weeks now. If it's working well for you, a quick review helps other WordPress site owners find it.", 'miniorange-2-factor-authentication' ); ?>
				</p>
				<p style="margin:0;">
					<a href="<?php echo esc_url( $reviewed_url ); ?>" class="button button-primary"><?php esc_html_e( 'Leave a review', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="<?php echo esc_url( $later_url ); ?>" class="button" style="margin-left:6px;"><?php esc_html_e( 'Remind me later', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-left:10px;color:#787c82;text-decoration:none;"><?php esc_html_e( 'No thanks', 'miniorange-2-factor-authentication' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
}
