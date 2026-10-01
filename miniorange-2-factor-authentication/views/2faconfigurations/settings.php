<?php
/**
 * Description: This file is used to login settings.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Login Behavior', 'miniorange-2-factor-authentication' ); ?></h2>
		<p><?php esc_html_e( 'Controls what happens on the login screen itself and after a successful sign-in.', 'miniorange-2-factor-authentication' ); ?></p>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Enable Custom Redirection URL After Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_custom_redirect" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_custom_redirect ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		</div>
	</div>
	<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo $mo2f_enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_settings">
		<div>
			<table class="my-mo-3 w-5/6" id="mo2f_redirect_url_table">
				<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_all" <?php checked( 'redirect_all' === get_site_option( 'mo2f_redirect_url_for_users', 'redirect_all' ) ); ?>/><?php esc_html_e( 'Redirection URL for all users:', 'miniorange-2-factor-authentication' ); ?></div></td><td><input type="text" placeholder="Enter Redirect URL" class="mo2f-redirection-field" id="redirect_url_all" value="<?php echo get_option( 'mo2f_custom_redirect_url' ) ? esc_attr( get_option( 'mo2f_custom_redirect_url' ) ) : esc_url( home_url() ); ?>"></td></tr>
			</table>
		</div>
		<div class="relative mo2f-enterprise-plan">
			<table class="my-mo-3 w-3/4" id="mo2f_custom_redirect_url_table">
				<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_user_roles" <?php checked( 'redirect_user_roles' === get_site_option( 'mo2f_redirect_url_for_users' ) ); ?>/><?php esc_html_e( 'Redirection URL based on user roles:', 'miniorange-2-factor-authentication' ); ?>
				<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
			</div></td><td></td></tr>
		<?php

		foreach ( $mo2f_custom_login_urls as $mo2f_selected_role_name => $mo2f_redirection_url ) {
			?>
			<tr id="<?php echo esc_attr( $mo2f_selected_role_name ); ?>">
			<td><div class="mo2f_custom_redirections_roles"><select name="" class="ml-mo-4 w-1/2 text-center mo2f_redirect_url_roles">
			<?php
			foreach ( $wp_roles->role_names as $mo2f_role_id => $mo2f_role_name ) {
				?>
					<option value="<?php echo 'mo2fa_' . esc_attr( $mo2f_role_id ); ?>" <?php echo ( 'mo2fa_' . esc_attr( $mo2f_role_id ) === $mo2f_selected_role_name ? 'selected' : '' ); ?>><?php echo esc_attr( $mo2f_role_name ); ?></option>
			<?php } ?>
			</div></select></td><td><div class="mo2f_custom_redirections_urls"><input type="text" class="mo2f_redirection_url mo2f-redirection-field" placeholder="Enter Redirect URL" value="<?php echo esc_url( $mo2f_redirection_url ); ?>"></td></div><td><button class="mo2f-basic-plan mo2f-add-row" id="mo2f_add_custom_redirect_url_<?php echo ( esc_attr( $mo2f_role_id ) ); ?>">+</button></td><td><button class="mo2f-basic-plan mo2f-remove-row" id="mo2f_remove_custom_redirect_url">-</button></td>
				</tr>
			<?php
		}
		?>
		</table>
	</div>
		<div class="justify-start <?php echo $mo2f_enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_save"><div class="mo2f_enable_custom_redirect_save_button"><button id="mo2f_enable_custom_redirect_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Enable Email Magic Link Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_magic_link_login" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_magic_link_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		</div>
	</div>
	<?php $mo2f_magic_link_login_mode = $mo2f_magic_link_login_mode ? $mo2f_magic_link_login_mode : 'optional'; ?>
	<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo $mo2f_enable_magic_link_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_magic_link_login_settings">
		<div>
			<table class="my-mo-3 w-5/6" id="mo2f_magic_link_login_mode_table">
				<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_magic_link_login_mode" value="optional" <?php checked( 'optional' === $mo2f_magic_link_login_mode ); ?>/><?php esc_html_e( 'Optional: show it next to the password field', 'miniorange-2-factor-authentication' ); ?></div></td></tr>
				<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_magic_link_login_mode" value="mandatory" <?php checked( 'mandatory' === $mo2f_magic_link_login_mode ); ?>/><?php esc_html_e( 'Mandatory: this becomes the only way to log in, password field is hidden and password-based login is rejected', 'miniorange-2-factor-authentication' ); ?></div></td></tr>
			</table>
		</div>
		<div class="text-mo-tertiary-txt ml-mo-8" style="margin-bottom:16px;">
			<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sWarning:%2$12s Mandatory mode disables password-based login site-wide. Make sure outgoing email delivery works before enabling it, or admins may be locked out.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>'
				);
				?>
		</div>
		<div class="justify-start flex" id="mo2f_enable_magic_link_login_save"><div><button id="mo2f_enable_magic_link_login_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Prevent End Users from configuring their 2FA method at Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<?php $mo2f_disable_inline_2fa = get_site_option( 'mo2f_disable_inline_registration' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_disable_inline_2fa" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_disable_inline_2fa ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<p class="mo2f-setting-desc"><?php esc_html_e( 'Enabling this will not give end users the option to setup their 2FA method at the login time.', 'miniorange-2-factor-authentication' ); ?></p>
		</div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Allow End Users to choose any Configured 2FA Method for Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<?php $mo2f_mfa_login = get_site_option( 'mo2f_multi_factor_authentication' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_mfa_login" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_mfa_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<p class="mo2f-setting-desc"><?php esc_html_e( 'If the end users have mutliple 2FA method configured, enabling this option will allow them to login using any configured method otherwise they can login only using the latest configured 2FA method.', 'miniorange-2-factor-authentication' ); ?></p>
		</div>
	</div>
</div>

<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Security & Notifications', 'miniorange-2-factor-authentication' ); ?></h2>
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
jQuery("#settings").addClass("mo2f-subtab-active");
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
