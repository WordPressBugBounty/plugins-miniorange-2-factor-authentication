<?php
/**
 * Description: Shows Geo (Country) Blocking settings UI.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsConstants;

?>
<div class="mo2f-setting-row mo2f-basic-plan">
	<div class="mo2f-setting-main">
		<span class="mo2f-setting-title">
			<?php esc_html_e( 'Country/Region (Geo) Blocking', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		</span>
	</div>
	<div class="mo2f-setting-control">
		<label class="mo2f_checkbox_container">
			<input type="checkbox" id="mo2f_enable_geo_blocking" />
			<span class="mo2f-settings-checkmark"></span>
		</label>
		<p class="mo2f-setting-desc"><?php esc_html_e( 'Blocks login attempts based on the visitor\'s country. IPs on the existing Whitelist are never blocked, and if the country lookup ever fails the request is allowed through.', 'miniorange-2-factor-authentication' ); ?></p>
	</div>
</div>
<div class="mo2f-sub-settings-div mo2f-sub-panel mo2f-basic-plan flex" id="mo2f_enable_geo_blocking_settings">
	<div class="mo2f-settings-items">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_geo_blocking_mode" id="mo2f_geo_blocking_mode_block" value="block" checked>
			<?php esc_html_e( 'Block these countries', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	<br>
	<div class="mo2f-settings-items">
		<div class="mr-mo-4">
			<input type="radio" name="mo2f_geo_blocking_mode" id="mo2f_geo_blocking_mode_allow" value="allow">
			<?php esc_html_e( 'Allow only these countries', 'miniorange-2-factor-authentication' ); ?>
		</div>
	</div>
	<br>
	<div class="mt-mo-3">
		<label style="display:block;font-weight:500;margin-bottom:6px;"><?php esc_html_e( 'Countries', 'miniorange-2-factor-authentication' ); ?></label>
		<select multiple size="10" id="mo2f_geo_blocking_countries" class="mo2f-adv-set-input mo2f_width_80">
			<?php foreach ( Mo2f_Common_Helper::mo2f_get_country_list() as $mo2f_cc => $mo2f_cn ) : ?>
				<option value="<?php echo esc_attr( $mo2f_cc ); ?>"><?php echo esc_html( $mo2f_cn ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="justify-start" style="margin-top:14px;">
		<div class="mo2f-basic-plan">
		<button id="mo2f_geo_blocking_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
	<input type="hidden" id="mo2f_geo_blocking_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ); ?>" />
</div>
