<?php
/**
 * This file contains plugin's main dashboard UI.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div>
	<div class="mo2f-tw-top-content">
		<div class="mo2f-setup-two-factor-title">
			<span><?php esc_html_e( 'Setup 2-factor Method for You', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="test_auth_button">
		<?php
		if ( isset( $mo2f_two_fa_method ) && ! empty( $mo2f_two_fa_method ) && ! get_user_meta( $mo2f_user_id, 'mo_backup_code_limit_reached', true ) && $mo2f_can_display_admin_features ) {
			?>
			<button class="mo2f-tw-test-button" id="mo_2f_generate_codes">Download Backup Codes</button>
			<?php
		}
		$mo2f_sms_user_count       = $mo2fdb_queries->mo2f_get_specific_method_users_count( MoWpnsConstants::OTP_OVER_SMS );
		$mo2f_selected_method_abbr = str_replace( ' ', '', MoWpnsConstants::mo2f_convert_method_name( $mo2f_selected_method, 'cap_to_small' ) );
		if ( $mo2f_is_customer_admin_registered && 0 !== $mo2f_sms_user_count && $mo2f_can_display_admin_features ) {// to do: can show recharge link universal. check.
			?>
			<button onclick="window.open('<?php echo esc_url( MoWpnsConstants::RECHARGELINK ); ?>')" class="mo2f-tw-test-button">Add SMS</button>
			<?php
		}
		$mo2f_common_helper = new Mo2f_Common_Helper();
		if ( $mo2f_common_helper->mo2f_is_2fa_set( wp_get_current_user()->ID ) ) {
			?>

<button class="mo2f-reset-settings-button" id="mo2f_test_method" onclick="testAuthenticationMethod('<?php echo esc_attr( $mo2f_selected_method_abbr ); ?>');"
			<?php echo ( 'NONE' !== $mo2f_selected_method ) ? '' : ' disabled '; ?>>Test - <strong> <?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_selected_method, 'cap_to_small' ) ); ?> </strong>
			</button>
			<?php
		}
		?>
		</div>
	</div>
		<?php
		// ----------------------------------------.
		global $mo2fdb_queries;

		$mo2f_is_customer_registered        = 'SUCCESS' === $mo2fdb_queries->mo2f_get_user_detail( 'user_registration_with_miniorange', $mo2f_user->ID );
		$mo2f_can_user_configure_2fa_method = $mo2f_can_display_admin_features || $mo2f_is_customer_registered;

		echo '<div class="overlay1" id="overlay" hidden ></div>';
		echo '<form name="f" method="post" action="" id="mo2f_save_free_plan_auth_methods_form">
                <div id="mo2f_free_plan_auth_methods" >
                    <br>
                    <table class="mo2f_auth_methods_table">';

		foreach ( $mo2f_methods_on_dashboard as $mo2f_auth_method ) {
			$mo2f_is_premium_feature        = isset( $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['crown'] ) && $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['crown'];
			$mo2f_auth_method_abbr          = str_replace( ' ', '', MoWpnsConstants::mo2f_convert_method_name( $mo2f_auth_method, 'cap_to_small' ) );
			$mo2f_auth_method_abbr          = empty( $mo2f_auth_method_abbr ) ? 'NoMethod' : $mo2f_auth_method_abbr;
			$mo2f_is_auth_method_selected   = ( $mo2f_auth_method === $mo2f_selected_method ? true : false );
			$mo2f_doc_link                  = isset( $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['doc'] ) ? $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['doc'] : null;
			$mo2f_video_link                = isset( $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['video'] ) ? $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['video'] : null;
			$mo2f_is_auth_method_configured = 0;
			if ( ( MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_auth_method || MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_auth_method ) && ! MO2F_IS_ONPREM ) {
				$mo2f_is_auth_method_configured = 1;
			} else {
				$mo2f_is_auth_method_configured = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_' . $mo2f_auth_method_abbr . '_config_status', $mo2f_user->ID );
			}
			$mo2f_is_mfa_enabled        = get_site_option( 'mo2f_multi_factor_authentication' );
			$mo2f_is_all_inclusive_file = file_exists( $mo2f_dir_name . 'handler' . DIRECTORY_SEPARATOR . 'class-mo2f-all-inclusive-premium-settings.php' );
			echo '<div class="mo2f-tw-thumbnail ' . ( ( ! $mo2f_is_all_inclusive_file && 'WHATSAPP' === $mo2f_auth_method ) ? 'mo2f-all-inclusive-plan' : ' ' ) . '"';
			echo ( ( $mo2f_is_mfa_enabled && $mo2f_is_auth_method_configured ) || $mo2f_is_auth_method_selected ) ? 'bg-indigo-50' : 'bg-indigo-white';
			echo '" id="' . esc_attr( $mo2f_auth_method_abbr ) . '_thumbnail_2_factor"';
			echo $mo2f_is_auth_method_selected ? '#07b52a' : 'var(--mo2f-theme-blue)';
			echo ';">';
			echo '<div class="mo2f-thumbnail-top-section">
                        <div class="mo2f-method-header"><div class="">';
			echo '<img src="' . esc_url( plugins_url( 'includes/images/authmethods/' . $mo2f_auth_method_abbr . '.png', dirname( __DIR__ ) ) ) . '" class="mo2f-method-icon" />';

			echo '</div><div class="mo2f-method-title">';
			echo '<b>';
			if ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_auth_method ) {
				echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_auth_method, 'cap_to_small' ) . ' Via Link' );
			} else {
				echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_auth_method, 'cap_to_small' ) );
			}

			echo '</b></div></div>';
			echo '<div>';
			if ( ! $mo2f_is_all_inclusive_file && 'WHATSAPP' === $mo2f_auth_method ) {
				echo MoWpnsConstants::PREMIUM_CROWN; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. 
			}
			echo '</div>';
			echo '   <div class="mo2f-guide-icons" >';
			if ( isset( $mo2f_doc_link ) ) {
				echo '<a href=' . esc_url( $mo2f_doc_link ) . ' class="mx-auto" target="_blank">
                <span title="View Setup Guide" class="dashicons dashicons-text-page  mo2f-dash-icons-doc"></span>
                </a>';
			}
			if ( isset( $mo2f_video_link ) ) {
				echo '<a href=' . esc_url( $mo2f_video_link ) . ' class="mx-auto" target="_blank">
                <span title="Watch Setup Video" class="dashicons dashicons-video-alt3 mo2f-dash-icons-video"></span>
                </a>';
			}
			echo '</div>';
			echo '</div>';
			echo '<div class="mo2f-thumbnail-method-desc">';
			echo wp_kses_post( __( $mo2f_two_factor_methods_details[ $mo2f_auth_method ]['desc'], 'miniorange-2-factor-authentication' ) ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal 
			echo '</div>';
			if ( $mo2f_is_premium_feature ) {
				echo '<div class="mo2f_settings_overlay"></div>';
			}
			echo '<div class="mo2f-thumbnail-bottom-section">';
			if ( MO2F_IS_ONPREM ) {
				$twofactor_transactions             = new Mo2fDB();
				$mo2f_limit_exceeded                = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $mo2f_user->ID ), 'is_user_limit_exceeded', array() );
				$mo2f_can_user_configure_2fa_method = ! $mo2f_limit_exceeded || ! empty( $mo2f_selected_method );
				$mo2f_display_configure_button      = 1;
				$mo2f_disabled_attr                 = $mo2f_can_user_configure_2fa_method ? '' : ' disabled ';
			} else {
				$mo2f_display_configure_button = ! $mo2f_is_customer_registered ? true : ( MoWpnsConstants::OUT_OF_BAND_EMAIL !== $mo2f_auth_method && MoWpnsConstants::OTP_OVER_EMAIL !== $mo2f_auth_method );

				if ( ! MO2F_IS_ONPREM && ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $mo2f_auth_method || MoWpnsConstants::OTP_OVER_EMAIL === $mo2f_auth_method ) ) {
					$mo2f_display_configure_button = 0;
				}
				$mo2f_disabled_attr = $mo2f_can_user_configure_2fa_method ? '' : '  ';
			}
			echo '<div>';
			if ( ! $mo2f_is_all_inclusive_file && 'WHATSAPP' === $mo2f_auth_method ) {
				echo '<span class="mo2f-tw-configure-2fa-whatsapp" id="' . esc_attr( $mo2f_auth_method_abbr ) . '_configuration" >Configure</span>';
			} elseif ( $mo2f_display_configure_button ) {
				echo '<button type="button" id="' . esc_attr( $mo2f_auth_method_abbr ) . '_configuration" class="mo2f-tw-configure-2fa" onclick="configureOrSet2ndFactor_free_plan(\'' . esc_js( $mo2f_auth_method_abbr ) . '\', \'configure2factor\');"';
				echo esc_attr( $mo2f_disabled_attr );
				echo '>';
				echo $mo2f_is_auth_method_configured ? 'Reconfigure' : 'Configure';
				echo '</button>';
			}
			echo '</div>';
			echo '<div>';
			if ( $mo2f_is_auth_method_configured && ! $mo2f_is_auth_method_selected && ! $mo2f_is_mfa_enabled ) {
				echo '<button type="button" id="' . esc_attr( $mo2f_auth_method_abbr ) . '_set_2_factor" class="mo2f-tw-configure-2fa" onclick="configureOrSet2ndFactor_free_plan(\'' . esc_js( $mo2f_auth_method_abbr ) . '\', \'select2factor\');"';
				echo esc_attr( $mo2f_disabled_attr );
				echo '>Set as 2-factor</button>';

			}
			echo '</div>';
			echo '</div>';
			echo '</div></div>';

		}
		echo '</table>';

		$mo2f_configured_auth_method_abbr = str_replace( ' ', '', $mo2f_selected_method );
		echo '</div> <input type="hidden" name="miniorange_save_form_auth_methods_nonce"
                        value="' . esc_attr( wp_create_nonce( 'miniorange-save-form-auth-methods-nonce' ) ) . '"/>
                    <input type="hidden" name="option" value="mo2f_save_free_plan_auth_methods" />
                    <input type="hidden" name="mo2f_configured_2FA_method_free_plan" id="mo2f_configured_2FA_method_free_plan" />
                    <input type="hidden" name="mo2f_selected_action_free_plan" id="mo2f_selected_action_free_plan" />
                    </form>';
		?>
</div><br>
<hr><br>
<?php if ( $mo2f_can_display_admin_features ) { ?>
<div class="mo2f-setup-two-factor-title footer-setup-2fa">

	<span><?php esc_html_e( 'Set 2-factor method for other users?', 'miniorange-2-factor-authentication' ); ?></span>&emsp13;<span class="text-mo-caption"><?php esc_html_e( '  Click ', 'miniorange-2-factor-authentication' ); ?><a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'here', 'miniorange-2-factor-authentication' ); ?></a> <?php esc_html_e( ' to setup 2FA method for your users.', 'miniorange-2-factor-authentication' ); ?></span>

</div>
<?php } ?>
<form name="f" method="post" action="" id="mo2f_2factor_generate_backup_codes">
	<input type="hidden" name="option" value="mo2f_download_backup_codes_dashboard"/>
	<input type="hidden" name="mo2f_login_settings_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo2f-login-settings-nonce' ) ); ?>"/>
</form>
<?php
global $mo2f_main_dir;
wp_enqueue_script( 'setup-2fa-for-me-script', $mo2f_main_dir . '/includes/js/setup-2fa-for-me.min.js', array(), MO2F_VERSION, false );
wp_localize_script(
	'setup-2fa-for-me-script',
	'setup2faForMe',
	array(
		'nonce' => esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ),
	)
);
