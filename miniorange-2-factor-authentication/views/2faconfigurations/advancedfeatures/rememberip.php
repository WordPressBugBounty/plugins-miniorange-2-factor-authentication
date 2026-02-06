<?php
/**
 * Description: Shows remember IP settings UI.
 *
 * @package miniorange-2-factor-authentication/views/advancedsettings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsConstants;

?>
<div class="mo2f-settings-div mo2f-all-inclusive-plan">
	<div class="mo2f-settings-head">
		<span><?php esc_html_e( 'Remember (Whitelist) IP to Bypass 2FA', 'miniorange-2-factor-authentication' ); ?></span>
					<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'all-inclusive', MoWpnsConstants::MO2F_PREMIUM_1PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

	</div>
	<br>
	<div class="ml-mo-16">
		<label class="mo2f_checkbox_container">
			<input type="checkbox" id="mo2f_remember_ip_feature" <?php checked( $mo2f_configurations['mo2f_remember_ip_feature'] ); ?>/>
		</label>
		<span>
		<?php
			printf(
				/* Translators: %s: bold tags */
				esc_html( __( 'Enable %1$1s\'Remember IP\'%2$2s Option', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
				'<b>',
				'</b>',
			);
			?>
		</span>
		<br><br>
	</div>
	<div class="text-mo-tertiary-txt ml-mo-22"> 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s This will allow the users to directly login to the site using username and password if their IP address is remembered ( whitelisted ) otherwise they would be asked for the 2-factor authentication.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
	</div>
	<br>
	<div id="mo2f-remeber-ip-content">
		<div class="mo2f-settings-items ml-mo-20">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_give_rem_ip_choice" id="mo2f_give_rem_ip_give_choice" value="1" <?php checked( $mo2f_configurations['mo2f_give_rem_ip_choice'] ); ?>>
				<?php
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( 'Give users an option to %1$1s\'Remember (Whitelist)\'%2$2s their IP', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
					);
					?>
			</div>
		</div>
		<br>
		<div class="mo2f-settings-items ml-mo-20">
			<div class="mr-mo-4">
				<input type="radio" name="mo2f_give_rem_ip_choice" id="mo2f_give_rem_ip_no_choice" value="0" <?php checked( '0' === $mo2f_configurations['mo2f_give_rem_ip_choice'] ); ?>>
				<?php
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( 'Add IPs for %1$1s\'Whitelisting\'%2$2s', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
					);
					?>
			</div>
		</div>
		<br>
		<div id="mo2f-remember-ip-range-content">
			<div class="mo2f-settings-items ml-mo-30" id="mo2f_range_of_ips_block" >
				<form name="mo2f-remember-ip-form" method="post" action="" id="mo2f-remember-ip-form" >
					<p> 
						<label class="mo2f_checkbox_container">
							<input type="checkbox" id="mo2f_remember_ip_list" <?php checked( $mo2f_configurations['mo2f_enable_ip_list'] ); ?>/>
						</label><?php esc_html_e( 'Add the IPs you want to whitelist for 2FA', 'miniorange-2-factor-authentication' ); ?> 
					</p>
					<div class="mo2f-settings-items mo-input-wrapper ml-mo-4">
						<label class="mo-input-label"><?php esc_html_e( 'Add IPs', 'miniorange-2-factor-authentication' ); ?></label>
						<input type ="text" class="mo2f-adv-set-input mo2f_width_80" name="mo2f_2fa_whitelist_ip_list" value ="<?php echo esc_attr( isset( $mo2f_configurations['mo2f_2fa_whitelist_ip_list'] ) ? $mo2f_configurations['mo2f_2fa_whitelist_ip_list'] : '' ); ?>" placeholder="e.g 192.168.0.100,192.168.0.190,192.168.0.195" />
					</div>
					<br>
					<p>
						<label class="mo2f_checkbox_container">
							<input type="checkbox" id="mo2f_remember_ip_range" <?php checked( $mo2f_configurations['mo2f_enable_ip_range'] ); ?>/>
						</label><?php esc_html_e( 'Add range of IPs to whitelist', 'miniorange-2-factor-authentication' ); ?> 
					</p>		
					<div class="mo2f-settings-items ml-mo-32">
						<table>
							<?php
							foreach ( $mo2f_rem_ip_ranges as $mo2f_index => $mo2f_rem_ip_range ) {
								?>
							<tr>
								<td>
									<div class="mo2f-settings-items mo-input-wrapper mo2f-start-ips mb-mo-4">
										<label class="mo-input-label"><?php esc_html_e( 'Start IP', 'miniorange-2-factor-authentication' ); ?></label>
										<input type="text"  class="mo2f-adv-set-input mo2f_width_80 mo2f-start-ip-inputs" value ="<?php echo esc_attr( is_array( $mo2f_rem_ip_range ) && isset( $mo2f_rem_ip_range[0] ) ? $mo2f_rem_ip_range[0] : '' ); ?>" placeholder=" e.g 192.168.0.100" />
									</div>
								</td>
								<td>
									<div class="mo2f-settings-items mo-input-wrapper mo2f-end-ips mb-mo-4">
										<label class="mo-input-label"><?php esc_html_e( 'End IP', 'miniorange-2-factor-authentication' ); ?></label>
										<input type="text" class="mo2f-adv-set-input mo2f_width_80 mo2f-end-ip-inputs" value="<?php echo esc_attr( is_array( $mo2f_rem_ip_range ) && isset( $mo2f_rem_ip_range[1] ) ? $mo2f_rem_ip_range[1] : '' ); ?>"  placeholder=" e.g 192.168.0.190" />
									</div>
								</td>
								<td>
								<div class="mo2f-settings-items mb-mo-4">
									<button class="mo2f-all-inclusive-plan mo2f-add-rem-ip-row ml-mo-4">+</button>
									<button class="mo2f-all-inclusive-plan mo2f-remove-rem-ip-row ml-mo-4">-</button>
								</div>
								</td>
							</tr>
								<?php
							}
							?>
						</table>
					</div>
				</form>
			</div>	
		</div>	
	</div>	
	<br>
	<div class="justify-start ml-mo-16">
		<div class="mo2f-enterprise-plan">
		<button id="mo2f_remember_ip_settings" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
	</div>
</div>
