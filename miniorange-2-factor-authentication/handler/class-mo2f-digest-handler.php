<?php
/**
 * This file contains the periodic activity digest email feature.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

namespace TwoFA\Handler;

use TwoFA\Database\MoWpnsDB;
use TwoFA\Helper\MocURL;
use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Digest_Handler' ) ) {

	/**
	 * Emails a periodic (daily/weekly) summary of login and 2FA-reset activity to site admins.
	 */
	class Mo2f_Digest_Handler {

		use Instance;

		const CRON_HOOK      = 'mo2f_digest_email_cron';
		const WEEKLY_SCHEDULE = 'mo2f_weekly';

		/**
		 * Mo2f_Digest_Handler constructor.
		 */
		public function __construct() {
			add_filter( 'cron_schedules', array( $this, 'mo2f_register_weekly_cron_schedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- Fixed 7-day interval is the point of a weekly digest.
			add_action( 'init', array( $this, 'mo2f_maybe_schedule_digest_cron' ) );
			add_action( self::CRON_HOOK, array( $this, 'mo2f_send_digest_email' ) );
		}

		/**
		 * @param array $schedules Existing cron schedules.
		 * @return array
		 */
		public function mo2f_register_weekly_cron_schedule( $schedules ) {
			if ( ! isset( $schedules[ self::WEEKLY_SCHEDULE ] ) ) {
				$schedules[ self::WEEKLY_SCHEDULE ] = array(
					'interval' => WEEK_IN_SECONDS,
					'display'  => __( 'Once Weekly (miniOrange 2FA digest)', 'miniorange-2-factor-authentication' ),
				);
			}
			return $schedules;
		}

		/**
		 * @return void
		 */
		public function mo2f_maybe_schedule_digest_cron() {
			if ( ! get_site_option( 'mo2f_enable_digest_email' ) ) {
				wp_clear_scheduled_hook( self::CRON_HOOK );
				return;
			}
			$desired_schedule = 'daily' === get_site_option( 'mo2f_digest_email_frequency', 'weekly' ) ? 'daily' : self::WEEKLY_SCHEDULE;
			$scheduled_event  = wp_get_scheduled_event( self::CRON_HOOK );
			if ( $scheduled_event && $scheduled_event->schedule === $desired_schedule ) {
				return; // already scheduled at the right cadence.
			}
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time(), $desired_schedule, self::CRON_HOOK );
		}

		/**
		 * @return string[]
		 */
		private function mo2f_get_recipients() {
			$configured = get_site_option( 'mo2f_digest_email_recipients', '' );
			if ( ! empty( $configured ) ) {
				return array_filter( array_map( 'trim', explode( ',', $configured ) ), 'is_email' );
			}
			$admin_emails = array();
			foreach ( get_users( array( 'capability' => 'manage_options' ) ) as $admin_user ) {
				if ( is_email( $admin_user->user_email ) ) {
					$admin_emails[] = $admin_user->user_email;
				}
			}
			return array_unique( $admin_emails );
		}

		/**
		 * @return void
		 */
		public function mo2f_send_digest_email() {
			if ( ! get_site_option( 'mo2f_enable_digest_email' ) ) {
				return;
			}
			$frequency = 'daily' === get_site_option( 'mo2f_digest_email_frequency', 'weekly' ) ? 'daily' : 'weekly';
			$since     = time() - ( 'daily' === $frequency ? DAY_IN_SECONDS : WEEK_IN_SECONDS );
			$summary   = MoWpnsDB::instance()->mo2f_get_digest_summary( $since );
			$recipients = $this->mo2f_get_recipients();
			if ( empty( $recipients ) ) {
				return;
			}
			$subject = sprintf(
				/* translators: 1: site name, 2: "Daily" or "Weekly". */
				__( '%2$s 2FA activity digest for %1$s', 'miniorange-2-factor-authentication' ),
				get_bloginfo( 'name' ),
				'daily' === $frequency ? __( 'Daily', 'miniorange-2-factor-authentication' ) : __( 'Weekly', 'miniorange-2-factor-authentication' )
			);
			$message = $this->mo2f_build_digest_email_body( $summary, $frequency );
			foreach ( $recipients as $recipient ) {
				MocURL::mo2f_send_cloud_email( $recipient, $subject, $message );
			}
		}

		/**
		 * @param array  $summary   From MoWpnsDB::mo2f_get_digest_summary().
		 * @param string $frequency 'daily' or 'weekly'.
		 * @return string
		 */
		private function mo2f_build_digest_email_body( $summary, $frequency ) {
			$period = 'daily' === $frequency ? __( 'last 24 hours', 'miniorange-2-factor-authentication' ) : __( 'last 7 days', 'miniorange-2-factor-authentication' );
			$rows   = array(
				__( 'New logins', 'miniorange-2-factor-authentication' )             => $summary['new_logins'],
				__( 'Failed login attempts', 'miniorange-2-factor-authentication' )  => $summary['failed_attempts'],
				__( 'Admin-initiated 2FA resets', 'miniorange-2-factor-authentication' ) => $summary['admin_resets'],
				__( 'Self-service 2FA resets', 'miniorange-2-factor-authentication' ) => $summary['self_service_resets'],
			);
			$message = '<p>' . sprintf(
				/* translators: 1: site name, 2: time period covered. */
				esc_html__( 'Here\'s what happened on %1$s in the %2$s:', 'miniorange-2-factor-authentication' ),
				esc_html( get_bloginfo( 'name' ) ),
				esc_html( $period )
			) . '</p>';
			$message .= '<table cellpadding="6" style="border-collapse:collapse;">';
			foreach ( $rows as $label => $count ) {
				$message .= '<tr><td style="border:1px solid #ddd;">' . esc_html( $label ) . '</td><td style="border:1px solid #ddd;text-align:right;"><b>' . (int) $count . '</b></td></tr>';
			}
			$message .= '</table>';
			$message .= '<p>' . sprintf(
				/* translators: %s: link to the Compliance Log report. */
				wp_kses( __( 'Full details are in the <a href="%s">Compliance Log</a>.', 'miniorange-2-factor-authentication' ), array( 'a' => array( 'href' => array() ) ) ),
				esc_url( admin_url( 'admin.php?page=mo_2fa_reports&subpage=compliancelog' ) )
			) . '</p>';
			return $message;
		}
	}
}
