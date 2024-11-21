<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$overlay_on_premium_features = MO2F_PREMIUM_PLAN ? '' : 'mo2f-premium-feature';
$crown                       = MO2F_PREMIUM_PLAN ? '' : '<svg width="18" class="ml-mo-4 -mb-mo-0.5" height="18" viewBox="0 0 24 24" fill="none">
<g id="d4a43e0162b45f718f49244b403ea8f4">
	<g id="4ea4c3dca364b4cff4fba75ac98abb38">
		<g id="2413972edc07f152c2356073861cb269">
			<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
		</g>
	</g>
</g>
</svg>';
if ( current_user_can( 'administrator' ) ) {
	?>
	<div id="wpns_nav_message"></div>
	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<?php $enable_2fa = MoWpnsUtility::get_mo2f_db_option( 'mo2f_activate_plugin', 'get_option' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable2FA" onclick="mo2f_showSettings(this)" <?php checked( $enable_2fa ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div flex" id="mo2f_enable2FA_settings">
			<div class="mb-mo-3">
				<div class="my-mo-3"><?php esc_html_e( 'Enable 2FA for roles', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-settings-items">
					<?php
					foreach ( $wp_roles->role_names as $role_id => $role_name ) {
						?>
					<div class="mr-mo-4"><input type="checkbox" name="role" value="<?php echo 'mo2fa_' . esc_attr( $role_id ); ?>" 
																						<?php

																						if ( get_option( 'mo2fa_' . $role_id ) ) {
																							echo 'checked';
																						} else {
																							echo 'unchecked';
																						}
																						?>
					/><?php echo esc_attr( $role_name ); ?></div>
					<?php } ?>
				</div>
			</div>
			<div class="relative mb-mo-3 <?php echo esc_attr( $overlay_on_premium_features ); ?>">
				<div class="my-mo-3"><?php esc_html_e( 'Enable 2FA for specific users', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
				<div class="mo2f-settings-items"><?php esc_html_e( 'Click', 'miniorange-2-factor-authentication' ); ?>&emsp13;<a href="#gotouserspage"><?php esc_html_e( 'here', 'miniorange-2-factor-authentication' ); ?></a>&emsp13;<?php esc_html_e( 'to enable/disable 2FA for your users.', 'miniorange-2-factor-authentication' ); ?></div>
			</div>
		</div>
		<div class="justify-start flex" id="mo2f_enable2FA_save"><div class="mo2f_enable2FA_save_button"><button id="mo2f_enable2FA_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<?php $enable_backup_login = get_site_option( 'mo2f_enable_backup_methods' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_backup_methods" onclick="mo2f_showSettings(this)" <?php checked( $enable_backup_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Backup Login Methods', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo $enable_backup_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_backup_methods_settings">
			<div class="flex px-mo-4 text-mo-title">
				<?php $enabled_backup_methods = (array) get_site_option( 'mo2f_enabled_backup_methods' ); ?>
				<div class="my-mo-3 mr-mo-4"><input type="checkbox" name="mo2f_enabled_backup_method" value="mo2f_back_up_codes" <?php echo in_array( 'mo2f_back_up_codes', $enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Backup Codes', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="my-mo-3 mr-mo-4"><input type="checkbox" name="mo2f_enabled_backup_method" value="mo2f_reconfig_link_show" <?php echo in_array( 'mo2f_reconfig_link_show', $enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Reconfiguration Link', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="my-mo-3 mr-mo-4 <?php echo esc_attr( $overlay_on_premium_features ); ?>"><input type="checkbox" name="mo2f_enabled_backup_method" value="backup_kba" <?php echo in_array( 'backup_kba', $enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
			</div>
		</div>
		<div class="justify-start <?php echo $enable_backup_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_backup_methods_save"><div class="mo2f_enable_backup_methods_save_button"><button id="mo2f_enable_backup_methods_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<?php $enable_custom_redirect = get_option( 'mo2f_enable_custom_redirect' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_custom_redirect" onclick="mo2f_showSettings(this)" <?php checked( $enable_custom_redirect ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Custom Redirection URL After Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo $enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_settings">
			<div>	
				<table class="my-mo-3 w-5/6" id="mo2f_redirect_url_table">
					<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_all" <?php checked( 'redirect_all' === get_site_option( 'mo2f_redirect_url_for_users' ) ); ?>/><?php esc_html_e( 'Redirect URL for all Users:', 'miniorange-2-factor-authentication' ); ?></div></td><td><input type="text" placeholder="Enter Redirect URL" id="redirect_url_all" value="<?php echo get_option( 'mo2f_custom_redirect_url' ) ? esc_attr( get_option( 'mo2f_custom_redirect_url' ) ) : esc_url( home_url() ); ?>"></td></tr>
				</table>
			</div>
			<div class="relative  <?php echo esc_attr( $overlay_on_premium_features ); ?>">
				<table class="my-mo-3 w-3/4" id="mo2f_redirect_url_table">
					<tr><td><div class="my-mo-3"><input type="radio" name="mo2f_redirect_url_for_users" value="redirect_user_roles" <?php checked( 'redirect_user_roles' === get_option( 'mo2f_redirect_url_for_users' ) ); ?>/><?php esc_html_e( 'Redirect URL based on user roles:', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div></td><td></td></tr>
					<tr><td><select name="" id="redirect_url_roles" class="ml-mo-4 w-1/2 text-center">
			<?php
			foreach ( $wp_roles->role_names as $role_id => $role_name ) {
				?>
						<option value="<?php echo 'mo2fa_' . esc_attr( $role_id ); ?>"><?php echo esc_attr( $role_name ); ?></option>
				<?php } ?>
					</select></td><td><input type="text" placeholder="Enter Redirect URL" value="<?php echo get_option( 'mo2f_custom_redirect_url' ) ? esc_attr( get_option( 'mo2f_custom_redirect_url' ) ) : esc_url( home_url() ); ?>"></td><td><button class="mo2f-add-url-button" id="mo2f_add_custom_redirect_url">+</button></td></tr>
				</table>
		</div>
			</div>
			<div class="justify-start <?php echo $enable_custom_redirect ? 'flex' : 'hidden'; ?>" id="mo2f_enable_custom_redirect_save"><div class="mo2f_enable_custom_redirect_save_button"><button id="mo2f_enable_custom_redirect_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<?php $enable_grace_period = MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'site_option' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_graceperiod" onclick="mo2f_showSettings(this)" <?php checked( $enable_grace_period ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Grace Period', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo $enable_grace_period ? 'flex' : 'hidden'; ?>" id="mo2f_enable_graceperiod_settings">
				<div class="my-mo-3"><?php esc_html_e( 'Provide users a Grace Period to configure 2FA', 'miniorange-2-factor-authentication' ); ?></div>
				<div id="mo2f_grace_period_show" class="mo2f-settings-items items-center">
					<div class="mr-mo-4"><input type="number" name="" id="mo2f_grace_period" class="mo2f-settings-number-field" name= "mo2f_grace_period_value" value="<?php echo esc_attr( get_site_option( 'mo2f_grace_period_value', 1 ) ); ?>" min=0></div>				  
					<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_type" class="mt-mo-2" id="mo2f_grace_hour" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'get_option' ) === 'hours' ); ?>  value="hours"/>Hours</div>
					<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_type" class="mt-mo-2" id="mo2f_grace_day" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'get_option' ) === 'days' ); ?> value="days"/>Days</div>			
					</br>	
				</div>
				<div class="mb-mo-3">
				<div class="my-mo-3"><?php esc_html_e( 'Action after grace period is expired', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-settings-items">	
				<div class="mr-mo-4"><input type="radio" name="mo2f_grace_period_action" id="mo2f_enforce_2fa" value="enforce_2fa" <?php checked( get_site_option( 'mo2f_graceperiod_action' ) === 'enforce_2fa' ); ?>><?php esc_html_e( 'Enforce 2FA', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mr-mo-4"><input type="radio" name="mo2f_grace_period_action" id="mo2f_block_users" value="block_user_login" <?php checked( get_site_option( 'mo2f_graceperiod_action' ) === 'block_user_login' ); ?>><?php esc_html_e( 'Block users from login', 'miniorange-2-factor-authentication' ); ?></div>
				</div>
			</div>
		</div>
		<div class="justify-start <?php echo $enable_grace_period ? 'flex' : 'hidden'; ?>" id="mo2f_enable_graceperiod_save"><div><button id="mo2f_enable_graceperiod_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_select_methods" onclick="mo2f_showSettings(this)"/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Select Specific Set of Authentication Methods for Users', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div hidden  <?php echo esc_attr( $overlay_on_premium_features ); ?>" id="mo2f_select_methods_settings">
		<div class="my-mo-3"><input type="radio" name="mo2f_methods_for_users" id="2fa_methods_for_all" checked><?php esc_html_e( '2FA Methods For All', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
			<div class="mo2f-settings-items">
				<?php
				foreach ( $mo2f_methods_on_dashboard as $method ) {
					?>
				<div class="mr-mo-4"><input type="checkbox" id="<?php echo 'mo2fa_' . esc_attr( $method ); ?>" checked><?php echo esc_attr( $mo2f_method_names[ $method ] ); ?></div>
				<?php } ?>
			</div>
			</br>
		<div class="my-mo-3"><input type="radio" name="mo2f_methods_for_users" id="2fa_methods_for_roles"><?php esc_html_e( '2FA Methods For User Roles', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
			<div class="mo2f-settings-items flex-col">
			<?php
			foreach ( $wp_roles->role_names as $role_id => $role_name ) {
				?>
				<div class="my-mo-3"><input type="checkbox" name="mo2f_methods_for_roles" onclick="mo2f_showSettings(this)" id="<?php echo 'mo2fa_' . esc_attr( $role_id ); ?>" <?php echo 'administrator' === $role_id ? 'checked' : ''; ?>/><?php echo esc_html( $role_name ); ?></div>
				<div class="mo2f-sub-settings-items <?php echo 'administrator' === $role_id ? 'flex' : 'hidden'; ?>" id="<?php echo 'mo2fa_' . esc_attr( $role_id ) . '_settings'; ?>">
				<?php
				foreach ( $mo2f_methods_on_dashboard as $method ) {
					?>
				<div class="mr-mo-4"><input type="checkbox" id="<?php echo 'mo2fa_' . esc_attr( $method ); ?>" checked><?php echo esc_html( $mo2f_method_names[ $method ] ); ?></div>
				<?php } ?>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="justify-start hidden" id="mo2f_select_methods_save">
			<div class=" <?php echo esc_attr( $overlay_on_premium_features ); ?>">
				<button class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
			</div>
		</div>

	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
		<?php $disable_inline_2fa = get_site_option( 'mo2f_disable_inline_registration' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_disable_inline_2fa" onclick="mo2f_showSettings(this)" <?php checked( $disable_inline_2fa ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Prevent 2FA Configuration on Login', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
		<?php $mo2f_mfa_login = get_site_option( 'mo2f_multi_factor_authentication' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_mfa_login" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_mfa_login ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Provide Option to Users to Login with any Configured Method', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
	</div>

	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
		<?php $mo2f_enable_shortcodes = get_option( 'mo2f_enable_shortcodes' ); ?>
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_shortcodes" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_shortcodes ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Shortcodes to Provide Option to Users to Enable 2FA and Configure 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo $mo2f_enable_shortcodes ? 'flex' : 'hidden'; ?>  <?php echo esc_attr( $overlay_on_premium_features ); ?>" id="mo2f_enable_shortcodes_settings">
		<div class="my-mo-3">[mo2f_enable_2fa]: <?php esc_html_e( 'Shortcode to add a checkbox to enable 2FA on a custome page.', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
		<div class="my-mo-3">[mo2f_reconfigure_2fa]: <?php esc_html_e( 'Shortcode to add functionality to configure 2FA on a custome page.', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></div>
		</div>
	</div>
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
}
?>
</div>
