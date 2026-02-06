<?php
/** This file contains functions regarding mobile login or passwordless login.
 *
 * @package miniorange-2-factor-authentication/handler/twofa
 */

namespace TwoFA\Handler\Twofa;

use TwoFA\Handler\Twofa\MO2f_Utility;
use TwoFA\Handler\Twofa\Miniorange_Password_2Factor_Login;
use WP_Error;
use TwoFA\Traits\Instance;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 */
require dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'mo2f-common-login-onprem-cloud.php';

if ( ! class_exists( 'Miniorange_Mobile_Login' ) ) {
	/**
	 * Mobile Login class
	 */
	class Miniorange_Mobile_Login {

		use Instance;

		/**
		 * This function enqueues custom login script.
		 *
		 * @return void
		 */
		public function custom_login_enqueue_scripts() {

			wp_enqueue_script( 'jquery' );
			$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css', dirname( __DIR__ ) );
			$bootstrappath = str_replace( '/handler/includes/css', '/includes/css', $bootstrappath );
			wp_enqueue_style( 'bootstrap_script', $bootstrappath, array(), MO2F_VERSION );
			wp_enqueue_script( 'bootstrap_script', plugins_url( 'includes/js/bootstrap.min.js', dirname( __DIR__ ) ), array(), MO2F_VERSION, false );
		}
		/**
		 * This function is useful for hide login form.
		 *
		 * @return void
		 */
		public function mo_2_factor_hide_login() {

			$bootstrappath = plugins_url( 'includes/css/bootstrap.min.css', dirname( __DIR__ ) );
			$bootstrappath = str_replace( '/handler/includes/css', '/includes/css', $bootstrappath );
			if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'includes/css/hide-login-form.min.css' ) ) {
				$hidepath = plugins_url( 'includes/css/hide-login-form.min.css', dirname( __DIR__ ) );
			}
			$hidepath = str_replace( '/handler/includes/css', '/includes/css', $hidepath );

			wp_register_style( 'hide-login', $hidepath, array(), MO2F_VERSION );
			wp_register_style( 'bootstrap', $bootstrappath, array(), MO2F_VERSION );
			wp_enqueue_style( 'hide-login' );
			wp_enqueue_style( 'bootstrap' );
		}

		/**
		 * This function login with password when phonelogin enabled.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_login_with_password_when_phonelogin_enabled() {

			if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'includes/css/show-login.min.css' ) ) {
				wp_register_style( 'show-login', plugins_url( 'includes/css/show-login.min.css', dirname( __DIR__ ) ), array(), MO2F_VERSION );
			}
			wp_enqueue_style( 'show-login' );
		}
		/**
		 * This function is useful for login form fields
		 *
		 * @return void
		 */
		public function mo_2_factor_show_wp_login_form_when_phonelogin_enabled() {
			?>
		<script>
			var content = ' <a href="javascript:void(0)" id="backto_mo" onClick="mo2fa_backtomologin()" style="float:right">← Back</a>';
			jQuery('#login').append(content);

			function mo2fa_backtomologin() {
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
			<?php
		}
		/**
		 * This function show login.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_login() {

			if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'includes/css/hide-login-form.min.css' ) ) {
				$hidepath = plugins_url( 'includes/css/hide-login-form.min.css', dirname( __DIR__ ) );
			}
			if ( file_exists( plugin_dir_path( dirname( __DIR__ ) ) . 'includes/css/show-login.min.css' ) ) {
				$showpath = plugins_url( 'includes/css/show-login.min.css', dirname( __DIR__ ) );
			}

			if ( get_option( 'mo2f_enable_login_with_2nd_factor' ) ) {
				wp_register_style( 'show-login', $hidepath, array(), MO2F_VERSION );
			} else {
				wp_register_style( 'show-login', $showpath, array(), MO2F_VERSION );
			}
			wp_enqueue_style( 'show-login' );
		}
		/**
		 * This function handle wp login.
		 *
		 * @return void
		 */
		public function mo_2_factor_show_wp_login_form() {

			$mo2f_enable_login_with_2nd_factor = get_option( 'mo2f_enable_login_with_2nd_factor' );

			?>
		<div class="mo2f-login-container">
			<?php if ( ! $mo2f_enable_login_with_2nd_factor ) { ?>
				<div style="position: relative" class="or-container">
					<div class="login_with_2factor_inner_div"></div>
					<h2 class="login_with_2factor_h2"><?php esc_html_e( 'or', 'miniorange-2-factor-authentication' ); ?></h2>
				</div>
			<?php } ?>			
			<br>
			<div class="mo2f-button-container" id="mo2f_button_container">
				<input type="text" name="mo2fa_usernamekey" id="mo2fa_usernamekey" autofocus="true"
				placeholder="<?php esc_attr_e( 'Username', 'miniorange-2-factor-authentication' ); ?>"/>
				<p>			
					<input type="button" name="miniorange_login_submit" style="width:100% !important;"
						onclick="mouserloginsubmit();" id="miniorange_login_submit"
						class="button button-primary button-large"
						value="<?php esc_attr_e( 'Login with 2nd factor', 'miniorange-2-factor-authentication' ); ?>"/>
				</p>
				<br><br><br>
				<?php
				if ( ! $mo2f_enable_login_with_2nd_factor ) {
					?>
					<br><br><?php } ?>
			</div>
		</div>

		<script>
			jQuery(window).scrollTop(jQuery('#mo2f_button_container').offset().top);

			function mouserloginsubmit() {
				var username = jQuery('#mo2fa_usernamekey').val();
				var recap    = jQuery('#g-recaptcha-response').val();
				if(document.getElementById("mo2fa-g-recaptcha-response-form") !== null){
				document.getElementById("mo2fa-g-recaptcha-response-form").elements[0].value = username;
				document.getElementById("mo2fa-g-recaptcha-response-form").elements[1].value = recap;			
				jQuery('#mo2fa-g-recaptcha-response-form').submit();
				}
			}

			jQuery('#mo2fa_usernamekey').keypress(function (e) {
				if (e.which == 13) {//Enter key pressed
					e.preventDefault();
					var username = jQuery('#mo2fa_usernamekey').val();
					if(document.getElementById("mo2fa-g-recaptcha-response-form") !== null){
						document.getElementById("mo2fa-g-recaptcha-response-form").elements[0].value = username;
						jQuery('#mo2fa-g-recaptcha-response-form').submit();
					}
				}

			});
		</script>
			<?php
		}
		/**
		 * This function have login footer
		 *
		 * @return void
		 */
		public function miniorange_login_footer_form() {

			$session_id_encrypt    = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing -- Added in Login footer form.
			$pass2fa_login_session = new Miniorange_Password_2Factor_Login();
			if ( is_null( $session_id_encrypt ) ) {
				$session_id_encrypt = $pass2fa_login_session->create_session();
			}

			?>
		<input type="hidden" name="miniorange_login_nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>
		<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" hidden>
			<input type="hidden" name="miniorange_mobile_validation_failed_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-mobile-validation-failed-nonce' ) ); ?>"/>
				<input type="hidden" id="sessids" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		</form>
		<form name="f" id="mo2fa-g-recaptcha-response-form" method="post" action="" hidden>
			<input type="text" name="mo2fa_username" id="mo2fa_username" hidden/>
			<input type="text" name="g-recaptcha-response" id = 'g-recaptcha-response' hidden/>
			<input type="hidden" name="miniorange_login_nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'miniorange-2-factor-login-nonce' ) ); ?>"/>
				<input type="hidden" id="sessid" name="session_id"
				value="<?php echo esc_attr( $session_id_encrypt ); ?>"/>
		</form>
		<script>
		jQuery(document).ready(function () {
			var session_ids="<?php echo esc_js( $session_id_encrypt ); ?>";
				if (document.getElementById('loginform') != null) {
					jQuery("#user_pass").after( "<input type='hidden' id='sessid' name='session_id' value='"+session_ids+"'/>");
					jQuery(".wp-hide-pw").addClass('mo2fa_visible');			   
				}
		});
		</script>
			<?php
		}
	}
}
?>
