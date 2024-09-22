<?php
/**
 * This file contains the information regarding custom login form support.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

// Needed in both.

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$overlay_on_premium_features = MO2F_PREMIUM_PLAN ? '' : 'mo2f-premium-feature';
$crown                       = MO2F_PREMIUM_PLAN ? '' : '<svg width="18" class="ml-mo-4 -mb-mo-0.5" height="18" viewBox="0 0 24 24" fill="none">
<g id="d4a43e0162b45f718f49244b403ea8f4">
	<g id="4ea4c3dca364b4cff4fba75ac98abb38">
		<g id="2413972edc07f152c2356073861cb269">
			<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
		</g>
	</g>
</g>
</svg>';

$login_forms = array(
	'WooCommerce Login'     => array(
		'form_logo' => 'woocommerce',
		'form_link' => 'Woocommerce',
	),
	'Elementor Pro'         => array(
		'form_logo' => 'elementor-pro',
		'form_link' => 'Elementor Pro',
	),
	'Ultimate Member Login' => array(
		'form_logo' => 'ultimate_member',
		'form_link' => 'Ultimate Member',
	),
	'Admin Custom Login'    => array(
		'form_logo' => 'Admin_Custom_Login',
		'form_link' => 'Admin Custom Login',
	),
	'Login with Ajax'       => array(
		'form_logo' => 'login-with-ajax',
		'form_link' => 'Login with Ajax',
	),
);

$registration_forms = array(
	'WooCommerce Registration'     => array(
		'form_logo' => 'woocommerce',
		'form_link' => 'Woocommerce',
	),
	'User Registration'            => array(
		'form_logo' => 'user_registration',
		'form_link' => 'User Registration',
	),
	'Ultimate Member Registration' => array(
		'form_logo' => 'ultimate_member',
		'form_link' => 'Ultimate Member',
	),
	'Registration Magic'           => array(
		'form_logo' => 'RegistrationMagic_Custom_Registration_Forms_and_User_Login',
		'form_link' => 'RegistrationMagic',
	),
);

?>
<div id="toggle" class="mo2f_forms_toggle">
	<div id="mo2f_login_btn" class="mo2f_forms_toggle_login mo2f-active">Login Forms</div>
	<div id="mo2f_register_button" class="mo2f_forms_toggle_register mo2f-active">Registration Forms</div>
</div>
<div class="" id="mo2f_login_form_settings">
	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<span><?php esc_html_e( 'Login forms supported by miniOrange 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="flex">
			<?php
			foreach ( $login_forms as $key => $value ) {
				?>
					<div class="mo2f_forms_advertise">
						<div class="text-center"><img height=40 width=40 src="<?php echo esc_url( plugins_url( 'includes/images/' . esc_attr( $value['form_logo'] ) . '.png', dirname( dirname( __FILE__ ) ) ) ); ?>"/></div><div class="text-center my-mo-2"><?php echo esc_html( $key ); ?></div>
					</div>
				<?php
			}
			?>

		</div>
	</div>
	<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<div class="mo2f-settings-head">
		<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_login_form" <?php echo checked( '1' === get_site_option( 'mo2f_enable_custom_login_form' ) ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable Any Custom Login Form', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="mo2f-sub-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
			<span><?php esc_html_e( 'Enter the selectors of your login form', 'miniorange-2-factor-authentication' ); ?></span><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>
		</div>
		<div class="mo2f-sub-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
			<div class="mt-mo-2"><?php esc_html_e( 'URL of Login Form ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
			<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter Login Form URL" id="mo2f_login_form_url" value="<?php echo get_option( 'mo2f_login_form_url' ) ? esc_attr( get_option( 'mo2f_login_form_url' ) ) : esc_url( wp_login_url() ); ?>"></div>
			<div class="mt-mo-2"><?php esc_html_e( 'Email Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
			<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter email field selector" id="mo2f_login_form_url" value="<?php echo get_option( 'mo2f_login_email_selector' ) ? esc_attr( get_option( 'mo2f_login_email_selector' ) ) : '#login-email'; ?>"></div>
			<table class="w-full">
				<tr><td>
					<div><?php esc_html_e( 'Password Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
					<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter password field selector" id="mo2f_login_form_url" value="<?php echo get_option( 'mo2f_login_password_selector' ) ? esc_attr( get_option( 'mo2f_login_password_selector' ) ) : '#login-pwd'; ?>"></div>
				</td><td>
					<div><?php esc_html_e( 'Password Label Selector', 'miniorange-2-factor-authentication' ); ?></div>
					<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter passowrd label selector" id="mo2f_login_form_url" value="<?php echo get_option( 'mo2f_login_password_label' ) ? esc_attr( get_option( 'mo2f_login_password_label' ) ) : '#login-pwd-label'; ?>"></div>
				</td></tr>
				<tr><td>
					<div><?php esc_html_e( 'Submit Button Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
					<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter submit button selector" id="mo2f_submit_selector" value="<?php echo get_option( 'mo2f_login_submit_selector' ) ? esc_attr( get_option( 'mo2f_submit_selector' ) ) : '#login-submit'; ?>"></div>
				</td><td>
					<div><?php esc_html_e( 'Form Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
					<div class="mb-mo-2"><input type="text" class="w-full" placeholder="Enter form selector" id="mo2f_login_form_selector" value="<?php echo get_option( 'mo2f_login_form_selector' ) ? esc_attr( get_option( 'mo2f_login_form_selector' ) ) : '#login-form'; ?>"></div>
				</td></tr>
			</table>
		</div>
		<div class="justify-start flex" id="mo2f_enable_custom_login_save"><div class="mo2f_enable_custom_login_save_button <?php echo esc_attr( $overlay_on_premium_features ); ?>"><button id="mo2f_enable_custom_login_save_button" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
	</div>
</div>

<div class="" id="mo2f_registration_form_settings">
<div class="mo2f-settings-div hidden">
		<div class="mo2f-settings-head">
			<span><?php esc_html_e( 'Registration forms supported by miniOrange 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="flex">
			<?php
			foreach ( $registration_forms as $key => $value ) {
				?>
					<div class="mo2f_forms_advertise">
						<div class="text-center"><img height=40 width=40 src="<?php echo esc_url( plugins_url( 'includes/images/' . esc_attr( $value['form_logo'] ) . '.png', dirname( dirname( __FILE__ ) ) ) ); ?>"/></div><div class="text-center my-mo-2"><?php echo esc_html( $key ); ?></div>
					</div>
				<?php
			}
			?>

		</div>
	</div>
	<div class="mo2f-settings-div <?php echo esc_attr( $overlay_on_premium_features ); ?>">
		<div class="mo2f-settings-head">
			<label class="mo2f_checkbox_container"><input type="checkbox" name="mo2f_use_shortcode_config" id="mo2f_use_shortcode_config" <?php echo checked( '1' === get_site_option( 'enable_form_shortcode' ) ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable OTP Verification on your Registration Form', 'miniorange-2-factor-authentication' ); ?><?php echo $crown; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?></span>
		</div>
		<div class="mo2f-sub-settings-div hidden">
			<?php
			$is_registered = get_site_option( 'mo2f_customerkey' ) ? get_site_option( 'mo2f_customerkey' ) : 'false';
			if ( 'false' === $is_registered ) {
				?>
			<br>
			<div class="mo2f_register_error">
				<a onclick="registerwithminiOrange()"> <?php esc_html_e( 'Register/Login', 'miniorange-2-factor-authentication' ); ?></a> <?php esc_html_e( 'with miniOrange to enable OTP verifcation on registration form.', 'miniorange-2-factor-authentication' ); ?>
			</div>
				<?php
			}
			?>
		</div>
		<div class="mo2f-sub-settings-div  <?php echo esc_attr( $overlay_on_premium_features ); ?>">
			<div class="mb-mo-3"><?php esc_html_e( 'Step 1: Select Authentication Method', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mo2f-settings-items">
				<div class="mr-mo-4"><input type="checkbox" name="mo2f_method_email" id="mo2f_method_email" value="email" <?php checked( MoWpnsConstants::OTP_OVER_EMAIL === get_site_option( 'mo2f_custom_auth_type' ) || 'both' === get_site_option( 'mo2f_custom_auth_type' ) ); ?>><?php esc_html_e( 'Email Verification', 'miniorange-2-factor-authentication' ); ?></div>

				<div class="mr-mo-4"><input type="checkbox" name="mo2f_method_phone" id="mo2f_method_phone" value="phone" <?php checked( MoWpnsConstants::OTP_OVER_SMS === get_site_option( 'mo2f_custom_auth_type' ) || 'both' === get_site_option( 'mo2f_custom_auth_type' ) ); ?>><?php esc_html_e( 'Phone Verification', 'miniorange-2-factor-authentication' ); ?></div>
			</div>
		</div>
		<div class="mo2f-sub-settings-div flex-col  <?php echo esc_attr( $overlay_on_premium_features ); ?>">
			<div class="my-mo-3"><?php esc_html_e( 'Step 2: Select Form', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="px-mo-4 text-mo-title">
				<div>
				<select id="regFormList" name="regFormList">
					<?php

					$default_wordpress = array(
						'formName'       => 'Wordpress Registration',
						'formSelector'   => '#wordpress-register',
						'emailSelector'  => '#wordpress-register',
						'submitSelector' => '#wordpress-register',
					);

					$wc_form = array(
						'formName'       => 'Woo Commerce',
						'formSelector'   => '.woocommerce-form-register',
						'emailSelector'  => '#reg_email',
						'submitSelector' => '.woocommerce-form-register__submit',
					);

					$bb_form = array(
						'formName'       => 'Buddy Press',
						'formSelector'   => '#signup-form',
						'emailSelector'  => '#signup_email',
						'submitSelector' => '#submit',
					);

					$login_press_form = array(
						'formName'       => 'Login Press',
						'formSelector'   => '#registerform',
						'emailSelector'  => '#user_email',
						'submitSelector' => '#wp-submit',
					);

					$user_reg_form = array(
						'formName'       => 'User Registration',
						'formSelector'   => '.register',
						'emailSelector'  => '#user_email',
						'submitSelector' => '.ur-submit-button',
					);

					$pm_pro_form = array(
						'formName'       => 'Paid MemberShip Pro',
						'formSelector'   => '#pmpro_form',
						'emailSelector'  => '#bemail',
						'phoneSelector'  => '#bphone',
						'submitSelector' => '#pmpro_btn-submit',
					);

					$custom_form = array(
						'formName'       => 'Custom Form',
						'formSelector'   => '',
						'emailSelector'  => '',
						'submitSelector' => '',
					);

					$forms_array     = array( 'forms' => array( $default_wordpress, $wc_form, $bb_form, $login_press_form, $user_reg_form, $pm_pro_form, $custom_form ) );
					$form_size_array = count( $forms_array['forms'] );
					for ( $i = 0; $i < $form_size_array; $i++ ) {
						$form_name = $forms_array['forms'];
						echo '<option' . ( get_site_option( 'mo2f_custom_form_name' ) === $form_name[ $i ]['formSelector'] ? ' selected ' : '' ) . ' value=' . esc_attr( strtolower( str_replace( ' ', '', esc_attr( $form_name[ $i ]['formName'] ) ) ) ) . '>' . esc_html( $form_name[ $i ]['formName'] ) . '</option>';
						?>
						<?php
					}
					?>
				</select>
			</div>
			<div id="formDiv" class="hidden">
				<div class="mt-mo-2"><?php esc_html_e( 'Form Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-4"><input type="text" class="w-full" placeholder="#register-form" name="mo2f_shortcode_form_selector" id="mo2f_shortcode_form_selector" value="<?php echo get_option( 'mo2f_custom_form_name' ) ? esc_attr( get_option( 'mo2f_custom_form_name' ) ) : ''; ?>"></div>
			</div>
			<div id="emailDiv" class="hidden">
				<div class="mt-mo-2"><?php esc_html_e( 'Email Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-4"><input type="text" class="w-full" placeholder="#register-email" name="mo2f_shortcode_email_selector" id="mo2f_shortcode_email_selector" value="<?php echo get_option( 'mo2f_custom_email_selector' ) ? esc_attr( get_option( 'mo2f_custom_email_selector' ) ) : ''; ?>"></div>
			</div>
			<div id="phoneDiv">
				<div class="mt-mo-2"><?php esc_html_e( 'Phone Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-4"><input type="text" class="w-full" placeholder="#register-phone" name="mo2f_shortcode_phone_selector" id="mo2f_shortcode_phone_selector" value="<?php echo get_option( 'mo2f_custom_phone_selector' ) ? esc_attr( get_option( 'mo2f_custom_phone_selector' ) ) : ''; ?>"></div>
			</div>
			<div id="submitDiv" class="hidden">
				<div class="mt-mo-2"><?php esc_html_e( 'Submit Button Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-4"><input type="text" class="w-full" placeholder="#register-submit" name="mo2f_shortcode_submit_selector" id="mo2f_shortcode_submit_selector" value="<?php echo get_option( 'mo2f_custom_submit_selector' ) ? esc_attr( get_option( 'mo2f_custom_submit_selector' ) ) : ''; ?>"></div>
			</div>
			<div class="mr-mo-4"><input type="checkbox" name="mo2f_form_submit_after_validation" id="mo2f_form_submit_after_validation" value="yes" <?php checked( 'true' === get_option( 'mo2f_form_submit_after_validation' ) ); ?>><?php esc_html_e( 'Submit form after validating OTP', 'miniorange-2-factor-authentication' ); ?></div>
		</div>
		</div>
		<div class="mo2f-sub-settings-div  <?php echo esc_attr( $overlay_on_premium_features ); ?>">
			<div class="my-mo-3"><?php esc_html_e( 'Step 3: Copy Shortcode', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mo2f-settings-items flex-col">
				<div><?php esc_html_e( 'Add this on the page where you have your registration form/check out form to enable OTP verification for the same', 'miniorange-2-factor-authentication' ); ?></div></br>  
				<div>[mo2fa_enable_register]</div>
			</div>
		</div>
		<div class="justify-start flex  <?php echo esc_attr( $overlay_on_premium_features ); ?>" id="mo2f_enable_custom_login_save"><div class="mo2f_enable_custom_login_save_button"><button id="mo2f_form_config_save" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
		<input type="hidden" id="mo2f_nonce_save_form_settings" name="mo2f_nonce_save_form_settings"
				value="<?php echo esc_attr( wp_create_nonce( 'mo2f-nonce-save-form-settings' ) ); ?>"/>
	</div>
	</div>
</div>
<?php
	global $main_dir;
	wp_enqueue_script( 'forms-script', $main_dir . '/includes/js/forms.min.js', array(), MO2F_VERSION, false );
	wp_localize_script(
		'forms-script',
		'forms',
		array(
			'nonce'          => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
			'formArray'      => wp_json_encode( $form_name ),
			'isRegistered'   => esc_js( $is_registered ),
			'formSelector'   => esc_attr( get_site_option( 'mo2f_custom_form_name' ) ),
			'submitSelector' => esc_attr( get_site_option( 'mo2f_custom_submit_selector' ) ),
			'emailSelector'  => esc_attr( get_site_option( 'mo2f_custom_email_selector' ) ),
			'authTypePhone'  => esc_js( MoWpnsConstants::OTP_OVER_SMS ),
			'authTypeEmail'  => esc_js( MoWpnsConstants::OTP_OVER_EMAIL ),
		)
	);
	?>
