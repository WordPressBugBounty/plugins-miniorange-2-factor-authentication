<?php
/**
 * Description: Shows login-hardening settings (XML-RPC, CAPTCHA) on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations/advancedfeatures
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-advfeature-group">
<div class="mo2f-setting-row">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title"><?php esc_html_e( 'Disable XML-RPC', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_xmlrpc" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_xmlrpc ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		<p class="mo2f-setting-desc"><?php esc_html_e( "XML-RPC (xmlrpc.php) is a common brute-force and DDoS-amplification target. Disable it if you don't rely on the WordPress mobile app, Jetpack, or another XML-RPC based integration.", 'miniorange-2-factor-authentication' ); ?></p>
	</div>
</div>
</div>

<div class="mo2f-advfeature-group">
<div class="mo2f-setting-row">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title"><?php esc_html_e( 'Add CAPTCHA to the Login Screen', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_captcha" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_captcha ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		<p class="mo2f-setting-desc"><?php esc_html_e( "Shows a CAPTCHA challenge on wp-login.php to slow down automated brute-force login attempts. Note: this check also applies to the authenticate step used by XML-RPC and any other integration that logs a user in with a username and password -- an app that can't present a CAPTCHA (e.g. the WordPress mobile app, Jetpack) won't be able to log in while this is on.", 'miniorange-2-factor-authentication' ); ?></p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo esc_attr( $mo2f_enable_captcha ? 'flex' : 'hidden' ); ?>" id="mo2f_enable_captcha_settings">
	<div class="mb-mo-3">
		<div class="my-mo-3"><?php esc_html_e( 'CAPTCHA Provider', 'miniorange-2-factor-authentication' ); ?></div>
		<div class="mo2f-settings-items">
			<div class="mr-mo-4"><input type="radio" name="mo2f_captcha_provider" id="mo2f_captcha_provider_recaptcha" value="recaptcha" <?php checked( 'recaptcha' === $mo2f_captcha_provider ); ?>/><?php esc_html_e( 'Google reCAPTCHA v2', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mr-mo-4"><input type="radio" name="mo2f_captcha_provider" id="mo2f_captcha_provider_turnstile" value="turnstile" <?php checked( 'turnstile' === $mo2f_captcha_provider ); ?>/><?php esc_html_e( 'Cloudflare Turnstile', 'miniorange-2-factor-authentication' ); ?></div>
		</div>
	</div>
	<div class="mb-mo-3">
		<label for="mo2f_captcha_site_key"><?php esc_html_e( 'Site Key', 'miniorange-2-factor-authentication' ); ?></label><br/>
		<input type="text" id="mo2f_captcha_site_key" class="mo2f-redirection-field" value="<?php echo esc_attr( $mo2f_captcha_site_key ); ?>"/>
	</div>
	<div class="mb-mo-3">
		<label for="mo2f_captcha_secret_key"><?php esc_html_e( 'Secret Key', 'miniorange-2-factor-authentication' ); ?></label><br/>
		<input type="password" id="mo2f_captcha_secret_key" class="mo2f-redirection-field" autocomplete="off" value="<?php echo esc_attr( $mo2f_captcha_secret_key ); ?>"/>
	</div>
	<div class="justify-start flex" id="mo2f_enable_captcha_save"><div><button id="mo2f_enable_captcha_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
</div>
</div>

<div class="mo2f-advfeature-group">
<div class="mo2f-setting-row">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title"><?php esc_html_e( 'Automatically Block IPs After Repeated Failed Logins', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_auto_lockout" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_lockout_settings['enabled'] ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		<p class="mo2f-setting-desc"><?php esc_html_e( "Blocks an IP address after too many failed login attempts in a short time. Whitelisted IPs are never counted or blocked -- if you don't want to risk locking yourself out, add your own IP to the whitelist first.", 'miniorange-2-factor-authentication' ); ?></p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo esc_attr( $mo2f_lockout_settings['enabled'] ? 'flex' : 'hidden' ); ?>" id="mo2f_enable_auto_lockout_settings">
	<?php
	$mo2f_lockout_fields = array(
		array(
			'label' => __( 'Failed attempts before blocking:', 'miniorange-2-factor-authentication' ),
			'name'  => 'mo2f_lockout_max_attempts',
			'value' => $mo2f_lockout_settings['max_attempts'],
			'min'   => 1,
		),
		array(
			'label' => __( 'Time period to count attempts (in seconds):', 'miniorange-2-factor-authentication' ),
			'name'  => 'mo2f_lockout_time_window',
			'value' => $mo2f_lockout_settings['time_window'],
			'min'   => 10,
		),
		array(
			'label' => __( 'Block duration after max attempts (in seconds):', 'miniorange-2-factor-authentication' ),
			'name'  => 'mo2f_lockout_duration',
			'value' => $mo2f_lockout_settings['lockout_time'],
			'min'   => 10,
		),
	);
	foreach ( $mo2f_lockout_fields as $mo2f_field ) :
		?>
		<div class="mb-mo-3">
			<span><?php echo esc_html( $mo2f_field['label'] ); ?></span><br/>
			<input type="number" class="mo2f-redirection-field" name="<?php echo esc_attr( $mo2f_field['name'] ); ?>" value="<?php echo esc_attr( $mo2f_field['value'] ); ?>" min="<?php echo esc_attr( $mo2f_field['min'] ); ?>">
		</div>
	<?php endforeach; ?>
	<div class="justify-start flex" id="mo2f_enable_auto_lockout_save"><div><button id="mo2f_auto_lockout_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
</div>
</div>
