<?php
/**
 * This file contains all the functions and views regarding setup wizard flow
 *
 * @package miniorange-2-factor-authentication/views
 */

// Needed in both.

namespace TwoFA\Views;

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\MoWpnsUtility;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Mo2f_Setup_Wizard' ) ) {
	/**
	 * Includes all the functions and views regarding setup wizard flow
	 */
	class Mo2f_Setup_Wizard {

		/**
		 * Total steps present in the setup wizard.
		 *
		 * @var array
		 */
		private $wizard_steps;

		/**
		 * Step on which user is present during setup wizard.
		 *
		 * @var string
		 */
		private $current_step;

		/**
		 * Includes styles , scripts and redirected URLs.
		 *
		 * @return void
		 */
		public function mo2f_setup_page() {
			// Get page argument from $_GET array.

			$page = ( isset( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			if ( empty( $page ) || 'mo2f-setup-wizard' !== $page ) {
				return;
			}
			if ( get_site_option( 'mo2f_setup_complete' ) === 1 ) {
				$this->mo2f_redirect_to_2fa_dashboard();
			}
			$wizard_steps       = array(
				'welcome'                => array(
					'content' => array( $this, 'mo2f_step_welcome' ),
				),
				'settings_configuration' => array(
					'content' => array( $this, 'mo2f_step_global_2fa_methods' ),
					'save'    => array( $this, 'mo2f_step_global_2fa_methods_save' ),
				),
				'finish'                 => array(
					'content' => array( $this, 'mo_2fa_setup_wizard_completed' ),
				),
			);
			$this->wizard_steps = apply_filters( 'mo2f_wizard_default_steps', $wizard_steps );

			// Set current step.
			$current_step       = ( isset( $_GET['current-step'] ) ) ? sanitize_text_field( wp_unslash( $_GET['current-step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			$this->current_step = ! empty( $current_step ) ? $current_step : current( array_keys( $this->wizard_steps ) );
			wp_register_style( 'mo_2fa_admin_setupWizard', plugins_url( 'includes' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'setup-wizard.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_enqueue_script( 'mo2f_setup_wizard', plugins_url( 'includes' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'setup-wizard.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, false );
			$save_step = ( isset( $_POST['save_step'] ) ) ? sanitize_text_field( wp_unslash( $_POST['save_step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading POST parameter for checking the saved step, doesn't require nonce verification for the 1st window.
			if ( ! empty( $save_step ) && ! empty( $this->wizard_steps[ $this->current_step ]['save'] ) ) {
				call_user_func( $this->wizard_steps[ $this->current_step ]['save'] );
			}
			wp_enqueue_script( 'jquery' );
			$this->mo2f_setup_page_header();
			wp_register_script( 'mo2f_qr_code_minjs', plugins_url( '/includes/jquery-qrcode/jquery-qrcode.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_register_script( 'mo2f_phone_js', plugins_url( '/includes/js/phone.min.js', dirname( __FILE__ ) ), array(), MO2F_VERSION, true );
			wp_register_style( 'mo_2fa_admin_setupWizard', plugins_url( 'includes/css/setup-wizard.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			wp_register_style( 'mo2f_phone_css', plugins_url( 'includes/css/phone.min.css', dirname( __FILE__ ) ), array(), MO2F_VERSION );
			echo '<head>';
			wp_print_scripts( 'mo2f_qr_code_minjs' );
			wp_print_scripts( 'mo2f_phone_js' );
			wp_print_styles( 'mo2f_phone_css' );
			wp_print_styles( 'mo_2fa_admin_setupWizard' );
			wp_print_styles( 'dashicons' );
			echo '</head>';
			$this->mo2f_setup_page_content();
			?>
			<?php
			exit();
		}

		/**
		 * Shows congratulations message.
		 *
		 * @return void
		 */
		public function mo_2fa_setup_wizard_completed() {
			?>
			<p class="mo2f-step-show"> <?php esc_html_e( 'Step 4 of 4', 'miniorange-2-factor-authentication' ); ?></p>
			<div style="text-align: center;">
				<h3 style="text-align:center;font-size: xx-large;"> <?php esc_html_e( 'Congratulations!', 'miniorange-2-factor-authentication' ); ?> </h3>
				<br>
				<?php esc_html_e( 'You have successfully configured the two-factor authentication.', 'miniorange-2-factor-authentication' ); ?>
				<br><br><br>
				<input type="button" name="mo2f_next_step4" id="mo2f_next_step4" class="mo2f-modal__btn button button-primary" value="Advance Settings" />
			</div>
			<script>
				jQuery('#mo2f_next_step4').click(function(e) {

					localStorage.setItem("last_tab", 'unlimittedUser_2fa');
					window.location.href = '<?php echo esc_js( admin_url() ); ?>' + 'admin.php?page=mo_2fa_two_fa';
				});
			</script>
			<?php
		}

		/**
		 * Load script in header on setup wizard.
		 *
		 * @return void
		 */
		public function mo2f_setup_wizard_header() {
			// both.
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>

			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e( 'miniOrange 2-factor Setup Wizard', 'miniorange-2-factor-authentication' ); ?></title>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_print_scripts' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>

			<body class="mo2f_setup_wizard">
			<?php
		}
		/**
		 * Header of the setup wizard
		 *
		 * @return void
		 */
		private function mo2f_setup_page_header() {
			?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'miniOrange 2FA &rsaquo; Setup Wizard', 'miniorange-2-factor-authentication' ); ?></title>
			<?php
			wp_print_styles( 'mo_2fa_admin_setupWizard' );
			wp_print_scripts( 'jquery' );
			wp_print_scripts( 'jquery-ui-core' );
			wp_print_scripts( 'mo2f_setup_wizard' );
			?>
		<head>
		<body class="mo2f_body">
				<header class="mo2f-setup-wizard-header">
					<img width="70px" height="auto" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/miniorange-new-logo.png' ); ?>" alt="<?php esc_attr_e( 'miniOrange 2-factor Logo', 'miniorange-2-factor-authentication' ); ?>" >
					<h1><?php esc_html_e( 'miniOrange 2-factor authentication Setup', 'miniorange-2-factor-authentication' ); ?></h1>
					<span class="mo2f_loader" id="mo2f_loader" style="display: none;"></span>
				</header>
			<?php
		}
		/**
		 * To redirect to the dashboard.
		 *
		 * @return void
		 */
		private function mo2f_redirect_to_2fa_dashboard() {
			wp_safe_redirect(
				add_query_arg(
					array( 'page' => 'mo_2fa_two_fa' ),
					admin_url( 'admin.php' )
				)
			);
			exit();
		}
		/**
		 * Get the next setup during setup wizard.
		 *
		 * @return string
		 */
		private function mo2f_get_next_step() {
			// Get current step.
			$current_step = $this->current_step;

			// Array of step keys.
			$keys = array_keys( $this->wizard_steps );
			if ( end( $keys ) === $current_step ) { // If last step is active then return WP Admin URL.
				return admin_url();
			}

			// Search for step index in step keys.
			$step_index = array_search( $current_step, $keys, true );
			if ( false === $step_index ) { // If index is not found then return empty string.
				return '';
			}

			// Return next step.
			return add_query_arg( 'current-step', $keys[ $step_index + 1 ] );
		}
		/**
		 * Call respective function based on current step.
		 *
		 * @return void
		 */
		private function mo2f_setup_page_content() {
			?>
		<div class="mo2f-setup-content">
			<?php
			if ( ! empty( $this->wizard_steps[ $this->current_step ]['content'] ) ) {
				call_user_func( $this->wizard_steps[ $this->current_step ]['content'] );
			}
			?>
		</div>
			<?php
		}
		/**
		 * Step View of welcome Page
		 *
		 * @return void
		 */
		private function mo2f_step_welcome() {
			$this->mo2f_welcome_step( $this->mo2f_get_next_step() );
		}
		/**
		 * Welcome step
		 *
		 * @param array $next_step url of the next step.
		 * @return void
		 */
		public function mo2f_welcome_step( $next_step ) {
			$redirect  = 'enforce-2fa';
			$admin_url = is_network_admin() ? network_admin_url() . 'admin.php?page=mo_2fa_two_fa' : admin_url() . 'admin.php?page=mo_2fa_two_fa';

			?>
		<h3><?php esc_html_e( 'Let us help you get started', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f-setup-wizard-font"><?php esc_html_e( 'This wizard will assist you with plugin configuration and the 2FA settings for you and the users on this website.', 'miniorange-2-factor-authentication' ); ?></p>

		<div class="mo2f-setup-actions">
			<a class="button button-primary"
				href="<?php echo esc_url( $next_step ); ?>">
				<?php esc_html_e( 'Let’s get started!', 'miniorange-2-factor-authentication' ); ?>
			</a>
			<a class="button button-secondary mo2f-first-time-wizard"
				href="<?php echo esc_url( $admin_url ); ?>">
				<?php esc_html_e( 'Skip Setup Wizard', 'miniorange-2-factor-authentication' ); ?>
			</a>
		</div>
			<?php
		}

		/**
		 * Setup Wizard settings
		 *
		 * @return void
		 */
		private function mo2f_step_global_2fa_methods() {
			?>
			<form method="post" class="mo2f-setup-form mo2f-form-styles" autocomplete="off">
				<?php wp_nonce_field( 'mo2f-step-choose-method' ); ?>
			<div class="mo2f-step-setting-wrapper active" data-step-title="<?php esc_html_e( 'Inline Registration', 'miniorange-2-factor-authentication' ); ?>">
				<?php $this->mo2f_inline_registration(); ?>
				<div class="mo2f-setup-actions">
					<a class="button button-primary" style="margin-left:100px;" name="next_step_setting" onclick="mo2f_change_settings()" value="<?php esc_attr_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>
			<div class="mo2f-step-setting-wrapper" data-step-title="<?php esc_html_e( 'Choose User roles', 'miniorange-2-factor-authentication' ); ?>">
				<?php $this->mo2f_select_user_roles(); ?>
				<div class="mo2f-setup-actions">
					<a href="#inlinereg" onclick="mo2f_go_back_settings()">
						<span class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons" style="float:left;">
							<svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
									<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
							</svg> <?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</a>
					<a class="button button-primary" name="next_step_setting" onclick="mo2f_change_settings()" value="<?php esc_attr_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'Continue Setup', 'miniorange-2-factor-authentication' ); ?></a>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>

			<div class="mo2f-step-setting-wrapper" data-step-title="<?php esc_html_e( 'Grace period', 'miniorange-2-factor-authentication' ); ?>">
			<?php $this->mo2f_grace_period(); ?>
				<div class="mo2f-setup-actions">
					<a href="#chooseuserroles" onclick="mo2f_go_back_settings()">
						<span class="text-with-arrow text-with-arrow-left mo2f_setup_wizard_footer_buttons" style="float:left;">
							<svg viewBox="0 0 448 512" role="img" class="icon" data-icon="long-arrow-alt-left" data-prefix="far" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="12">
									<path xmlns="http://www.w3.org/2000/svg" fill="currentColor" d="M107.515 150.971L8.485 250c-4.686 4.686-4.686 12.284 0 16.971L107.515 366c7.56 7.56 20.485 2.206 20.485-8.485v-71.03h308c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H128v-71.03c0-10.69-12.926-16.044-20.485-8.484z"></path>
							</svg> <?php esc_html_e( 'Go Back', 'miniorange-2-factor-authentication' ); ?>
						</span>
					</a>
					<button class="button button-primary save-wizard" type="submit" name="save_step" value="<?php esc_attr_e( 'All done', 'miniorange-2-factor-authentication' ); ?>"><?php esc_html_e( 'All Done', 'miniorange-2-factor-authentication' ); ?></button>
					<a href="#skipwizard" class="mo2f_setup_wizard_footer_buttons" style="float:right;"><?php esc_html_e( 'Skip Setup', 'miniorange-2-factor-authentication' ); ?></a>
				</div>
			</div>
			</form>
			<script>
				jQuery('a[href="#skipwizard"]').click(function() {
					localStorage.setItem("last_tab", 'setup_2fa');
					var nonce = "<?php echo esc_js( wp_create_nonce( 'mo-two-factor-ajax-nonce' ) ); ?>";
					var skiptwofactorstage = 'Settings Configuration';
					var data = {
						'action': 'mo_two_factor_ajax',
						'mo_2f_two_factor_ajax': 'mo2f_skiptwofactor_wizard',
						'nonce': nonce,
						'twofactorskippedon': skiptwofactorstage,
					};
					var ajax_url = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
					jQuery.post(ajax_url, data, function(response) {
						window.location.href = "<?php echo esc_url( admin_url() . 'admin.php?page=mo_2fa_two_fa' ); ?>";
					});
				});
			</script>
			<?php
		}

		/**
		 * Inline registration UI in setup Wizard
		 *
		 * @return void
		 */
		public function mo2f_inline_registration() {
			?>
		<h3 id="mo2f_login_with_mfa_settings"><?php esc_html_e( 'Prompt users to setup 2FA after login? ', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f_description">
			<?php esc_html_e( 'When you enable this, the users will be prompted to set up the 2FA method after entering username and password. Users can select from the list of all 2FA methods. Once selected, user will setup and will login to the site ', 'miniorange-2-factor-authentication' ); ?><a href="<?php echo esc_url( MoWpnsConstants::MO2F_PLUGINS_PAGE_URL ) . '/setup-login-with-any-configured-method-wordpress-2fa'; ?>" target="_blank" rel=noopener><?php esc_html_e( 'Learn more.', 'miniorange-2-factor-authentication' ); ?></a>
		</p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<label for="mo2f-use-inline-registration" style="margin-bottom: 10px; display: block;">
				<input type="radio" name="mo2f_policy[mo2f_disable_inline_registration]" id="mo2f-use-inline-registration" value="0"
				<?php checked( ! get_site_option( 'mo2f_disable_inline_registration' ) ); ?>
				>
			<span><?php esc_html_e( 'Users should setup 2FA after first login.', 'miniorange-2-factor-authentication' ); ?></span>
			</label>
			<label for="mo2f-no-inline-registration">
				<input type="radio" name="mo2f_policy[mo2f_disable_inline_registration]" id="mo2f-no-inline-registration" value="1"
				<?php checked( get_site_option( 'mo2f_disable_inline_registration' ) ); ?>
				>
				<span><?php esc_html_e( 'Users will setup 2FA in plugin dashboard', 'miniorange-2-factor-authentication' ); ?></span>
			</label>
		</fieldset>
			<?php
		}

		/**
		 * Select user roles settings
		 *
		 * @return void
		 */
		public function mo2f_select_user_roles() {
			?>
		<h3 id="mo2f_enforcement_settings"><?php esc_html_e( 'Do you want to enable 2FA for some, or all the user roles? ', 'miniorange-2-factor-authentication' ); ?></h3>
		<p class="mo2f_description">
			<?php esc_html_e( 'When you enable 2FA, the users will be prompted to configure 2FA the next time they login. Users have a grace period for configuring 2FA. You can configure the grace period and also exclude role(s) in this settings page. ', 'miniorange-2-factor-authentication' ); ?>
		</p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<div onclick="mo2f_toggle_select_roles_and_users()">
				<label for="mo2f-all-users" style="margin:.35em 0 .5em !important; display: block;">
					<input type="radio" name="mo2f_policy[mo2f-enforcement-policy]" id="mo2f-all-users" value="mo2f-all-users"
					<?php checked( get_site_option( 'mo2f-enforcement-policy' ), 'mo2f-all-users' ); ?>
					>
					<span><?php esc_html_e( 'All users', 'miniorange-2-factor-authentication' ); ?></span>
				</label>
			</div>
			<div onclick="mo2f_toggle_select_roles_and_users()">
				<label for="mo2f-certain-roles-only" style="margin:.35em 0 .5em !important; display: block;">
					<?php $checked = in_array( get_site_option( 'mo2f-enforcement-policy' ), array( 'mo2f-certain-roles-only', 'certain-users-only' ), true ); ?>
					<input type="radio" name="mo2f_policy[mo2f-enforcement-policy]" id="mo2f-certain-roles-only" value="mo2f-certain-roles-only"
					data-unhide-when-checked=".mo2f-grace-period-inputs"
					<?php checked( get_site_option( 'mo2f-enforcement-policy' ), 'mo2f-certain-roles-only' ); ?>
					>
					<span><?php esc_html_e( 'Only for specific roles', 'miniorange-2-factor-authentication' ); ?></span>
				</label>
			</div>
			<div id='mo2f-show-certain-roles-only' style="display:none;">
				<fieldset class="hidden mo2f-certain-roles-only-inputs">
					<div class="mo2f-line-height">
						<?php $this->mo2f_display_user_roles(); ?>
					</div>
				</fieldset>
			</div>
		</fieldset>
			<?php
		}

		/**
		 * Display User roles settings
		 *
		 * @return void
		 */
		public function mo2f_display_user_roles() {
			global $wp_roles;
			if ( is_multisite() ) {
				$first_role           = array( 'superadmin' => 'Superadmin' );
				$wp_roles->role_names = array_merge( $first_role, $wp_roles->role_names );
			}
			?>
			<input type="button" class="button button-secondary" name="mo2f_select_all_roles" id="mo2f_select_all_roles" value="Select all"/>
			<?php
			foreach ( $wp_roles->role_names as $id => $name ) {
				$setting = get_site_option( 'mo2fa_' . $id );
				?>
				<div>
					<input type="checkbox" name="mo2f_policy[mo2f-enforce-roles][]" value="<?php echo 'mo2fa_' . esc_html( $id ); ?>"
					<?php
					if ( get_site_option( 'mo2fa_' . $id ) ) {
						echo 'checked';
					} else {
						echo 'unchecked';
					}
					?>
						/>
					<?php
					echo esc_html( $name );
					?>
				</div>
				<?php
			}
		}
		/**
		 * Save the setup wizard settings
		 *
		 * @return void
		 */
		private function mo2f_step_global_2fa_methods_save() {
			// Check nonce.
			check_admin_referer( 'mo2f-step-choose-method' );
			$array                       = isset( $_POST['mo2f_policy'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mo2f_policy'] ) ) : array();
			$array['mo2f-enforce-roles'] = isset( $_POST['mo2f_policy']['mo2f-enforce-roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mo2f_policy']['mo2f-enforce-roles'] ) ) : array();
			$this->mo2f_update_plugin_settings( $array );
			wp_safe_redirect( esc_url_raw( $this->mo2f_get_next_step() ) );
			exit();
		}
		/**
		 * Save the setup wizard settings in database
		 *
		 * @param array $settings Setup wizard settings that needs to be saved.
		 * @return void
		 */
		private function mo2f_update_plugin_settings( $settings ) {
			global $wp_roles;
			foreach ( $settings as $setting => $value ) {
				$setting = sanitize_text_field( $setting );
				$value   = sanitize_text_field( $value );
				if ( 'mo2f_grace_period_value' === $setting ) {
					update_site_option( $setting, ( $value > 0 ) ? floor( $value ) : 1 );
				} else {
					update_site_option( $setting, $value );
				}
			}
			if ( isset( $settings['mo2f-enforcement-policy'] ) && 'mo2f-all-users' === $settings['mo2f-enforcement-policy'] ) {
				if ( isset( $wp_roles ) ) {
					update_site_option( 'mo2f_activate_plugin', 1 );
					foreach ( $wp_roles->role_names as $role => $name ) {
						update_site_option( 'mo2fa_' . $role, 1 );
					}
				}
			} elseif ( isset( $settings['mo2f-enforcement-policy'] ) && 'mo2f-certain-roles-only' === $settings['mo2f-enforcement-policy'] && isset( $settings['mo2f-enforce-roles'] ) && is_array( $settings['mo2f-enforce-roles'] ) ) {
				foreach ( $wp_roles->role_names as $role => $name ) {
					if ( in_array( 'mo2fa_' . $role, $settings['mo2f-enforce-roles'], true ) ) {
						update_site_option( 'mo2f_activate_plugin', 1 );
						update_site_option( 'mo2fa_' . $role, 1 );
					} else {
						update_site_option( 'mo2fa_' . $role, 0 );
					}
				}
			}
		}
		/**
		 * Display Grace period settings
		 *
		 * @return void
		 */
		private function mo2f_grace_period() {
			?>
		<h3><?php esc_html_e( 'Should users be given a grace period or should they be directly enforced for 2FA setup?', 'miniorange-2-factor-authentication' ); ?></h3>
			<p class="mo2f_description"><?php esc_html_e( 'When you configure the 2FA policies and require users to configure 2FA, they can either have a grace period to configure 2FA (users who don\'t have 2fa setup after grace period, will be enforced to setup 2FA ). Choose which method you\'d like to use:', 'miniorange-2-factor-authentication' ); ?></p>
		<fieldset class="mo2f-contains-hidden-inputs">
			<div >
		<input type="radio" style="margin-bottom: 10px;" name="mo2f_policy[mo2f_grace_period]" id="mo2f-no-grace-period" value="0" <?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'site_option' ), null ); ?>>
			<?php esc_html_e( 'Users should be directly enforced for 2FA setup.', 'miniorange-2-factor-authentication' ); ?>
			</div>
			<div style="display:inline-flex;">
				<div>
					<input type="radio" name="mo2f_policy[mo2f_grace_period]" id="mo2f-use-grace-period" value="1" <?php checked( (int) MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period', 'site_option' ), 1 ); ?> data-unhide-when-checked=".mo2f-grace-period-inputs">
				</div> 
				<div class="mo2f_setupwizard_grace_period">
					<p><?php esc_html_e( 'Give users a grace period to configure 2FA (Users will be enforced to setup 2FA after grace period expiry).', 'miniorange-2-factor-authentication' ); ?></p>
				</div>
			</div>
			<fieldset class="mo2f-grace-period-inputs" 
			<?php
			if ( ! get_site_option( 'mo2f_grace_period' ) ) {
				echo 'hidden';
			}
			?>
			>
				<br/>
				<input type="number" id="mo2f-grace-period"  name="mo2f_policy[mo2f_grace_period_value]" class="mo2f-settings-number-field" value="<?php echo esc_attr( get_site_option( 'mo2f_grace_period_value', 1 ) ); ?>" min="1">
				<label class="radio-inline">
					<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="hours"
					<?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'site_option' ), 'hours' ); ?>
					>
					<?php esc_html_e( 'hours', 'miniorange-2-factor-authentication' ); ?>
				</label>
				<label class="radio-inline">
					<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="days"
					<?php checked( MoWpnsUtility::get_mo2f_db_option( 'mo2f_grace_period_type', 'site_option' ), 'days' ); ?>
					>
					<?php esc_html_e( 'days', 'miniorange-2-factor-authentication' ); ?>
				</label>
				<?php
				/**
				 * Via that, you can change the grace period TTL.
				 *
				 * @param bool - Default at this point is true - no method is selected.
				 */
				$testing = apply_filters( 'mo2f_allow_grace_period_in_seconds', false );
				if ( $testing ) {
					?>
					<label class="radio-inline">
						<input class="js-nested" type="radio" name="mo2f_policy[mo2f_grace_period_type]" value="seconds"
						<?php checked( get_site_option( 'mo2f_grace_period_type' ), 'seconds' ); ?>
						>
						<?php esc_html_e( 'Seconds', 'miniorange-2-factor-authentication' ); ?>
					</label>
					<?php
				}
				$user                         = wp_get_current_user();
				$last_user_to_update_settings = $user->ID;
				?>
				<input type="hidden" id="mo2f_main_user" name="mo2f_policy[2fa_settings_last_updated_by]" value="<?php echo esc_attr( $last_user_to_update_settings ); ?>">
			</fieldset>
			<br/>
		</fieldset>
		<script>
			jQuery(document).ready(function($){
				jQuery("#mo2f-use-grace-period").click(function()
				{
						jQuery("#mo2f-grace-period").focus();
				});
				jQuery(".radio-inline").click(function()
				{
						jQuery("#mo2f-grace-period").focus();
				});
			});
			</script>
			<?php
		}
	}
}
