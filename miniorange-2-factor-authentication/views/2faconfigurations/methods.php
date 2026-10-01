<?php
/**
 * This file shows the Methods settings on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( current_user_can( 'manage_options' ) ) {
	?>
	<div id="wpns_nav_message"></div>

	<div class="mo2f-settings-div mo2f-section">
		<div class="mo2f-section-head">
			<h2><?php esc_html_e( 'Methods', 'miniorange-2-factor-authentication' ); ?></h2>
			<p><?php esc_html_e( 'Choose which 2FA methods are available to your users.', 'miniorange-2-factor-authentication' ); ?></p>
		</div>

		<p class="mo2f-plugin-value-hint">
			<?php esc_html_e( 'Free methods below are enabled automatically for every user role. Upgrade to pick specific methods or set different methods per role.', 'miniorange-2-factor-authentication' ); ?>
		</p>

		<div class="mo2f-settings-items flex" id="mo2f_all_2fa_methods_div">
			<?php
			// Split into free vs. premium so they render as two distinct groups.
			$mo2f_free_methods_on_dashboard    = array();
			$mo2f_premium_methods_on_dashboard = array();
			foreach ( $mo2f_methods_on_dashboard as $mo2f_method ) {
				if ( ! empty( $mo2f_two_factor_methods_details[ $mo2f_method ]['crown'] ) ) {
					$mo2f_premium_methods_on_dashboard[] = $mo2f_method;
				} else {
					$mo2f_free_methods_on_dashboard[] = $mo2f_method;
				}
			}
			foreach ( array( $mo2f_free_methods_on_dashboard, $mo2f_premium_methods_on_dashboard ) as $mo2f_method_group ) {
				if ( empty( $mo2f_method_group ) ) {
					continue;
				}
				$mo2f_group_is_premium = $mo2f_method_group === $mo2f_premium_methods_on_dashboard;
				?>
				<div class="mo2f-method-group-divider">
					<span><?php echo $mo2f_group_is_premium ? esc_html__( 'Premium Methods', 'miniorange-2-factor-authentication' ) : esc_html__( 'Free Methods', 'miniorange-2-factor-authentication' ); ?></span>
				</div>
				<div class="mo2f-method-tiles">
					<?php
					foreach ( $mo2f_method_group as $mo2f_method ) {
						$mo2f_is_method_premium_locked = ! empty( $mo2f_two_factor_methods_details[ $mo2f_method ]['crown'] );
						?>
						<div class="mo2f-method-tile <?php echo $mo2f_is_method_premium_locked ? '' : 'enabled'; ?>" data-method="<?php echo esc_attr( $mo2f_method ); ?>">
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
									<?php else : ?>
										<span class="mo2f-method-badge"><?php echo esc_html__( 'Enabled', 'miniorange-2-factor-authentication' ); ?></span>
									<?php endif; ?>
								</div>
								<p class="mo2f-method-hint"><?php echo esc_html( isset( $mo2f_method_hints[ $mo2f_method ] ) ? $mo2f_method_hints[ $mo2f_method ] : '' ); ?></p>
								<?php if ( ! $mo2f_is_method_premium_locked && ! empty( $mo2f_show_cloud_login_notice ) && in_array( $mo2f_method, array( MoWpnsConstants::OTP_OVER_SMS, MoWpnsConstants::OTP_OVER_EMAIL, MoWpnsConstants::OUT_OF_BAND_EMAIL ), true ) ) : ?>
									<p class="mo2f-method-login-notice"><?php esc_html_e( 'You need to log in to your miniOrange account first to use this method.', 'miniorange-2-factor-authentication' ); ?></p>
								<?php endif; ?>
							</div>
							<?php if ( $mo2f_is_method_premium_locked ) : ?>
								<div class="mo2f-method-toggle">
									<label class="switch_2fa_methods">
										<input type="checkbox" disabled aria-disabled="true">
										<span class="slider_2fa_methods"></span>
									</label>
								</div>
							<?php endif; ?>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
		</div>

		<div class="mo2f-setting-row mo2f-basic-plan">
			<div class="mo2f-setting-main">
				<span class="mo2f-setting-title">
					<?php esc_html_e( 'Role-based Method Selection', 'miniorange-2-factor-authentication' ); ?>
					<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
				</span>
			</div>
			<div class="mo2f-setting-control">
				<p class="mo2f-setting-desc">
					<?php esc_html_e( 'Pick specific methods, or assign different methods to each user role -- e.g. require Passkey for Administrators and allow OTP for Subscribers.', 'miniorange-2-factor-authentication' ); ?>
				</p>
			</div>
		</div>
	</div>
	<script>
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
}
?>
