<?php
/**
 * Description: This file contains addon settings UI.
 *
 * @package miniorange-2fa-page-protection-addon/views
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div mo2f-all-inclusive-plan">
	<div class="mo2f-settings-head">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_page_protection" <?php checked( $mo2f_settings_status ); ?>/><span class="mo2f-settings-checkmark"></span></label>
		<span><?php esc_html_e( 'Enable 2FA On Specific Pages', 'miniorange-2-factor-authentication' ); ?></span>
		<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'all-inclusive', MoWpnsConstants::MO2F_PREMIUM_1PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
	</div>
	<div class="mo2f-sub-settings-div flex" id="mo2f_2fa_page_protection_settings">
		<div class="mb-mo-3">
			<div class="my-mo-3"><?php esc_html_e( 'Select Pages For 2FA', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mr-mo-4 mo2f-settings-items mo2f-sub-settings-div mo2f_table_styling">
				<table>
				<tr>
					<td>
						<input type="checkbox" id="mo2f_select_all_pages" onclick="selectAllPages(this)" 
							<?php
							$mo2f_select_all_checked = true;
							foreach ( $mo2f_pages as $mo2f_page ) {
								if ( ! in_array( $mo2f_page->post_name, $mo2f_enabled_pages, true ) ) {
									$mo2f_select_all_checked = false;
									break;
								}
							}
							echo $mo2f_select_all_checked ? 'checked' : '';
							?>
							/>
						<label for="mo2f_select_all_pages"><?php esc_html_e( 'Select All Pages', 'miniorange-2-factor-authentication' ); ?></label>
					</td>
					<?php
					$mo2f_counter = 1;
					foreach ( $mo2f_pages as $mo2f_page ) {
						if ( $mo2f_counter > 0 && 0 === $mo2f_counter % 5 ) {
							echo '</tr><tr>';
						}
						?>
					<td>
						<input type="checkbox" name="page" class="role-checkbox" id="mo2f_page_checkbox" value="<?php echo esc_attr( $mo2f_page->post_name ); ?>" 
							<?php echo in_array( $mo2f_page->post_name, $mo2f_enabled_pages, true ) ? 'checked' : ''; ?>
							onclick="updateSelectAllPages(this)" />
						<?php echo esc_attr( $mo2f_page->post_title ); ?>
					</td>
						<?php
						++$mo2f_counter;
					}
					?>
				</tr>
				</table>
			
			</div>
		</div>
		<div id="mo2f_page_protection_session_expiry_time_content">
			<span>
			<?php
				esc_html_e( 'Enter the time after which the user should be authenticated:', 'miniorange-2-factor-authentication' );
			?>
			</span>
			<input type="number" class="mo2f-settings-number-field" name="mo2f_page_protection_session_time" value="<?php echo esc_attr( $mo2f_session_time ); ?>" min=0 max=336>
			<span>
			<?php
				esc_html_e( 'Hours', 'miniorange-2-factor-authentication' );
			?>
			</span>
			<br>
		</div>
		<br>
	</div>
	<div class="justify-start" id="mo2f_page_protection_save">
		<div class="mo2f_save_2fa_page_protection_settings">
			<button id="mo2f_save_2fa_page_protection_settings" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?>
			</button>
		</div>
	</div>
</div>

</div>
