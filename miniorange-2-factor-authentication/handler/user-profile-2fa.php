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
$is_registered = empty( get_site_option( 'mo2f_customerkey' ) ) ? false : true;
$userrole      = $user->roles;
$roles         = (array) $user->roles;
$main_handler  = new Mo2f_Main_Handler();
$flag          = $main_handler->mo2f_check_if_twofa_is_enabled( $user );
if ( ! current_user_can( 'administrator', $user->ID ) || ( ! MO2F_IS_ONPREM && ! $is_registered ) || ! $flag ) {
	return;
} elseif ( ! MO2F_IS_ONPREM && ! $is_registered ) {
	return;
}
$userid            = get_current_user_id();
$common_helper     = new Mo2f_Common_Helper();
$available_methods = $common_helper->fetch_methods( $user );
$available_methods = apply_filters( 'mo2f_basic_plan_settings_filter', $available_methods, 'fetch_twofa_methods', array( 'user' => $user ) );
if ( ! $available_methods ) {
	return;
}
$transient_id = MO2f_Utility::random_str( 20 );

MO2f_Utility::mo2f_set_transient( $transient_id, 'mo2f_user_id', $user->ID );
$same_user = $user->ID === $userid;
global $mo2fdb_queries;
$current_method         = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
$twofactor_transactions = new Mo2fDB();
$exceeded               = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $user->ID ), 'is_user_limit_exceeded', array() );
if ( $exceeded ) {
	return;
}
$user_column_exists = $mo2fdb_queries->mo2f_check_if_user_exists( $user->ID );
$email              = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
if ( empty( $email ) ) {
	$mo2fdb_queries->mo2f_update_user_details( $user->ID, array( 'mo2f_user_email' => $user->user_email ) );
}
$email                  = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
$email                  = ! empty( $email ) ? $email : $user->user_email;
$pass_2fa_login_session = new Miniorange_Password_2Factor_Login();

$two_factor_methods_descriptions = array(
	MoWpnsConstants::GOOGLE_AUTHENTICATOR => 'administrator' === $user->roles[0] ? 'Please scan the below QR code using Google Authenticator app.' : 'Link to configure Google authenticator method will be sent to ' . $user->user_email . '.',
	MoWpnsConstants::SECURITY_QUESTIONS   => 'Please click on %1$1sUpdate User%2$2s button in order to set the %3$3sSecurity Questions%4$4s method for ' . $user->user_login . '.',
	MoWpnsConstants::OTP_OVER_SMS         => get_site_option( 'mo2f_customerkey' ) ? 'Enter the ' . $user->user_login . '\'s phone number and click on %1$1sSave%2$2s .' : '',
	MoWpnsConstants::OTP_OVER_WHATSAPP    => get_site_option( 'mo2f_customerkey' ) ? 'Enter the ' . $user->user_login . '\'s phone number and click on %1$1sSave%2$2s .' : '',
	MoWpnsConstants::OTP_OVER_EMAIL       => '',
	MoWpnsConstants::OUT_OF_BAND_EMAIL    => '',
	MoWpnsConstants::HARDWARE_TOKEN       => 'Enter the One Time Passcode on your Hardware Token to login.',
);
global $main_dir;
wp_enqueue_style( 'mo2f_intl_tel_style', plugin_dir_url( __FILE__ ) . '../includes/css/phone.min.css', array(), MO2F_VERSION );
wp_enqueue_script( 'mo2f_intl_tel_script', plugin_dir_url( __FILE__ ) . '../includes/js/phone.min.js', array( 'jquery' ), MO2F_VERSION, false );
wp_enqueue_script( 'mo_wpns_min_qrcode_script', $main_dir . '/includes/jquery-qrcode/jquery-qrcode.min.js', array(), MO2F_VERSION, false );
wp_enqueue_style( 'mo2f_user-profile_style', $main_dir . '/includes/css/user-profile.min.css', array(), MO2F_VERSION );
wp_enqueue_script( 'user-profile-2fa-script', $main_dir . '/includes/js/user-profile-twofa.min.js', array(), MO2F_VERSION, false );
$twofa_heading  = 'Set-up 2FA method for ';
$twofa_heading .= $userid === $user->ID ? 'yourself' : $user->user_login;
?>
<h3>
<input type="checkbox" name="mo2f_enable_userprofile_2fa" onChange="mo2f_set_2fa_authentication()" value="1" <?php checked( $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_configured_2FA_method', $user->ID ) !== '' ); ?> />
	<?php esc_html_e( $twofa_heading, 'miniorange-2-factor-authentication' ); //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal ?></h3>
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
					foreach ( $two_factor_methods_descriptions as $method => $description ) {
						if ( in_array( $method, $available_methods, true ) ) {
							?>
							<button class="mo2fa_tablinks" type="button"
							<?php
							if ( ( ! empty( $current_method ) && $current_method === $method ) || ( empty( $current_method ) && MoWpnsConstants::GOOGLE_AUTHENTICATOR === $method ) ) {
								?>
								id="defaultOpen" 
							<?php } ?>
							onclick='mo2fa_viewMethod(event, "<?php echo esc_js( $method ); ?>")'><?php echo esc_html( MoWpnsConstants::mo2f_convert_method_name( $method, 'cap_to_small' ) ); ?>
						</button>
							<?php
						}
					}
					?>
				</div>
			</form>
			<?php
			foreach ( $two_factor_methods_descriptions as $method => $description ) {
				if ( in_array( $method, $available_methods, true ) ) {
					?>
					<div id="<?php echo esc_attr( $method ); ?>" class="mo2fa_tabcontent">
						<p>
						<?php
						printf(
						/* Translators: %s: bold tags */
							esc_html( __( $description, 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
							'<b>',
							'</b>',
							'<b>',
							'</b>',
						);
						?>
			</p>

						<p><?php methods_on_user_profile( $method, $user, $transient_id ); ?></p>
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
	<input type="hidden" name="same_user" value="<?php echo esc_attr( $same_user ); ?>">
	<input type="hidden" name="is_registered" value="<?php echo esc_attr( $is_registered ); ?>">
	<input type="hidden" name="mo2f-update-mobile-nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f-update-mobile-nonce' ) ); ?>">
	<input type="hidden" name="mo2fa_count" id="mo2fa_count" value="1">
	<input type="hidden" name="transient_id" value="<?php echo esc_attr( $transient_id ); ?>">
	<input type="hidden" name='method' id="method" value="NONE">
	<input type="hidden" name='mo2f_configuration_status' id="mo2f_configuration_status" value="Configuration">
	<?php

	/**
	 * Shows user profile 2fa UI.
	 *
	 * @param string $method 2fa method name.
	 * @param object $user User object.
	 * @param string $transient_id Transient id.
	 * @return void
	 */
	function methods_on_user_profile( $method, $user, $transient_id ) {
		global $mo2fdb_queries,$main_dir;
		$email                  = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
		$pass_2fa_login_session = new Miniorange_Password_2Factor_Login();
		$trimmed_method         = $method;
		$is_registered          = get_site_option( 'mo2f_customerkey' );
		$userid                 = get_current_user_id();
		if ( empty( $email ) ) {
			$mo2fdb_queries->mo2f_update_user_details( $user->ID, array( 'mo2f_user_email' => $user->user_email ) );
		}
		$update_user_button = 'Click on %1$1sUpdate User%2$2s button to set the ';
		$email              = $mo2fdb_queries->mo2f_get_user_detail( 'mo2f_user_email', $user->ID );
		$email              = ! empty( $email ) ? $email : $user->user_email;
		switch ( $method ) {
			case MoWpnsConstants::GOOGLE_AUTHENTICATOR:
				if ( $user->ID === $userid ) {
					$cloud_onprem_interface = new MO2f_Cloud_Onprem_Interface();
					$ga_secret              = $cloud_onprem_interface->mo2f_user_profile_ga_setup( $user );
					?>
				<div class="mcol-2">
					<br>
					<form name="f" method="post" action="" id="<?php echo 'mo2f_verify_form-' . esc_attr( $trimmed_method ); ?>">
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
						esc_html( __( $update_user_button . '%1$1s' . MoWpnsConstants::mo2f_convert_method_name( $method, 'cap_to_small' ) . '%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
						'<b>',
						'</b>',
						'<b>',
						'</b>',
					);
				}
				break;
			case MoWpnsConstants::SECURITY_QUESTIONS:
				if ( $userid === $user->ID ) {
					$common_helper = new Mo2f_Common_Helper();
					$common_helper->mo2f_configure_kba_questions( $user );
				}

				break;
			case MoWpnsConstants::OTP_OVER_SMS:
				if ( ! $is_registered ) {
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
				<form name="f" method="post" action="" id="<?php echo esc_attr( 'mo2f_verify_form-' . $trimmed_method ); ?>">

					<table id="mo2f_setup_sms">
						<td class="bg-none"><?php esc_html_e( 'Authentication codes will be sent to ', 'miniorange-2-factor-authentication' ); ?></td> 
						<td><input class="mo2f_table_textbox" style="width:200px;" name="verify_phone" id="<?php echo 'textbox-' . esc_attr( $trimmed_method ); ?>" value="<?php echo esc_attr( $user_phone ); ?>" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" required="true" title="<?php esc_attr_e( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ); ?>"/></td>
						<td><a id="<?php echo 'save-' . esc_attr( $trimmed_method ); ?>" name="save" class="button button1" ><?php esc_html_e( 'Save', 'miniorange-2-factor-authentication' ); ?></a></td>
					</table>
				</form>
					<?php
				}
				break;
			case MoWpnsConstants::OTP_OVER_WHATSAPP:
				if ( ! $is_registered ) {
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
					<form name="f" method="post" action="" id="<?php echo esc_attr( 'mo2f_verify_form-' . $trimmed_method ); ?>">

						<table id="mo2f_setup_sms">
							<td class="bg-none"><?php esc_html_e( 'Authentication codes will be sent to ', 'miniorange-2-factor-authentication' ); ?></td> 
							<td><input class="mo2f_table_textbox" style="width:200px;" name="verify_phone" id="<?php echo 'textbox-' . esc_attr( $trimmed_method ); ?>" value="<?php echo esc_attr( $user_phone ); ?>" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" required="true" title="<?php esc_attr_e( 'Enter phone number without any space or dashes', 'miniorange-2-factor-authentication' ); ?>"/></td>
							<td><a id="<?php echo 'save-' . esc_attr( $trimmed_method ); ?>" name="save" class="button button1" ><?php esc_html_e( 'Save', 'miniorange-2-factor-authentication' ); ?></a></td>
						</table>
					</form>
					<?php
				}
				break;
			case MoWpnsConstants::OTP_OVER_EMAIL:
			case MoWpnsConstants::OUT_OF_BAND_EMAIL:
				if ( ! $mo2fdb_queries->mo2f_check_if_user_exists( $user->ID ) ) {
					$content = $pass_2fa_login_session->create_user_in_miniorange( $user->ID, $email, $method );
				}
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( $update_user_button . '%1$1s' . MoWpnsConstants::mo2f_convert_method_name( $method, 'cap_to_small' ) . '%2$2s method for ' . $user->user_login . '.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
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
