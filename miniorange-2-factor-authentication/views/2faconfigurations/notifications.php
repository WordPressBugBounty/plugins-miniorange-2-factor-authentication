<?php
/**
 * This file shows the Notifications settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Notifications', 'miniorange-2-factor-authentication' ); ?></h2>
		<p><?php esc_html_e( 'Keeps admins and users informed when something about their 2FA setup changes.', 'miniorange-2-factor-authentication' ); ?></p>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Security Alert on 2FA Change', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_2fa_change_alerts" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_2fa_change_alerts ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<p class="mo2f-setting-desc"><?php esc_html_e( "Emails a user whenever their own 2FA method is changed, or an admin resets their 2FA, so they notice if it wasn't them. On by default.", 'miniorange-2-factor-authentication' ); ?></p>
		</div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Remind Users to Set Up 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_2fa_reminder_emails" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_2fa_reminder_emails ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<p class="mo2f-setting-desc"><?php esc_html_e( "Emails users who are required to have 2FA but haven't set it up yet, at most once a week per user, until they do. Off by default.", 'miniorange-2-factor-authentication' ); ?></p>
		</div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'New IP Login Notification', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<?php $mo2f_notify_admin_unusual_activity = get_site_option( 'mo_wpns_enable_unusual_activity_email_to_user' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_new_ip_login_notification" onclick="mo2f_enable_new_ip_notification()" <?php checked( $mo2f_notify_admin_unusual_activity ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<p class="mo2f-setting-desc">
				<?php esc_html_e( 'Enabling this option will send end users a notification on their email whenever they log into the site using new IP.', 'miniorange-2-factor-authentication' ); ?>
				&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_white_labelling&subpage=emailtemplates#mo2f_2fa_new_ip_detected_email_subject' ) ); ?>" style="cursor:pointer" target="_blank"><?php esc_html_e( 'Customize Email Template', 'miniorange-2-factor-authentication' ); ?></a>
			</p>
		</div>
	</div>
</div>

<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Diagnostics', 'miniorange-2-factor-authentication' ); ?></h2>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Enable Plugin Logs', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<label class="mo2f_checkbox_container">
				<input type="checkbox" id="mo2f_debug_log" name="mo2f_activate_plugin_log" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_debug_log ); ?> />
				<span class="mo2f-settings-checkmark"></span>
			</label>
			<p class="mo2f-setting-desc"><?php esc_html_e( 'The plugin debug log file is very helpful for debugging issues if you encounter any.', 'miniorange-2-factor-authentication' ); ?></p>
		</div>
	</div>
	<div class="justify-start mo2f-sub-panel <?php echo $mo2f_enable_debug_log ? 'flex' : 'hidden'; ?>" id="mo2f_debug_log_save">
		<div>
			<button class="mo2f-save-settings-button" id="mo2f_debug_download_form" name="mo2f_debug_download_form">
				<?php esc_html_e( 'Download log file', 'miniorange-2-factor-authentication' ); ?>
			</button>
			<button class="mo2f-reset-settings-button" id="mo2f_debug_delete_form" name="mo2f_debug_delete_form">
				<?php esc_html_e( 'Delete log file', 'miniorange-2-factor-authentication' ); ?>
			</button>
			<input type="hidden" id="mo2f_delete_log" name="mo2f_nonce_delete_log" value="<?php echo esc_html( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>" />
		</div>
	</div>
</div>
<form name="mo2f_form" method="post" id="mo2f_download_log_file">
	<input type="hidden" id="mo2f_download_log" name="mo_wpns_feedback_nonce" value="<?php echo esc_html( wp_create_nonce( 'mo-wpns-feedback-nonce' ) ); ?>"/>
	<input type="hidden" id="mo2f_download_logs" name="option" value="log_file_download"/>
</form>
<script>
jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
</script>
<?php
	global $mo2f_main_dir;
	wp_enqueue_script( 'login-settings-script', $mo2f_main_dir . '/includes/js/login-settings.min.js', array(), MO2F_VERSION, false );
	wp_localize_script(
		'login-settings-script',
		'loginSettings',
		array(
			'nonce' => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
		)
	);
