<?php
/**
 * This file contains the information regarding custom login form support.
 *
 * @package miniorange-2-factor-authentication/views/twofa
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$mo2f_login_forms = array(
	'WooCommerce Login'     => array(
		'form_logo'  => 'woocommerce',
		'form_guide' => $mo2f_two_factor_premium_doc['Woocommerce'],
	),
	'Elementor Pro'         => array(
		'form_logo'  => 'elementor-pro',
		'form_guide' => $mo2f_two_factor_premium_doc['Elementor Pro'],
	),
	'Ultimate Member Login' => array(
		'form_logo'  => 'ultimate_member',
		'form_guide' => $mo2f_two_factor_premium_doc['Ultimate Member'],
	),
	'Admin Custom Login'    => array(
		'form_logo'  => 'Admin_Custom_Login',
		'form_guide' => $mo2f_two_factor_premium_doc['Admin Custom Login'],
	),
	'Login with Ajax'       => array(
		'form_logo'  => 'login-with-ajax',
		'form_guide' => $mo2f_two_factor_premium_doc['Login with Ajax'],
	),
);

$mo2f_registration_forms = array(
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
	<div id="mo2f_login_btn" class="mo2f_toggle_button mo2f-active">Login Forms</div>
	<div id="mo2f_register_button" class="mo2f_toggle_button mo2f-active">Registration Forms</div>
</div>
<div class="" id="mo2f_login_form_settings">
	<div class="mo2f-settings-div">
		<div class="mo2f-settings-head">
			<span><?php esc_html_e( 'Login forms supported by miniOrange 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="flex">
			<?php
			foreach ( $mo2f_login_forms as $mo2f_key => $mo2f_value ) {
				?>
					<a href="<?php echo esc_url( $mo2f_value['form_guide'] ); ?>" target="_blank" rel="noopener noreferrer" class="mo2f_forms_advertise mo2f_login_forms_guide_links">
						<div class="text-center "><img height=40 width=40 src="<?php echo esc_url( plugins_url( 'includes/images/' . esc_attr( $mo2f_value['form_logo'] ) . '.png', dirname( __DIR__ ) ) ); ?>"/></div><div class="text-center my-mo-2"><?php echo esc_html( $mo2f_key ); ?></div>
					</a>
				<?php
			}
			?>

		</div>
		<div class="text-mo-tertiary-txt ml-mo-8 mo-margin-bottom-20" > 
		<?php
				printf(
					/* Translators: %s: bold tags */
					esc_html( __( '%1$1sNote:%2$12s If you do not find your login form in the above list, you can integrate your form using the below settings to enable the 2FA on the same.', 'miniorange-2-factor-authentication' ) ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- The $text is a single string literal
					'<b>',
					'</b>',
				);
				?>
		</div>
		<div class="mo2f-all-inclusive-plan">
			<div class="mo2f-settings-head">
			<label class="mo2f_checkbox_container"><input type="checkbox" id="mo2f_enable_login_form" <?php echo checked( (int) get_site_option( 'mo2f_enable_custom_login_form' ) ); ?>/><span class="mo2f-settings-checkmark"></span></label>
				<span><?php esc_html_e( 'Enable 2FA On Any Custom Login Form', 'miniorange-2-factor-authentication' ); ?></span>
			</div>
			<div class="mo2f-sub-settings-div mo2f-all-inclusive-plan">
				<span><?php esc_html_e( 'Enter the selectors of your login form', 'miniorange-2-factor-authentication' ); ?></span>
				<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'all-inclusive', MoWpnsConstants::MO2F_PREMIUM_1PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

			</div>
			<div class="mo2f-sub-settings-div mo2f-all-inclusive-plan">
				<div class="mt-mo-2"><?php esc_html_e( 'URL of Login Form', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter Login Form URL e.g. https://example.com/login" id="mo2f_login_url_selector" value="<?php echo esc_attr( $mo2f_form_url ); ?>"></div>
				<div class="mt-mo-2"><?php esc_html_e( 'Email Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
				<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter email field selector e.g. #login-email" id="mo2f_login_email_selector" value="<?php echo esc_attr( $mo2f_email_selector ); ?>"></div>
				<table class="w-full">
					<tr><td>
						<div ><?php esc_html_e( 'Password Field Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
						<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter password field selector  e.g. #login-pass" id="mo2f_login_password_selector" value="<?php echo esc_attr( $mo2f_pass_selector ); ?>"></div>
					</td><td>
						<div><?php esc_html_e( 'Password Label Selector', 'miniorange-2-factor-authentication' ); ?></div>
						<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter passowrd label selector e.g #login-pass-label" id="mo2f_login_password_label" value="<?php echo esc_attr( $mo2f_pass_label_selector ); ?>"></div>
					</td></tr>
					<tr><td>
						<div><?php esc_html_e( 'Submit Button Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
						<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter submit button selector e.g. #login-submit" id="mo2f_login_submit_selector" value="<?php echo esc_attr( $mo2f_submit_selector ); ?>"></div>
					</td><td>
						<div><?php esc_html_e( 'Form Selector ', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></div>
						<div class="mb-mo-2 mo2f-login-form-selector"><input type="text" class="w-full" placeholder="Enter form selector e.g. #login-form" id="mo2f_login_form_selector" value="<?php echo esc_attr( $mo2f_form_selector ); ?>"></div>
					</td></tr>
				</table>
			</div>
			<div class="justify-start flex" id="mo2f_enable_custom_login_save">
				<div class="mo2f_enable_custom_login_save_button mo2f-all-inclusive-plan">
				<button id="mo2f_custom_login_form_config_save" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="" id="mo2f_registration_form_settings">
<div class="mo2f-settings-div hidden">
		<div class="mo2f-settings-head">
			<span><?php esc_html_e( 'Registration forms supported by miniOrange 2FA', 'miniorange-2-factor-authentication' ); ?></span>
		</div>
		<div class="flex">
			<?php
			foreach ( $mo2f_registration_forms as $mo2f_key => $mo2f_value ) {
				?>
					<div class="mo2f_forms_advertise">
						<div class="text-center"><img height=40 width=40 src="<?php echo esc_url( plugins_url( 'includes/images/' . esc_attr( $mo2f_value['form_logo'] ) . '.png', dirname( __DIR__ ) ) ); ?>"/></div><div class="text-center my-mo-2"><?php echo esc_html( $mo2f_key ); ?></div>
					</div>
				<?php
			}
			?>

		</div>
	</div>

	<div class="mo2f-settings-div mo2f-basic-plan">
		<div class="mo2f-settings-head">
		<form name="form_custom_form_config" method="post" action="" id="mo2f_custom_form_config" >
			<label class="mo2f_checkbox_container"><input type="checkbox" name="mo2fa_use_shortcode_config" id="mo2fa_use_shortcode_config" <?php echo checked( get_site_option( 'mo2f_enable_form_shortcode' ) ); ?>/><span class="mo2f-settings-checkmark"></span></label>
			<span><?php esc_html_e( 'Enable OTP Verification on your Registration Form', 'miniorange-2-factor-authentication' ); ?>
			<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'basic', MoWpnsConstants::MO2F_PREMIUM_3PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

		</div>
		<div class="mo2f-sub-settings-div hidden">
			<?php
			$mo2f_is_registered = get_site_option( 'mo2f_customerkey' ) ? get_site_option( 'mo2f_customerkey' ) : 'false';
			if ( 'false' === $mo2f_is_registered ) {
				?>
			<br>
			<div class="mo2f_register_error">
				<a onclick="registerwithminiOrange()"> <?php esc_html_e( 'Register/Login', 'miniorange-2-factor-authentication' ); ?></a> <?php esc_html_e( 'with miniOrange to enable OTP verifcation on registration form.', 'miniorange-2-factor-authentication' ); ?>
			</div>
				<?php
			}
			?>
		</div>
		<div class="mo2f-sub-settings-div mo2f-basic-plan">
			<div class="mb-mo-3"><?php esc_html_e( 'Step 1: Select Authentication Method', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mo2f-settings-items">
			<div class="mr-mo-4">
			<input type="radio" name="mo2f_auth_method" id="mo2f_method_email" value="email" 
			<?php checked( 'EMAIL' === $mo2f_registration_auth_type ); ?>>
			<?php esc_html_e( 'Email Verification', 'miniorange-2-factor-authentication' ); ?>
			</div>

			<div class="mr-mo-4">
			<input type="radio" name="mo2f_auth_method" id="mo2f_method_phone" value="phone" 
			<?php checked( 'SMS' === $mo2f_registration_auth_type ); ?>>
			<?php esc_html_e( 'Phone Verification', 'miniorange-2-factor-authentication' ); ?>
			</div>			
		</div>
		</div>
		<div class="mo2f-sub-settings-div flex-col mo2f-basic-plan">
			<div class="my-mo-3"><?php esc_html_e( 'Step 2: Select Form', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="px-mo-4 text-mo-title">
				<div>
				<select id="regFormList" name="regFormList">
							<?php
							$mo2f_default_wordpress = array(
								'formName'       => 'Wordpress Registration',
								'formSelector'   => '#registerform',
								'emailSelector'  => '#user_email',
								'submitSelector' => '#wp-submit',
							);
							$mo2f_wc_form           = array(
								'formName'       => 'WooCommerce',
								'formSelector'   => '.woocommerce-form-register',
								'emailSelector'  => '#reg_email',
								'submitSelector' => '.woocommerce-form-register__submit',
							);
							$mo2f_bb_form           = array(
								'formName'       => 'BB Press',
								'formSelector'   => '.bbp-login-form',
								'emailSelector'  => '#user_email',
								'submitSelector' => '.user-submit',
							);
							$mo2f_login_press_form  = array(
								'formName'       => 'Login Press',
								'formSelector'   => '#registerform',
								'emailSelector'  => '#user_email',
								'submitSelector' => '#wp-submit',
							);
							$mo2f_user_reg_form     = array(
								'formName'       => 'User Registration',
								'formSelector'   => '.register',
								'emailSelector'  => '#user_email',
								'submitSelector' => '.ur-submit-button',
							);
							$mo2f_custom_form       = array(
								'formName'       => 'Custom Form',
								'formSelector'   => '',
								'emailSelector'  => '',
								'submitSelector' => '',
							);
							$mo2f_forms_array       = array( 'forms' => array( $mo2f_default_wordpress, $mo2f_wc_form, $mo2f_bb_form, $mo2f_login_press_form, $mo2f_user_reg_form, $mo2f_custom_form ) );
							$mo2f_selected_form     = $mo2f_registration_form_name;
							$mo2f_forms_count       = count( $mo2f_forms_array['forms'] );
							for ( $mo2f_i = 0; $mo2f_i < $mo2f_forms_count; $mo2f_i++ ) {
								$mo2f_form_name      = $mo2f_forms_array['forms'];
								$mo2f_form_name_slug = strtolower( str_replace( ' ', '', esc_html( $mo2f_form_name[ $mo2f_i ]['formName'] ) ) );
								echo '<option value="' . esc_attr( $mo2f_form_name_slug ) . '" ' . selected( $mo2f_selected_form, $mo2f_form_name_slug, false ) . '>' . esc_html( $mo2f_form_name[ $mo2f_i ]['formName'] ) . '</option>';
								?>
								<?php
							}
							?>
						</select>
			</div>
			<div id="mo2fa_selector_div">
			<h4 id="enterMessage" name="enterMessage" class="hidden"><?php esc_html_e( 'Enter Selectors for your Form', 'miniorange-2-factor-authentication' ); ?></h4>
			<div id="mo2fa_formDiv">
				<h4><?php esc_html_e( 'Form Selector', 'miniorange-2-factor-authentication' ); ?><span class="mo2f_forms_asterisk">*</span></h4>
				<input type="text" value="<?php echo esc_html( $mo2f_registration_form_selector ); ?>" class="w-full" name="mo2f_shortcode_form_selector" id="mo2f_shortcode_form_selector" placeholder="example #form_id" >
			</div>
			<div id="mo2fa_emailDiv">
				<h4><?php esc_html_e( 'Email Field Selector', 'miniorange-2-factor-authentication' ); ?> <span class="mo2f_forms_asterisk">*</span></h4>
				<input type="text" class="w-full" value="<?php echo esc_html( $mo2f_registration_email_field ); ?>" name="mo2f_shortcode_email_selector" id="mo2f_shortcode_email_selector" placeholder="example #email_field_id" >
			</div>
			<div id="mo2fa_phoneDiv" class="hidden">
			<h4><?php esc_html_e( 'Phone Field Selector', 'miniorange-2-factor-authentication' ); ?> <span class="mo2f_forms_asterisk">*</span></h4>
			<input type="text" class="w-full" value="<?php echo esc_html( $mo2f_registration_phone_selector ); ?>" 
			name="mo2f_shortcode_phone_selector" id="mo2f_shortcode_phone_selector" placeholder="example #phone_field_id" >
			</div>
			<div id="mo2fa_submitDiv">
				<h4><?php esc_html_e( 'Submit Button Selector', 'miniorange-2-factor-authentication' ); ?> <span class="mo2f_forms_asterisk">*</span></h4>
				<input type="text" class="w-full" value="<?php echo esc_html( $mo2f_registration_submit_selector ); ?>" name="mo2f_shortcode_submit_selector" id="mo2f_shortcode_submit_selector" placeholder="example #submit_button_id" >
			</div>
			</div>
			<br>
			<div class="mr-mo-4"><input type="checkbox" name="mo2f_form_submit_after_validation" id="mo2f_form_submit_after_validation" value="yes" <?php checked( 'true' === $mo2f_registration_form_submit ); ?>><?php esc_html_e( 'Submit form after validating OTP', 'miniorange-2-factor-authentication' ); ?></div>
		</div>
		</div>
		<div class="mo2f-sub-settings-div mo2f-basic-plan">
			<div class="my-mo-3"><?php esc_html_e( 'Step 3: Copy Shortcode', 'miniorange-2-factor-authentication' ); ?></div>
			<div class="mo2f-settings-items flex-col">
				<div><?php esc_html_e( 'If your form is not the default WordPress registration form, add the following shortcode to your registration or checkout page to enable OTP verification:', 'miniorange-2-factor-authentication' ); ?></div></br>  
				<div>[mo2f_enable_register]</div>
			</div>
		</div>
		<div class="justify-start flex mo2f-basic-plan" id="mo2f_enable_custom_login_save"><div class="mo2f_enable_custom_login_save_button"><button id="mo2f_form_config_save" class="mo2f-save-settings-button"><?php esc_html_e( 'Save Settings', 'miniorange-2-factor-authentication' ); ?></button></div></div>
		<input type="hidden" id="mo2f_nonce_save_form_settings" name="mo2f_nonce_save_form_settings"
				value="<?php echo esc_attr( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>"/>
	</div>
	</form>
	</div>
</div>
<?php
	global $mo2f_main_dir;
	wp_enqueue_script( 'forms-script', $mo2f_main_dir . '/includes/js/forms.min.js', array(), MO2F_VERSION, false );
	wp_localize_script(
		'forms-script',
		'forms',
		array(
			'nonce'         => esc_js( wp_create_nonce( 'mo2f-login-settings-ajax-nonce' ) ),
			'formArray'     => $mo2f_form_name,
			'isRegistered'  => esc_js( $mo2f_is_registered ),
			'authTypePhone' => esc_js( MoWpnsConstants::OTP_OVER_SMS ),
			'authTypeEmail' => esc_js( MoWpnsConstants::OTP_OVER_EMAIL ),
		)
	);
	?>
<script>
	jQuery("#mo_2fa_two_fa").addClass("side-nav-active");
	jQuery("#formintegration").addClass("mo2f-subtab-active");
</script>
