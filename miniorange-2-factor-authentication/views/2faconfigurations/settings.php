<?php
/**
 * Description: This file is used to login settings.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_custom_redirect" onclick="mo2f_showSettings(this)" <?php checked( $enable_custom_redirect ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Custom Redirection URL After Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo $enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_settings">
			<div>	
				<table class="my-mo-3 w-5/6" id="mo2f_redirect_url_table">
					<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_all" <?php checked( 'redirect_all' === get_site_option( 'mo2f_redirect_url_for_users', 'redirect_all' ) ); ?>/><?php esc_html_e( 'Redirection URL for all users:', 'miniorange-2-factor-authentication' ); ?></div></td><td><input type="text" placeholder="Enter Redirect URL" class="mo2f-redirection-field" id="redirect_url_all" value="<?php echo get_option( 'mo2f_custom_redirect_url' ) ? esc_attr( get_option( 'mo2f_custom_redirect_url' ) ) : esc_url( home_url() ); ?>"></td></tr>
				</table>
			</div>
			<div class="relative mo2f-enterprise-plan">
				<table class="my-mo-3 w-3/4" id="mo2f_custom_redirect_url_table">
					<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_user_roles" <?php checked( 'redirect_user_roles' === get_site_option( 'mo2f_redirect_url_for_users' ) ); ?>/><?php esc_html_e( 'Redirection URL based on user roles:', 'miniorange-2-factor-authentication' ); ?><?php echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div></td><td></td></tr>	
			<?php

			foreach ( $custom_login_urls as $selected_role_name => $redirection_url ) {
				?>
				<tr id="<?php echo esc_attr( $selected_role_name ); ?>">
				<td><div class="mo2f_custom_redirections_roles"><select name="" class="ml-mo-4 w-1/2 text-center mo2f_redirect_url_roles">
				<?php
				foreach ( $wp_roles->role_names as $role_id => $role_name ) {
					?>
						<option value="<?php echo 'mo2fa_' . esc_attr( $role_id ); ?>" <?php echo ( 'mo2fa_' . esc_attr( $role_id ) === $selected_role_name ? 'selected' : '' ); ?>><?php echo esc_attr( $role_name ); ?></option>
				<?php } ?>
				</div></select></td><td><div class="mo2f_custom_redirections_urls"><input type="text" class="mo2f_redirection_url mo2f-redirection-field" placeholder="Enter Redirect URL" value="<?php echo esc_url( $redirection_url ); ?>"></td></div><td><button class="mo2f-basic-plan mo2f-add-row" id="mo2f_add_custom_redirect_url_<?php echo ( esc_attr( $role_id ) ); ?>">+</button></td><td><button class="mo2f-basic-plan mo2f-remove-row" id="mo2f_remove_custom_redirect_url">-</button></td>
					</tr>
				<?php
			}
			?>
			</table>
		</div>
			</div>
			<div class="justify-start <?php echo $enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_save"><div class="mo2f_enable_custom_redirect_save_button"><button id="mo2f_enable_custom_redirect_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

<div class="mo2f-settings-div">
<div class="mo2f-settings-head">
<?php $disable_inline_2fa = get_site_option( 'mo2f_disable_inline_registration' ); ?>
	<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_disable_inline_2fa" onclick="mo2f_showSettings(this)" <?php checked( $disable_inline_2fa ); ?>/><span class="mo2f-settings-checkmark"></span></label>
	<span><?php esc_html_e( 'Prevent End Users from configuring their 2FA method at Login', 'miniorange-2-factor-authentication' ); ?></span>
</div>
<br>
<div class="text-mo-tertiary-txt ml-mo-8" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s Enabling this checkbox will not give end users the option to setup their 2FA method at the login time.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
</div>

<div class="mo2f-settings-div">
<div class="mo2f-settings-head">
<?php $mo2f_mfa_login = get_site_option( 'mo2f_multi_factor_authentication' ); ?>
	<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_mfa_login" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_mfa_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
	<span><?php esc_html_e( 'Allow End Users to choose any Configured 2FA Method for Login', 'miniorange-2-factor-authentication' ); ?></span>
</div>
<br>
<div class="text-mo-tertiary-txt ml-mo-8" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s If the end users have mutliple 2FA method configured, enabling this option will allow them to login using any configured method otherwise they can login only using the latest configured 2FA method.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
</div>

<div class="mo2f-settings-div">
<div class="mo2f-settings-head">
<?php $notify_admin_unusual_activity = get_site_option( 'mo_wpns_enable_unusual_activity_email_to_user' ); ?>
	<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_new_ip_login_notification" onclick="mo2f_enable_new_ip_notification()" <?php checked( $notify_admin_unusual_activity ); ?>/><span class="mo2f-settings-checkmark"></span></label>
	<span><?php esc_html_e( 'New IP Login Notification', 'miniorange-2-factor-authentication' ); ?></span>
	&nbsp;&nbsp;(<a  href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_white_labelling&subpage=emailtemplates#mo2f_2fa_new_ip_detected_email_subject' ) ); ?>" style="cursor:pointer" target="_blank"><?php esc_html_e( 'Customize Email Template', 'miniorange-2-factor-authentication' ); ?></a>)
</div>
<br>
<div class="text-mo-tertiary-txt ml-mo-8" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s Enabling this option will send end users a notification on their email whenever they log into the site using new IP.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
</div>
<script>
jQuery("#settings").addClass("mo2f-subtab-active");
jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
</script>
<?php
	global $main_dir;
	wp_enqueue_script( 'login-settings-script', $main_dir . '/includes/js/login-settings.min.js', array(), MO2F_VERSION, false );
	wp_localize_script(
		'login-settings-script',
		'loginSettings',
		array(
			'nonce' => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
		)
	);
