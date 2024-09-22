<?php
/**
 * This file contains plugin's main dashboard UI.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$overlay_on_premium_features = MO2F_PREMIUM_PLAN ? '' : '<div class="mo2f_settings_overlay"><div class="mo2f-method-premium-tag">Premium Feature</div></div>';
?>
<div>
	<div class="mo2f-tw-top-content">
		<div class="mo2f-setup-two-factor-title">
		<?php if ( $can_display_admin_features ) { ?>
			<span><?php esc_html_e( 'Setup 2-factor method for me', 'miniorange-2-factor-authentication' ); ?></span>
		<?php } ?>
		</div>
		<div class="test_auth_button">
		<?php
		if ( isset( $mo2f_two_fa_method ) && ! empty( $mo2f_two_fa_method ) && ! get_user_meta( $user_id, 'mo_backup_code_limit_reached' ) ) {
			?>
			<button class="mo2f-tw-test-button" id="mo_2f_generate_codes">Download Backup Codes</button>
			<?php
		}
		$count           = $mo2fdb_queries->mo2f_get_specific_method_users_count( MoWpnsConstants::OTP_OVER_SMS );
		$auth_method_abr = str_replace( ' ', '', MoWpnsConstants::mo2f_convert_method_name( $selected_method, 'cap_to_small' ) );
		if ( $is_customer_admin_registered && 0 !== $count && $can_display_admin_features ) {// to do: can show recharge link universal. check.
			?>
			<button onclick="window.open('<?php echo esc_url( MoWpnsConstants::RECHARGELINK ); ?>')" class="mo2f-tw-test-button">Add SMS</button>
			<?php } ?>
			<button class="mo2f-tw-test-button" id="mo2f_test_method" onclick="testAuthenticationMethod('<?php echo esc_attr( $auth_method_abr ); ?>');"
			<?php echo ( 'NONE' !== $selected_method ) ? '' : ' disabled '; ?>>Test - <strong> <?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $selected_method, 'cap_to_small' ) ); ?> </strong>
			</button>
		</div>
	</div>
		<?php
		// ----------------------------------------.
		global $mo2fdb_queries;

		$is_customer_registered        = 'SUCCESS' === $mo2fdb_queries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) ? true : false;
		$can_user_configure_2fa_method = $can_display_admin_features || $is_customer_registered;

		echo '<div class="overlay1" id="overlay" hidden ></div>';
		echo '<form name="f" method="post" action="" id="mo2f_save_free_plan_auth_methods_form">
                <div id="mo2f_free_plan_auth_methods" >
                    <br>
                    <table class="mo2f_auth_methods_table">';

		foreach ( $mo2f_methods_on_dashboard as $auth_method ) {
			$auth_method_abr           = str_replace( ' ', '', MoWpnsConstants::mo2f_convert_method_name( $auth_method, 'cap_to_small' ) );
			$auth_method_abr           = empty( $auth_method_abr ) ? 'NoMethod' : $auth_method_abr;
			$is_auth_method_selected   = ( $auth_method === $selected_method ? true : false );
			$doc_link                  = isset( $two_factor_methods_details[ $auth_method ]['doc'] ) ? $two_factor_methods_details[ $auth_method ]['doc'] : null;
			$video_link                = isset( $two_factor_methods_details[ $auth_method ]['video'] ) ? $two_factor_methods_details[ $auth_method ]['video'] : null;
			$is_auth_method_configured = 0;
			if ( ( MoWpnsConstants::OTP_OVER_EMAIL === $auth_method || MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_method ) && ! MO2F_IS_ONPREM ) {
				$is_auth_method_configured = 1;
			} else {
				$is_auth_method_configured = $mo2fdb_queries->get_user_detail( 'mo2f_' . $auth_method_abr . '_config_status', $user->ID );
			}
			$is_mfa_enabled = get_site_option( 'mo2f_multi_factor_authentication' );
			echo '<div class="mo2f-tw-thumbnail ';
			echo ( $is_mfa_enabled && $is_auth_method_configured || $is_auth_method_selected ) ? 'bg-indigo-50' : 'bg-indigo-white';
			echo '" id="' . esc_attr( $auth_method_abr ) . '_thumbnail_2_factor"';
			echo $is_auth_method_selected ? '#07b52a' : 'var(--mo2f-theme-blue)';
			echo ';">';
			echo '<div class="mo2f-thumbnail-top-section">
                        <div class="mo2f-method-header"><div class="">';
			echo '<img src="' . esc_url( plugins_url( 'includes/images/authmethods/' . $auth_method_abr . '.png', dirname( dirname( __FILE__ ) ) ) ) . '" class="mo2f-method-icon" />';

			echo '</div><div class="mo2f-method-title">';
			echo '<b>' . esc_html( MoWpnsConstants::mo2f_convert_method_name( $auth_method, 'cap_to_small' ) ) .
			'</b></div></div>';
			echo '   <div class="mo2f-guide-icons">';
			if ( isset( $doc_link ) ) {
				echo '<a href=' . esc_url( $doc_link ) . ' class="mx-auto" target="_blank">
                <span title="View Setup Guide" class="dashicons dashicons-text-page  mo2f-dash-icons-doc"></span>
                </a>';
			}
			if ( isset( $video_link ) ) {
				echo '<a href=' . esc_url( $video_link ) . ' class="mx-auto" target="_blank">
                <span title="Watch Setup Video" class="dashicons dashicons-video-alt3 mo2f-dash-icons-video"></span>
                </a>';
			}
			echo '</div>';
			echo '</div>';
			echo '<div class="mo2f-thumbnail-method-desc">';
			echo wp_kses_post( __( $two_factor_methods_details[ $auth_method ]['desc'], 'miniorange-2-factor-authentication' ) ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal 
			echo '</div>';
			if ( isset( $two_factor_methods_details[ $auth_method ]['crown'] ) && $two_factor_methods_details[ $auth_method ]['crown'] ) {
				$allowed_html = array(
					'div' => array(
						'class' => array(),
					),
				);
				echo wp_kses( $overlay_on_premium_features, $allowed_html );
			}
			echo '<div class="mo2f-thumbnail-bottom-section">';
			if ( MO2F_IS_ONPREM ) {
				$twofactor_transactions        = new Mo2fDB();
				$exceeded                      = $twofactor_transactions->check_alluser_limit_exceeded( $user->ID );
				$can_user_configure_2fa_method = ! $exceeded || ! empty( $selected_method );
				$display_configure_button      = 1;
				$disabled                      = $can_user_configure_2fa_method ? '' : ' disabled ';
			} else {
				$display_configure_button = ! $is_customer_registered ? true : ( MoWpnsConstants::OUT_OF_BAND_EMAIL !== $auth_method && MoWpnsConstants::OTP_OVER_EMAIL !== $auth_method );

				if ( ! MO2F_IS_ONPREM && ( MoWpnsConstants::OUT_OF_BAND_EMAIL === $auth_method || MoWpnsConstants::OTP_OVER_EMAIL === $auth_method ) ) {
					$display_configure_button = 0;
				}
				$disabled = $can_user_configure_2fa_method ? '' : '  ';
			}
			echo '<div>';
			if ( $display_configure_button ) {
				echo '<button type="button" id="' . esc_attr( $auth_method_abr ) . '_configuration" class="mo2f-tw-configure-2fa" onclick="configureOrSet2ndFactor_free_plan(\'' . esc_js( $auth_method_abr ) . '\', \'configure2factor\');"';
				echo esc_attr( $disabled );
				echo '>';
				echo $is_auth_method_configured ? 'Reconfigure' : 'Configure';
				echo '</button>';
			}
			echo '</div>';
			echo '<div>';
			if ( $is_auth_method_configured && ! $is_auth_method_selected && ! $is_mfa_enabled ) {
				echo '<button type="button" id="' . esc_attr( $auth_method_abr ) . '_set_2_factor" class="mo2f-tw-configure-2fa" onclick="configureOrSet2ndFactor_free_plan(\'' . esc_js( $auth_method_abr ) . '\', \'select2factor\');"';
				echo esc_attr( $disabled );
				echo '>Set as 2-factor</button>';

			}
			echo '</div>';
			echo '</div>';
			echo '</div></div>';

		}
		echo '</table>';

		$configured_auth_method_abr = str_replace( ' ', '', $selected_method );
		echo '</div> <input type="hidden" name="miniorange_save_form_auth_methods_nonce"
                        value="' . esc_attr( wp_create_nonce( 'miniorange-save-form-auth-methods-nonce' ) ) . '"/>
                    <input type="hidden" name="option" value="mo2f_save_free_plan_auth_methods" />
                    <input type="hidden" name="mo2f_configured_2FA_method_free_plan" id="mo2f_configured_2FA_method_free_plan" />
                    <input type="hidden" name="mo2f_selected_action_free_plan" id="mo2f_selected_action_free_plan" />
                    </form>';
		?>
</div><br>
<hr><br>
<div class="mo2f-setup-two-factor-title">
		<?php if ( $can_display_admin_features ) { ?>
			<span><?php esc_html_e( 'Setup 2-factor method for users?', 'miniorange-2-factor-authentication' ); ?></span>&emsp13;<span class="text-mo-caption"><?php esc_html_e( '  Click ', 'miniorange-2-factor-authentication' ); ?><a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'here', 'miniorange-2-factor-authentication' ); ?></a> <?php esc_html_e( ' to setup 2FA for your users.', 'miniorange-2-factor-authentication' ); ?></span>
		<?php } ?>
		</div>
<form name="f" method="post" action="" id="mo2f_2factor_generate_backup_codes">
	<input type="hidden" name="option" value="mo2f_2factor_generate_backup_codes"/>
	<input type="hidden" name="mo_2factor_generate_backup_codes_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo-2factor-generate-backup-codes-nonce' ) ); ?>"/>
</form>
	<!-- 2fa pop up conatainer -->
<div id="mo2f_2fa_popup_dashboard" class="modal" style="display:none;">
</div>
<?php
global $main_dir;
wp_enqueue_script( 'setup-2fa-for-me-script', $main_dir . '/includes/js/setup-2fa-for-me.min.js', array(), MO2F_VERSION, false );
wp_localize_script(
	'setup-2fa-for-me-script',
	'setup2faForMe',
	array(
		'nonce' => esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ),
	)
);
