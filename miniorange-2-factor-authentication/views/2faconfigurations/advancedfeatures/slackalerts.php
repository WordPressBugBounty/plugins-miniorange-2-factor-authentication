<?php
/**
 * Description: Shows Slack alert settings UI.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsConstants;

$mo2f_slack_event_labels = array(
	'login_success' => __( 'Successful login', 'miniorange-2-factor-authentication' ),
	'login_failure' => __( 'Failed login attempt', 'miniorange-2-factor-authentication' ),
	'geo_blocked'   => __( 'Geo Blocking blocked a visitor', 'miniorange-2-factor-authentication' ),
	'ip_blocked'    => __( 'An IP address was blocked', 'miniorange-2-factor-authentication' ),
	'2fa_reset'     => __( 'A user\'s 2FA was reset', 'miniorange-2-factor-authentication' ),
);
?>
<div class="mo2f-setting-row mo2f-all-inclusive-plan">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title">
			<?php esc_html_e( 'Slack Alerts', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'all-inclusive', MoWpnsConstants::MO2F_PREMIUM_1PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		</span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container">
			<input type="checkbox" id="mo2f_enable_slack_alerts" />
			<span class="mo2f-settings-checkmark"></span>
		</label>
		<p class="mo2f-setting-desc"><?php esc_html_e( 'Sends a message to a Slack channel via an incoming webhook when the selected events happen.', 'miniorange-2-factor-authentication' ); ?></p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel mo2f-all-inclusive-plan flex" id="mo2f_enable_slack_alerts_settings">
	<div class="mo2f-settings-items mo-input-wrapper">
		<label class="mo-input-label"><?php esc_html_e( 'Slack Webhook URL', 'miniorange-2-factor-authentication' ); ?></label>
		<input type="text" id="mo2f_slack_webhook_url" class="mo2f-adv-set-input mo2f_width_80" placeholder="https://hooks.slack.com/services/..." />
	</div>
	<br>
	<div>
		<?php foreach ( $mo2f_slack_event_labels as $mo2f_event_key => $mo2f_event_label ) : ?>
			<div class="my-mo-3 mr-mo-4" style="display:inline-flex;align-items:center;white-space:nowrap;">
				<input type="checkbox" name="mo2f_slack_alert_event" value="<?php echo esc_attr( $mo2f_event_key ); ?>" /><?php echo esc_html( $mo2f_event_label ); ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="justify-start" style="margin-top:14px;display:flex;gap:8px;">
		<div class="mo2f-all-inclusive-plan">
		<button id="mo2f_slack_alerts_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		<button id="mo2f_slack_send_test_message_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Send Test Message', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
	<input type="hidden" id="mo2f_slack_alerts_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ); ?>" />
</div>
