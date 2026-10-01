<?php
/**
 * This file shows the plugin settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( current_user_can( 'manage_options' ) ) {
	?>
	<div id="wpns_nav_message"></div>
	<?php if ( ! empty( $mo2f_free_plan_seats_full ) ) : ?>
		<div class="notice notice-warning inline mo2f-free-plan-limit-notice MOWrn" style="margin:0 0 16px;padding:12px 14px;">
			<p style="margin:0;"><?php echo wp_kses_post( MoWpnsMessages::mo2f_user_limit_exceeded_message_html() ); ?></p>
		</div>
	<?php endif; ?>

	<div class="mo2f-settings-div mo2f-section">
		<div class="mo2f-section-head">
			<h2><?php esc_html_e( 'Two-Factor Authentication', 'miniorange-2-factor-authentication' ); ?></h2>
		</div>

		<div class="mo2f-setting-row">
			<div class="mo2f-setting-main">
				<span class="mo2f-setting-title"><?php esc_html_e( 'Enable 2FA', 'miniorange-2-factor-authentication' ); ?></span>
			</div>
			<div class="mo2f-setting-control">
				<?php $mo2f_enable_2fa = MoWpnsUtility::get_mo2f_db_option( 'mo2f_activate_plugin', 'site_option' ); ?>
				<label class="mo2f_checkbox_container">
				<input type="checkbox" id="mo2f_enable2FA" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_2fa ); ?>/>
				<span class="mo2f-settings-checkmark"></span>
				</label>
			</div>
		</div>

		<div class="mo2f-sub-settings-div mo2f-sub-panel flex" id="mo2f_enable2FA_settings">
			<div class="mb-mo-3">
				<div class="my-mo-3"><?php esc_html_e( 'Please select the user roles for which you would like to enable Two-Factor Authentication (2FA)', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mb-mo-3">
					<label for="mo2f_role_search" class="mo2f-role-search-label"><?php esc_html_e( 'Role Filter', 'miniorange-2-factor-authentication' ); ?></label>
					<input type="text" id="mo2f_role_search" class="mo2f-role-search-input" placeholder="<?php esc_attr_e( 'Search roles...', 'miniorange-2-factor-authentication' ); ?>" />
					</div>
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<div class="mb-mo-3 mo2f-wc-staff-preset">
						<button type="button" id="mo2f_wc_staff_preset_button" class="mo2f-secondary-button" onclick="mo2f_apply_woocommerce_staff_preset()"><?php esc_html_e( 'Protect Staff, Not Shoppers (WooCommerce)', 'miniorange-2-factor-authentication' ); ?></button>
						<p class="description"><?php esc_html_e( 'Requires 2FA for Administrator, Editor, Author, and Shop Manager, and leaves Customer unchecked so checkout stays frictionless. Review the selection below, then click Save Settings.', 'miniorange-2-factor-authentication' ); ?></p>
					</div>
					<?php endif; ?>
				<div class="mo2f-role-list">
					<?php
						$mo2f_counter            = 0;
						$mo2f_select_all_checked = true;
					foreach ( $wp_roles->role_names as $mo2f_role_id => $mo2f_role_name ) {
						if ( ! get_site_option( 'mo2fa_' . $mo2f_role_id ) ) {
							$mo2f_select_all_checked = false;
							break;
						}
					}
					?>
					<div class="mo2f-enable-2fa-roles mo2f-role-list-item <?php echo $mo2f_select_all_checked ? 'mo2f-active-2' : ''; ?>">
						<input type="checkbox" id="mo2f_select_all_roles" onclick="selectAllRoles(this)"
							<?php echo $mo2f_select_all_checked ? 'checked' : ''; ?>
							/>
						<label for="mo2f_select_all_roles"><b><?php esc_html_e( 'Select All Roles', 'miniorange-2-factor-authentication' ); ?></b></label>
					</div>
					<?php
						++$mo2f_counter;
					foreach ( $wp_roles->role_names as $mo2f_role_id => $mo2f_role_name ) {
						?>
					<div class="mo2f-enable-2fa-roles mo2f-role-list-item <?php echo get_site_option( 'mo2fa_' . $mo2f_role_id ) ? 'mo2f-active-2' : ''; ?>" data-role-name="<?php echo esc_attr( strtolower( $mo2f_role_name ) ); ?>">
						<input type="checkbox" name="role" class="role-checkbox" id="mo2f_role_checkbox" value="<?php echo 'mo2fa_' . esc_attr( $mo2f_role_id ); ?>"
							<?php echo get_site_option( 'mo2fa_' . $mo2f_role_id ) ? 'checked' : ''; ?>
							onclick="updateSelectAll(this)" />
						<label><?php echo esc_attr( $mo2f_role_name ); ?></label>
					</div>
						<?php
						++$mo2f_counter;
					}
					?>
				</div>
				<?php
				$mo2f_admin_bypass_2fa = (int) get_site_option( 'mo2f_admin_login_bypass_2fa' );
				?>
				<div class="mo2f-quicksetup-bypass">
					<div class="mo2f-enable-2fa-roles mo2f-quicksetup-bypass__pill<?php echo $mo2f_admin_bypass_2fa ? ' mo2f-active-2' : ''; ?>">
						<input type="checkbox" class="role-checkbox" id="mo2f_admin_bypass_2fa_login" onchange="this.closest('.mo2f-quicksetup-bypass__pill').classList.toggle('mo2f-active-2', this.checked);" <?php checked( $mo2f_admin_bypass_2fa ); ?> />
						<label for="mo2f_admin_bypass_2fa_login"><?php esc_html_e( 'Allow administrators to skip 2FA from the login screen if needed', 'miniorange-2-factor-authentication' ); ?></label>
					</div>
					<p class="mo2f-quicksetup-bypass__hint"><?php esc_html_e( 'When enabled, administrators still complete the usual 2FA step, but the login screen adds a recovery link to finish without 2FA if they have trouble.', 'miniorange-2-factor-authentication' ); ?></p>
				</div>
			</div>
			<div class="relative mb-mo-3 mo2f-basic-plan mo2f_reduce_margin">
				<div class="my-mo-3">
					<?php esc_html_e( 'Enable 2FA for specific users', 'miniorange-2-factor-authentication' ); ?>
								<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
				</div>
				<div class="mo2f-settings-items">
					<?php esc_html_e( 'Click', 'miniorange-2-factor-authentication' ); ?>
					&emsp13;<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" target="_blank">
					<?php esc_html_e( 'here', 'miniorange-2-factor-authentication' ); ?>
					</a>&emsp13;<?php esc_html_e( 'to enable/disable 2FA for your users.', 'miniorange-2-factor-authentication' ); ?>
				</div>
			</div>
			<div class="justify-start flex" id="mo2f_enable2FA_save">
				<div class="mo2f_enable2FA_save_button">
					<button type="button" id="mo2f_enable2FA_save_button" class="mo2f-save-settings-button">
					<?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="mo2f-setting-row">
			<div class="mo2f-setting-main">
				<span class="mo2f-setting-title"><?php esc_html_e( 'Select 2FA Methods for Users', 'miniorange-2-factor-authentication' ); ?></span>
			</div>
			<div class="mo2f-setting-control">
				<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_select_methods_for_users" value="mo2f_select_methods_for_users"  onclick="mo2f_showSettings(this)" <?php checked( $mo2fa_enable_method_selection ); ?> disabled /><span class="mo2f-settings-checkmark"></span></label>
			</div>
		</div>
		<div class="mo2f-sub-settings-div mo2f-sub-panel" id="mo2f_select_methods_for_users_settings" <?php echo $mo2fa_enable_method_selection ? 'flex' : 'hidden'; ?> >
		<div class="my-mo-3 mo2f-basic-plan">
			<input type="radio" name="mo2f_methods_for_users" value="1" id="2fa_methods_for_all" <?php checked( $mo2f_selected_type ); ?> ><?php esc_html_e( 'Use 2FA methods for All Users', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

		</div>
		<div class="mo2f-sub-settings-div mo2f-settings-items <?php echo ( esc_attr( $mo2f_lv_needed ) ? ( $mo2f_selected_type ? 'flex' : 'mo2f-hide-div' ) : 'flex' ); ?>" id="mo2f_all_2fa_methods_div">
			<div class="mo2f-method-tiles<?php echo ! empty( $mo2f_method_tiles_readonly ) ? ' mo2f-method-tiles--readonly' : ''; ?>">
				<?php
				foreach ( $mo2f_methods_on_dashboard as $mo2f_method ) {
					$mo2f_is_method_premium_locked = ! empty( $mo2f_two_factor_methods_details[ $mo2f_method ]['crown'] );
					$mo2f_is_checked               = ! $mo2f_is_method_premium_locked && ( in_array( $mo2f_method, $mo2f_selected_methods, true ) || ( ! $mo2f_lv_needed ) );
					$mo2f_is_enabled               = $mo2f_is_checked ? 'enabled' : '';
					?>
					<div class="mo2f-method-tile <?php echo esc_attr( $mo2f_is_enabled ); ?>" data-method="<?php echo esc_attr( $mo2f_method ); ?>">
						<div class="mo2f-method-icon mo2f-method-icon-quick-setup">
							<?php
							if ( MoWpnsConstants::OTP_OVER_WHATSAPP === $mo2f_method ) {
								global $mo2f_main_dir;
								$mo2f_whatsapp_icon_url = ( isset( $mo2f_main_dir ) ? $mo2f_main_dir : ( defined( 'MO2F_PLUGIN_URL' ) ? MO2F_PLUGIN_URL : '' ) ) . 'includes/images/whatsapp.png';
								echo '<img src="' . esc_url( $mo2f_whatsapp_icon_url ) . '" style="width:18px;height:18px;vertical-align:middle;" alt="' . esc_attr__( 'WhatsApp', 'miniorange-2-factor-authentication' ) . '">';
							} else {
								echo esc_html( isset( $mo2f_method_icons[ $mo2f_method ] ) ? $mo2f_method_icons[ $mo2f_method ] : '🔧' );
							}
							?>
						</div>
						<div class="mo2f-method-content">
							<div class="mo2f-method-header">
								<span class="mo2f-method-name"><?php echo esc_html( $mo2f_method_names[ $mo2f_method ] ); ?></span>
								<?php if ( $mo2f_is_method_premium_locked ) : ?>
									<span class="mo2f-method-badge mo2f-method-badge--premium"><?php echo esc_html__( 'Premium', 'miniorange-2-factor-authentication' ); ?></span>
								<?php elseif ( $mo2f_is_checked ) : ?>
									<span class="mo2f-method-badge"><?php echo esc_html__( 'Enabled', 'miniorange-2-factor-authentication' ); ?></span>
								<?php endif; ?>
							</div>
							<p class="mo2f-method-hint"><?php echo esc_html( isset( $mo2f_method_hints[ $mo2f_method ] ) ? $mo2f_method_hints[ $mo2f_method ] : '' ); ?></p>
							<?php if ( ! $mo2f_is_method_premium_locked && ! empty( $mo2f_show_cloud_login_notice ) && in_array( $mo2f_method, array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OUT_OF_BAND_EMAIL ), true ) ) : ?>
								<p class="mo2f-method-login-notice"><?php esc_html_e( 'You need to log in to your miniOrange account first to use this method.', 'miniorange-2-factor-authentication' ); ?></p>
							<?php endif; ?>
						</div>
						<div class="mo2f-method-toggle">
							<label class="switch_2fa_methods">
								<input type="checkbox" name="mo2f_methods[]" id="<?php echo 'mo2fa_forall_' . esc_attr( $mo2f_method ); ?>" value="<?php echo esc_attr( $mo2f_method ); ?>" <?php checked( $mo2f_is_checked ); ?> <?php disabled( ! empty( $mo2f_method_tiles_readonly ) || $mo2f_is_method_premium_locked ); ?> aria-disabled="<?php echo ( ! empty( $mo2f_method_tiles_readonly ) || $mo2f_is_method_premium_locked ) ? 'true' : 'false'; ?>">
								<span class="slider_2fa_methods"></span>
							</label>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<div class="my-mo-3 mo2f-basic-plan">
			<input type="radio" name="mo2f_methods_for_users" value="0" id="2fa_methods_for_roles"  <?php checked( ! $mo2f_selected_type ); ?>><?php esc_html_e( 'Use 2FA methods for Specific Roles', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

		</div>
		<div class="<?php echo ! $mo2f_selected_type ? 'flex' : 'mo2f-hide-div'; ?>" id="mo2f_2fa_methods_for_roles_div">
		<div class="mo2f-settings-items flex-col" >
			<?php
			foreach ( $wp_roles->role_names as $mo2f_role_id => $mo2f_role_name ) {
				?>
				<div class="my-mo-3"><input type="checkbox" name="mo2f_user_role[]" value="<?php echo esc_attr( $mo2f_role_id ); ?>" onclick="mo2f_show_specific_twofa_settings(this)" id="<?php echo 'mo2fa_' . esc_attr( $mo2f_role_id ); ?>" <?php checked( in_array( $mo2f_role_id, $mo2f_selected_roles, true ) ); ?> <?php echo ( esc_attr( ! $mo2f_lv_needed ) && 'administrator' === $mo2f_role_id ? 'checked' : '' ); ?>/><?php echo esc_html( $mo2f_role_name ); ?></div>
				<div class="mo2f-sub-settings-items <?php echo ( in_array( $mo2f_role_id, $mo2f_selected_roles, true ) || ( ! $mo2f_lv_needed && 'administrator' === $mo2f_role_id ) ) ? 'flex' : 'hidden'; ?>"  id="<?php echo 'mo2fa_' . esc_attr( $mo2f_role_id ) . '_settings'; ?>" data-role="<?php echo esc_attr( $mo2f_role_id ); ?>">
					<div class="mo2f-method-tiles<?php echo ! empty( $mo2f_method_tiles_readonly ) ? ' mo2f-method-tiles--readonly' : ''; ?>">
						<?php
						foreach ( $mo2f_methods_on_dashboard as $mo2f_method ) {
							$mo2f_role_methods             = (array) get_site_option( 'mo2f_auth_methods_for_' . $mo2f_role_id );
							$mo2f_is_checked               = in_array( $mo2f_method, $mo2f_role_methods, true );
							$mo2f_is_enabled               = $mo2f_is_checked ? 'enabled' : '';
							$mo2f_is_method_premium_locked = ! empty( $mo2f_two_factor_methods_details[ $mo2f_method ]['crown'] );
							?>
							<div class="mo2f-method-tile <?php echo esc_attr( $mo2f_is_enabled ); ?>" data-method="<?php echo esc_attr( $mo2f_method ); ?>">
								<div class="mo2f-method-icon mo2f-method-icon-quick-setup">
									<?php
									if ( MoWpnsConstants::OTP_OVER_WHATSAPP === $mo2f_method ) {
										$mo2f_whatsapp_icon_url = plugin_dir_url( dirname( dirname( __DIR__ ) ) ) . 'includes/images/whatsapp.png';
										echo '<img src="' . esc_url( $mo2f_whatsapp_icon_url ) . '" style="width:18px;height:18px;vertical-align:middle;" alt="' . esc_attr__( 'WhatsApp', 'miniorange-2-factor-authentication' ) . '">';
									} else {
										echo esc_html( isset( $mo2f_method_icons[ $mo2f_method ] ) ? $mo2f_method_icons[ $mo2f_method ] : '🔧' );
									}
									?>
								</div>
								<div class="mo2f-method-content">
									<div class="mo2f-method-header">
										<span class="mo2f-method-name"><?php echo esc_html( $mo2f_method_names[ $mo2f_method ] ); ?></span>
										<?php if ( $mo2f_is_checked ) : ?>
											<span class="mo2f-method-badge"><?php echo esc_html__( 'Enabled', 'miniorange-2-factor-authentication' ); ?></span>
										<?php endif; ?>
									</div>
									<p class="mo2f-method-hint"><?php echo esc_html( isset( $mo2f_method_hints[ $mo2f_method ] ) ? $mo2f_method_hints[ $mo2f_method ] : '' ); ?></p>
									<?php if ( ! $mo2f_is_method_premium_locked && ! empty( $mo2f_show_cloud_login_notice ) && in_array( $mo2f_method, array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OUT_OF_BAND_EMAIL ), true ) ) : ?>
										<p class="mo2f-method-login-notice"><?php esc_html_e( 'You need to log in to your miniOrange account first to use this method.', 'miniorange-2-factor-authentication' ); ?></p>
									<?php endif; ?>
								</div>
								<div class="mo2f-method-toggle">
									<label class="switch_2fa_methods">
										<input type="checkbox" name="<?php echo 'mo2fa_' . esc_attr( $mo2f_role_id ) . '_[]'; ?>" id="<?php echo 'mo2fa_' . esc_attr( $mo2f_method ) . '_' . esc_attr( $mo2f_role_id ); ?>" value="<?php echo esc_attr( $mo2f_method ); ?>" <?php checked( $mo2f_is_checked ); ?> <?php disabled( ! empty( $mo2f_method_tiles_readonly ) ); ?> aria-disabled="<?php echo ! empty( $mo2f_method_tiles_readonly ) ? 'true' : 'false'; ?>">
										<span class="slider_2fa_methods"></span>
									</label>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
		<div class="justify-start <?php echo $mo2fa_enable_method_selection ? 'flex' : 'hidden'; ?>" id="mo2f_select_methods_for_users_save">
			<div class="mo2f-basic-plan">
				<button class="mo2f-save-settings-button" id="mo2f_selected_2fa_methods_save"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
			</div>
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
				<div class="my-mo-3 mr-mo-4" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_enabled_backup_method" value="mo2f_reconfig_link_show" <?php echo in_array( 'mo2f_reconfig_link_show', $mo2f_enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Account Recovery Via Email Verification', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="my-mo-3 mr-mo-4 mo2f-basic-plan" style="display:inline-flex;align-items:center;white-space:nowrap;"><input type="checkbox" name="mo2f_enabled_backup_method" value="backup_kba" <?php echo in_array( 'backup_kba', $mo2f_enabled_backup_methods, true ) ? 'checked' : ''; ?>/><?php esc_html_e( 'Security Questions (KBA)', 'miniorange-2-factor-authentication' ); ?>
					<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
				</div>
			</div>
		</div>
		<div class="justify-start mo2f-sub-panel <?php echo $mo2f_enable_backup_login ? 'flex' : 'hidden'; ?>" id="mo2f_enable_backup_methods_save"><div class="mo2f_enable_backup_methods_save_button"><button id="mo2f_enable_backup_methods_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
		</div>
		</div>

		<div class="mo2f-setting-row">
			<div class="mo2f-setting-main">
				<span class="mo2f-setting-title"><?php esc_html_e( 'Enable Grace Period', 'miniorange-2-factor-authentication' ); ?></span>
			</div>
			<div class="mo2f-setting-control">
				<?php $mo2f_enable_grace_period = MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'site_option' ); ?>
				<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_graceperiod" onclick="mo2f_showSettings(this)" <?php checked( $mo2f_enable_grace_period ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			</div>
		</div>
		<div class="mo2f-sub-settings-div mo2f-sub-panel <?php echo $mo2f_enable_grace_period ? 'flex' : 'hidden'; ?>" id="mo2f_enable_graceperiod_settings">
				<div class="my-mo-3"><?php esc_html_e( 'Provide users a Grace Period to configure 2FA', 'miniorange-2-factor-authentication' ); ?></div>
				<div id="mo2f_grace_period_show" class="mo2f-settings-items items-center">
					<div class="mr-mo-4"><input type="number" name="" id="mo2f_grace_period" class="mo2f-settings-number-field" name= "mo2f_grace_period_value" value="<?php echo esc_attr( get_site_option( 'mo2f_grace_period_value', 1 ) ); ?>" min=0></div>
					<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_type" class="mt-mo-2" id="mo2f_grace_hour" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'site_option' ) === 'hours' ); ?>  value="hours"/>Hours</div>
					<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_type" class="mt-mo-2" id="mo2f_grace_day" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'site_option' ) === 'days' ); ?> value="days"/>Days</div>
					</br>
				</div>
				<div class="mb-mo-3">
				<div class="my-mo-3"><?php esc_html_e( 'Action after grace period is expired', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-settings-items">
				<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_action" id="mo2f_enforce_2fa" value="enforce_2fa" <?php checked( get_site_option( 'mo2f_graceperiod_action' ) === 'enforce_2fa' ); ?>><?php esc_html_e( 'Enforce 2FA', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mr-mo-4"><input type="radio" name="mo2f_graceperiod_action" id="mo2f_block_users" value="block_user_login" <?php checked( get_site_option( 'mo2f_graceperiod_action' ) === 'block_user_login' ); ?>><?php esc_html_e( 'Block users from login', 'miniorange-2-factor-authentication' ); ?></div>
				</div>
			</div>
			<div class="mb-mo-3">
				<div class="my-mo-3"><?php esc_html_e( 'Per-role overrides (optional) — give specific roles a different grace period than the default above.', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f_table_styling">
				<table id="mo2f_role_graceperiod_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Role', 'miniorange-2-factor-authentication' ); ?></th>
						<th><?php esc_html_e( 'Grace period', 'miniorange-2-factor-authentication' ); ?></th>
						<th></th>
						<th><?php esc_html_e( 'Action when expired', 'miniorange-2-factor-authentication' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$mo2f_role_grace_overrides = (array) get_site_option( 'mo2f_role_grace_period_overrides', array() );
				foreach ( $wp_roles->role_names as $mo2f_gp_role_id => $mo2f_gp_role_name ) :
					$mo2f_gp_override = isset( $mo2f_role_grace_overrides[ $mo2f_gp_role_id ] ) ? $mo2f_role_grace_overrides[ $mo2f_gp_role_id ] : array();
					?>
					<tr>
						<td>
							<input type="checkbox" class="mo2f-role-grace-enabled" data-role="<?php echo esc_attr( $mo2f_gp_role_id ); ?>" <?php echo ! empty( $mo2f_gp_override ) ? 'checked' : ''; ?> />
							<?php echo esc_html( $mo2f_gp_role_name ); ?>
						</td>
						<td><input type="number" min="0" class="mo2f-settings-number-field mo2f-role-grace-value" data-role="<?php echo esc_attr( $mo2f_gp_role_id ); ?>" value="<?php echo esc_attr( isset( $mo2f_gp_override['value'] ) ? $mo2f_gp_override['value'] : '' ); ?>" /></td>
						<td>
							<select class="mo2f-role-grace-type" data-role="<?php echo esc_attr( $mo2f_gp_role_id ); ?>">
								<option value="days" <?php echo ( ! isset( $mo2f_gp_override['type'] ) || 'days' === $mo2f_gp_override['type'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Days', 'miniorange-2-factor-authentication' ); ?></option>
								<option value="hours" <?php echo ( isset( $mo2f_gp_override['type'] ) && 'hours' === $mo2f_gp_override['type'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Hours', 'miniorange-2-factor-authentication' ); ?></option>
							</select>
						</td>
						<td>
							<select class="mo2f-role-grace-action" data-role="<?php echo esc_attr( $mo2f_gp_role_id ); ?>">
								<option value="" <?php echo ! isset( $mo2f_gp_override['action'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Use default action above', 'miniorange-2-factor-authentication' ); ?></option>
								<option value="enforce_2fa" <?php echo ( isset( $mo2f_gp_override['action'] ) && 'enforce_2fa' === $mo2f_gp_override['action'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Enforce 2FA', 'miniorange-2-factor-authentication' ); ?></option>
								<option value="block_user_login" <?php echo ( isset( $mo2f_gp_override['action'] ) && 'block_user_login' === $mo2f_gp_override['action'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Block users from login', 'miniorange-2-factor-authentication' ); ?></option>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				</table>
				</div>
			</div>
		</div>
		<div class="justify-start mo2f-sub-panel <?php echo $mo2f_enable_grace_period ? 'flex' : 'hidden'; ?>" id="mo2f_enable_graceperiod_save"><div><button id="mo2f_enable_graceperiod_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>
<script>
	jQuery('#quicksetup').addClass('mo2f-subtab-active');
	jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
</script>
	<?php
	global $mo2f_main_dir;
	wp_enqueue_script( 'login-settings-script', $mo2f_main_dir . '/includes/js/login-settings.min.js', array( 'jquery' ), MO2F_VERSION, false );
	wp_localize_script(
		'login-settings-script',
		'loginSettings',
		array(
			'nonce' => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
		)
	);
	// Ensures mo2f_admin_login_bypass_2fa is always sent (cached login-settings.min.js may omit it).
	wp_add_inline_script(
		'login-settings-script',
		'jQuery.ajaxPrefilter(function(o){var d=o.data;if(!d||typeof d!=="object"||d instanceof window.FormData||Array.isArray(d)||d.option!=="mo2f_enable2FA_save_option"||Object.prototype.hasOwnProperty.call(d,"mo2f_admin_login_bypass_2fa"))return;var e=document.getElementById("mo2f_admin_bypass_2fa_login");d.mo2f_admin_login_bypass_2fa=e&&e.checked?"true":"false";});',
		'after'
	);
}
?>
