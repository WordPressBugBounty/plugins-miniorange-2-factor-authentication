<?php
/**
 * This file shows the Login Experience settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div mo2f-section">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Login Experience', 'miniorange-2-factor-authentication' ); ?></h2>
		<p><?php esc_html_e( "What the login screen looks like and does, beyond entering a password.", 'miniorange-2-factor-authentication' ); ?></p>
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

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Enable Backup Login Methods', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<?php $mo2f_enable_backup_login = get_site_option( 'mo2f_enable_backup_methods' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_backup_methods" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_backup_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		</div>
	</div>
	<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo $mo2f_enable_backup_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_backup_methods_settings">
		<div class="flex px-mo-4 text-mo-title">
			<?php $mo2f_enabled_backup_methods = (array) get_site_option( 'mo2f_enabled_backup_methods' ); ?>
			<div class="my-mo-3 mr-mo-4" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_enabled_backup_method" value="mo2f_back_up_codes" <?php echo in_array( 'mo2f_back_up_codes', $mo2f_enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Backup Codes', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="my-mo-3 mr-mo-4" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_enabled_backup_method" value="mo2f_reconfig_link_show" onclick="mo2f_toggle_subsetting(this,'mo2f_reconfig_link_roles_settings')" <?php echo in_array( 'mo2f_reconfig_link_show', $mo2f_enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Account Recovery Via Email Verification', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="my-mo-3 mr-mo-4 mo2f-basic-plan" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_enabled_backup_method" value="backup_kba" <?php echo in_array( 'backup_kba', $mo2f_enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?>
				<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
			</div>
		</div>
		<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo in_array( 'mo2f_reconfig_link_show', $mo2f_enabled_backup_methods, true ) ? 'flex' : 'hidden'; ?>" id="mo2f_reconfig_link_roles_settings">
			<div class="my-mo-3"><?php esc_html_e( 'Allow "Account Recovery Via Email Verification" for these roles:', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mo2f-settings-items">
				<?php
				$mo2f_reconfig_excluded_roles = (array) get_site_option( 'mo2f_reconfig_link_excluded_roles', array() );
				foreach ( $wp_roles->role_names as $mo2f_rl_role_id => $mo2f_rl_role_name ) :
					?>
					<div class="my-mo-3 mr-mo-4" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_reconfig_link_allowed_role" value="<?php echo esc_attr( $mo2f_rl_role_id ); ?>" <?php echo ! in_array( $mo2f_rl_role_id, $mo2f_reconfig_excluded_roles, true ) ? 'checked' : ''; ?>/><?php echo esc_html( $mo2f_rl_role_name ); ?></div>
					<?php
				endforeach;
				?>
			</div>
			<div class="my-mo-3" style="display:inline-flex;align-items:center;white-space:nowrap;">
				<input type="checkbox" id="mo2f_enable_self_service_reset_admin_alert" <?php checked( get_site_option( 'mo2f_enable_self_service_reset_admin_alert', 1 ) ); ?>/>
				<label for="mo2f_enable_self_service_reset_admin_alert"><?php esc_html_e( 'Email the site admin whenever a user resets their own 2FA this way', 'miniorange-2-factor-authentication' ); ?></label>
			</div>
		</div>
	</div>
	<div class="justify-start mo2f-sub-panel <?php echo $mo2f_enable_backup_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_backup_methods_save"><div class="mo2f_enable_backup_methods_save_button"><button id="mo2f_enable_backup_methods_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
	</div>
	</div>

	<div class="mo2f-setting-row">
		<div class="mo2f-setting-main">
			<span class="mo2f-setting-title"><?php esc_html_e( 'Rate Limiting', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-setting-control">
			<p class="mo2f-setting-desc"><?php esc_html_e( 'Built in and always on, no configuration needed. The plugin automatically throttles every email it sends on request, so none of these can be used to spam a user\'s inbox or exhaust the site\'s email quota:', 'miniorange-2-factor-authentication' ); ?></p>
			<ul class="mo2f-rate-limit-list">
				<li><?php esc_html_e( 'OTP over Email, Backup Codes, and Account Recovery Link: max 1 request every 15 seconds and 5 per hour, per user, IP, and recipient address, plus a 200-per-day site-wide cap.', 'miniorange-2-factor-authentication' ); ?></li>
				<li><?php esc_html_e( 'Magic Link Login: max 5 requests every 10 minutes, per IP address.', 'miniorange-2-factor-authentication' ); ?></li>
			</ul>
		</div>
	</div>
</div>
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
