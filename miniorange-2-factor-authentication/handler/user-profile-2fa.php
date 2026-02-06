<?php
/**
 * User profile 2fa file.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Handler\Twofa\MO2f_Cloud_Onprem_Interface;
use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Onprem\Two_Factor_Setup_Onprem_Cloud;
use TwoFA\Database\Mo2fDB;
use TwoFA\Helper\Mo2f_Common_Helper;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Handler\Mo2f_Main_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mo2f_onprem_cloud_obj;
$mo2f_is_registered = empty( get_site_option( 'mo2f_customerkey' ) ) ? false : true;
$mo2f_userrole      = $user->roles;
$mo2f_roles         = (array) $user->roles;
$mo2f_main_handler  = new Mo2f_Main_Handler();
$mo2f_flag          = $mo2f_main_handler->mo2f_check_if_twofa_is_enabled( $user );
if ( ! current_user_can( 'manage_options', $user->ID ) || ( ! MO2F_IS_ONPREM && ! $mo2f_is_registered ) || ! $mo2f_flag ) {
	return;
} elseif ( ! MO2F_IS_ONPREM && ! $mo2f_is_registered ) {
	return;
}
$mo2f_userid            = get_current_user_id();
$mo2f_common_helper     = new Mo2f_Common_Helper();
$mo2f_available_methods = $mo2f_common_helper->fetch_methods( $user );
$mo2f_available_methods = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2f_available_methods, 'fetch_twofa_methods', array( 'user' => $user ) );
if ( ! $mo2f_available_methods ) {
	return;
}
$mo2f_transient_id = MO2f_Utility::random_str( 20 );
set_transient( $mo2f_transient_id . 'mo2f_user_id', $user->ID, 300 );
$mo2f_same_user = $user->ID === $mo2f_userid;
global $mo2fdb_queries;
$mo2f_current_method    = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
$twofactor_transactions = new Mo2fDB();
$mo2f_exceeded          = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $user->ID ), 'is_user_limit_exceeded', array() );
if ( $mo2f_exceeded ) {
	return;
}
$mo2f_user_column_exists = $mo2fdb_queries->mo2f_check_if_user_exists( $user->ID );
$mo2f_email              = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
if ( empty( $mo2f_email ) ) {
	$mo2fdb_queries->mo2f_update_user_details( $user->ID, array( 'mo2f_user_email' => $user->user_email ) );
}
$mo2f_email                  = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
$mo2f_email                  = ! empty( $mo2f_email ) ? $mo2f_email : $user->user_email;
$mo2f_pass_2fa_login_session = new Miniorange_Password_2Factor_Login();

$mo2f_two_factor_methods_descriptions = array(
	MoWpnsConstants::GOOGLE_AUTHENTICATOR => 'administrator' === $user->roles[0] ? __( 'Please scan the below QR code using Google Authenticator app.', 'miniorange-2-factor-authentication' ) : sprintf(
		/* translators: %s: user email */
		__( 'Link to configure Google authenticator method will be sent to %s.', 'miniorange-2-factor-authentication' ),
		$user->user_email
	),
	MoWpnsConstants::SECURITY_QUESTIONS   => sprintf(
		/* translators: %s: user login */
		__( 'Please click on %1$1sUpdate User%2$2s button in order to set the %3$3sSecurity Questions%4$4s method for %5$5s.', 'miniorange-2-factor-authentication' ),
		'<b>',
		'</b>',
		'<b>',
		'</b>',
		$user->user_login
	),
	MoWpnsConstants::OTP_OVER_SMS         => get_site_option( 'mo2f_customerkey' ) ? sprintf(
		/* translators: %1$s: opening bold tag, %5$s: user login, %2$s: closing bold tag, %3$s: opening bold tag, %4$s: closing bold tag */
		__( 'Enter the %1$s%5$s%2$s\'s phone number and click on %3$sSave%4$s .', 'miniorange-2-factor-authentication' ),
		'<b>',
		'</b>',
		'<b>',
		'</b>',
		$user->user_login
	) : '',
	MoWpnsConstants::OTP_OVER_WHATSAPP    => get_site_option( 'mo2f_customerkey' ) ? sprintf(
		/* translators: %1$s: opening bold tag, %5$s: user login, %2$s: closing bold tag, %3$s: opening bold tag, %4$s: closing bold tag */
		__( 'Enter the %1$s%5$s%2$s\'s phone number and click on %3$sSave%4$s .', 'miniorange-2-factor-authentication' ),
		'<b>',
		'</b>',
		'<b>',
		'</b>',
		$user->user_login
	) : '',
	MoWpnsConstants::OTP_OVER_EMAIL       => '',
	MoWpnsConstants::OUT_OF_BAND_EMAIL    => sprintf(
		/* translators: %s: user email */
		__( 'Link to configure Out of Band Email method will be sent to %s.', 'miniorange-2-factor-authentication' ),
		$user->user_email
	),
	MoWpnsConstants::HARDWARE_TOKEN       => __( 'Enter the One Time Passcode on your Hardware Token to login.', 'miniorange-2-factor-authentication' ),
);
global $mo2f_main_dir;
wp_enqueue_style( 'mo2f_intl_tel_style', plugin_dir_url( __FILE__ ) . '../includes/css/phone.min.css', array(), MO2F_VERSION );
wp_enqueue_script( 'mo2f_intl_tel_script', plugin_dir_url( __FILE__ ) . '../includes/js/phone.min.js', array( 'jquery' ), MO2F_VERSION, false );
wp_enqueue_script( 'mo_wpns_min_qrcode_script', $mo2f_main_dir . '/includes/jquery-qrcode/jquery-qrcode.min.js', array(), MO2F_VERSION, false );
wp_enqueue_style( 'mo2f_user-profile_style', $mo2f_main_dir . '/includes/css/user-profile.min.css', array(), MO2F_VERSION );
wp_enqueue_script( 'user-profile-2fa-script', $mo2f_main_dir . '/includes/js/user-profile-twofa.min.js', array(), MO2F_VERSION, false );
$twofa_heading  = __( 'Set-up 2FA method for ', 'miniorange-2-factor-authentication' );
$twofa_heading .= $mo2f_userid === $user->ID ? __( 'yourself', 'miniorange-2-factor-authentication' ) : $user->user_login;
?>
<h3>
<input type="checkbox" name="mo2f_enable_userprofile_2fa" onChange="mo2f_set_2fa_authentication()" value="1" <?php checked( $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID ) !== '' ); ?> />
	<?php echo esc_html( $twofa_heading ); ?></h3>
	<input type="hidden" name="option" value="mo2f_enable_twofactor_userprofile">
	<input type="hidden" id="mo2f_enable_user_profile_2fa_nonce"  name="mo2f_enable_user_profile_2fa_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>"/>
<table class="form-table" id="mo2fa_form-table-user-profile">
	<tr>
		<th style="text-align: left;">
			<?php esc_html_e( '2-Factor Options', 'miniorange-2-factor-authentication' ); ?>
		</th>
		<td>
			<form name="f" method="post" action="" id="mo2f_update_2fa">
			<input type="hidden" id="mo_two_factor_ajax_nonce" name="mo-two-factor-ajax-nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>"/>
				<div class="mo2fa_tab">
					<?php
					foreach ( $mo2f_two_factor_methods_descriptions as $mo2f_method => $mo2f_description ) {
						if ( in_array( $mo2f_method, $mo2f_available_methods, true ) ) {
							?>
							<button class="mo2fa_tablinks" type="button"
							<?php
							if ( ( ! empty( $mo2f_current_method ) && $mo2f_current_method === $mo2f_method ) || ( empty( $mo2f_current_method ) && MoWpnsConstants::GOOGLE_AUTHENTICATOR === $mo2f_method ) ) {
								?>
								id="defaultOpen" 
							<?php } ?>
							onclick='mo2fa_viewMethod(event, "<?php echo esc_js( $mo2f_method ); ?>")'><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $mo2f_method, 'cap_to_small' ) ); ?>
						</button>
							<?php
						}
					}
					?>
				</div>
			</form>
			<?php
			foreach ( $mo2f_two_factor_methods_descriptions as $mo2f_method => $mo2f_description ) {
				if ( in_array( $mo2f_method, $mo2f_available_methods, true ) ) {
					?>
					<div id="<?php echo esc_attr( $mo2f_method ); ?>" class="mo2fa_tabcontent">
						<p>
						<?php
						echo wp_kses_post( $mo2f_description );
						?>
			</p>

						<p><?php mo2f_methods_on_user_profile( $mo2f_method, $user, $mo2f_transient_id ); ?></p>
					</div>
					<?php
				}
			}
			?>
			</td>
		</tr>
	</table>
	<div id="wpns_nav_message"></div>
	<input type="hidden" name="MO2F_IS_ONPREM" value="<?php echo esc_attr( MO2F_IS_ONPREM ); ?>">
	<input type="hidden" name="same_user" value="<?php echo esc_attr( $mo2f_same_user ); ?>">
	<input type="hidden" name="is_registered" value="<?php echo esc_attr( $mo2f_is_registered ); ?>">
	<input type="hidden" name="mo2f-update-mobile-nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-update-mobile-nonce' ) ); ?>">
	<input type="hidden" name="mo2fa_count" id="mo2fa_count" value="1">
	<input type="hidden" name="transient_id" value="<?php echo esc_attr( $mo2f_transient_id ); ?>">
	<input type="hidden" name='method' id="method" value="NONE">
	<input type="hidden" name='mo2f_configuration_status' id="mo2f_configuration_status" value="Configuration">
	<?php

	/**
	 * Shows user profile 2fa UI.
	 *
	 * @param string $mo2f_method 2fa method name.
	 * @param object $user User object.
	 * @param string $mo2f_transient_id Transient id.
	 * @return void
	 */
	function mo2f_methods_on_user_profile( $mo2f_method, $user, $mo2f_transient_id ) {
		global $mo2fdb_queries, $mo2f_main_dir;
		$mo2f_email                  = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
		$mo2f_pass_2fa_login_session = new Miniorange_Password_2Factor_Login();
		$mo2f_trimmed_method         = $mo2f_method;
		$mo2f_is_registered          = get_site_option( 'mo2f_customerkey' );
		$mo2f_userid                 = get_current_user_id();
		if ( empty( $mo2f_email ) ) {
			$mo2fdb_queries->mo2f_update_user_details( $user->ID, array( 'mo2f_user_email' => $user->user_email ) );
		}
		$update_user_button = 'Click on %1$1sUpdate User%2$2s button to set the ';
		$mo2f_email         = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
		$mo2f_email         = ! empty( $mo2f_email ) ? $mo2f_email : $user->user_email;
		switch ( $mo2f_method ) {
			case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
				if ( $user->ID === $mo2f_userid ) {
					$cloud_onprem_interface = new MO2f_Cloud_Onprem_Interface();
					$ga_secret              = $cloud_onprem_interface->mo2f_user_profile_ga_setup( $user );
					?>
				<div class="mcol-2">
					<br>
					<form name="f" method="post" action="" id="<?php echo 'mo2f_verify_form-' . esc_attr( $mo2f_trimmed_method ); ?>">
						<table id="mo2f_setup_ga">
							<td class="bg-none"><?php esc_html_e( 'Enter Code:', 'miniorange-2-factor-authentication' ); ?></td> 
							<td><input type="tel" class="mo2f_table_textbox" style="margin-left: 1%; margin-right: 1%;  width:200px;" name="google_auth_code" id="textbox-GoogleAuthenticator" value="" pattern="[0-9]{4,8}" title="<?php esc_attr_e( 'Enter OTP:', 'miniorange-2-factor-authentication' ); ?>"/></td>
							<td><a id="save-GoogleAuthenticator" name="save_GA" class="button button1" ><?php esc_html_e( 'Verify and Save', 'miniorange-2-factor-authentication' ); ?></a></td>
						</table>

						<input type="hidden" name="ga_secret" value="<?php echo esc_attr( $ga_secret ); ?>">
					</form>
				</div>
					<?php
				} else {
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( $update_user_button . '%1$1s' . MoWpnsConstants::mo2f_convert_method_name( $mo2f_method, 'cap_to_small' ) . '%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
						'<b>',
						'</b>',
					);
				}
				break;
			case MoWpnsConstants::SECURITY_QUESTIONS:
				if ( $mo2f_userid === $user->ID ) {
					$common_helper = new Mo2f_Common_Helper();
					$common_helper->mo2f_configure_kba_questions( $user );
				}

				break;
			case MoWpnsConstants::OTP_OVER_SMS:
				if ( ! $mo2f_is_registered ) {
					esc_html_e( 'Please register with miniOrange for using this method.', 'miniorange-2-factor-authentication' );
				} else {
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( $update_user_button . '%1$1sOTP Over SMS%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
						'<b>',
						'</b>',
					);
					$mo2f_user_phone = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_phone', $user->ID );
					$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : get_site_option( 'user_phone_temp' );
					?>
				<form name="f" method="post" action="" id="<?php echo esc_attr( 'mo2f_verify_form-' . $mo2f_trimmed_method ); ?>">

					<table id="mo2f_setup_sms">
						<td class="bg-none"><?php esc_html_e( 'Authentication codes will be sent to ', 'miniorange-2-factor-authentication' ); ?></td> 
						<td><input class="mo2f_table_textbox" style="width:200px;" name="verify_phone" id="<?php echo 'textbox-' . esc_attr( $mo2f_trimmed_method ); ?>" value="<?php echo esc_attr( $user_phone ); ?>" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" required="true" title="<?php esc_attr_e( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ); ?>"/></td>
						<td><a id="<?php echo 'save-' . esc_attr( $mo2f_trimmed_method ); ?>" name="save" class="button button1" ><?php esc_html_e( 'Save', 'miniorange-2-factor-authentication' ); ?></a></td>
					</table>
				</form>
					<?php
				}
				break;
			case MoWpnsConstants::OTP_OVER_WHATSAPP:
				if ( ! $mo2f_is_registered ) {
					esc_html_e( 'Please register with miniOrange for using this method.', 'miniorange-2-factor-authentication' );
				} else {
					printf(
						/* Translators: %s: bold tags */
						esc_html( __( $update_user_button . '%1$1sOTP Over WhatsApp%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
						'<b>',
						'</b>',
					);
					$mo2f_user_phone = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_whatsapp', $user->ID );
					$user_phone      = $mo2f_user_phone ? $mo2f_user_phone : get_site_option( 'user_phone_temp' );
					?>
					<form name="f" method="post" action="" id="<?php echo esc_attr( 'mo2f_verify_form-' . $mo2f_trimmed_method ); ?>">

						<table id="mo2f_setup_sms">
							<td class="bg-none"><?php esc_html_e( 'Authentication codes will be sent to ', 'miniorange-2-factor-authentication' ); ?></td> 
							<td><input class="mo2f_table_textbox" style="width:200px;" name="verify_phone" id="<?php echo 'textbox-' . esc_attr( $mo2f_trimmed_method ); ?>" value="<?php echo esc_attr( $user_phone ); ?>" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" required="true" title="<?php esc_attr_e( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ); ?>"/></td>
							<td><a id="<?php echo 'save-' . esc_attr( $mo2f_trimmed_method ); ?>" name="save" class="button button1" ><?php esc_html_e( 'Save', 'miniorange-2-factor-authentication' ); ?></a></td>
						</table>
					</form>
					<?php
				}
				break;
			case MoWpnsConstants::OTP_OVER_EMAIL:
			case MoWpnsConstants::OUT_OF_BAND_EMAIL:
				if ( ! $mo2fdb_queries->mo2f_check_if_user_exists( $user->ID ) ) {
					$content = $mo2f_pass_2fa_login_session->create_user_in_miniorange( $user->ID, $mo2f_email, $mo2f_method );
				}
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( $update_user_button . '%1$1s' . MoWpnsConstants::mo2f_convert_method_name( $mo2f_method, 'cap_to_small' ) . '%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
					'<b>',
					'</b>',
				);
				break;
		}
		$mo2fdb_queries->delete_user_login_sessions( $user->ID );
	}
	?>
