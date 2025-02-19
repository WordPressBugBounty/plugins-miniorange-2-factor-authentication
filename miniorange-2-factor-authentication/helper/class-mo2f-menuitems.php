<?php
/**Load adminstrator changes for Mo2f_MenuItems
 *
 * @package miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;
use TwoFA\Objects\Mo2f_TabDetails;
use TwoFA\Handler\Mo2f_Main_Handler;
use TwoFA\Database\Mo2fDB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class simply adds menu items for the plugin
 * in the WordPress dashboard.
 */
if ( ! class_exists( 'Mo2f_MenuItems' ) ) {

	/**
	 * Mo2f_MenuItems class
	 */
	final class Mo2f_MenuItems {

		use Instance;

		/**
		 * The URL for the plugin icon to be shown in the dashboard
		 *
		 * @var string
		 */
		private $callback;

		/**
		 * The call back function for the menu items
		 *
		 * @var string
		 */
		private $menu_slug;

		/**
		 * The slug for the main menu
		 *
		 * @var string
		 */
		private $menu_logo;

		/**
		 * Array of PluginPageDetails Object detailing
		 * all the page menu options.
		 *
		 * @var array $tab_details
		 */
		private $tab_details;

		/**
		 * Mo2f_MenuItems constructor.
		 */
		private function __construct() {
			$this->callback    = array( $this, 'mo_wpns' );
			$this->menu_logo   = plugin_dir_url( dirname( __FILE__ ) ) . 'includes/images/miniorange_icon.png';
			$tab_details       = Mo2f_TabDetails::instance();
			$this->tab_details = $tab_details->tab_details;
			$this->menu_slug   = $tab_details->parent_slug;
			$this->add_main_menu();
			$this->add_sub_menus();
		}
		/**
		 * Adding MainMenu.
		 */
		private function add_main_menu() {
			global $mo2fdb_queries;
			$user                = wp_get_current_user();
			$main_handler        = new Mo2f_Main_Handler();
			$flag                = $main_handler->mo2f_check_if_twofa_is_enabled( $user );
			$user_limit_exceeded = apply_filters( 'mo2f_basic_plan_settings_filter', $mo2fdb_queries->check_alluser_limit_exceeded( $user->ID ), 'is_user_limit_exceeded', array() );
			if ( ( $flag || current_user_can( 'manage_options' ) ) && ! $user_limit_exceeded ) {
				add_menu_page(
					'miniOrange 2-Factor',
					'miniOrange 2-Factor',
					'read',
					$this->menu_slug,
					$this->callback,
					$this->menu_logo
				);
			}

		}

		/**
		 * Adding MainMenu.
		 */
		private function add_sub_menus() {
			foreach ( $this->tab_details as $tab_detail ) {
				if ( $tab_detail->show_in_nav && 'mo_2fa_upgrade' !== $tab_detail->menu_slug ) {
					add_submenu_page(
						$this->menu_slug,
						$tab_detail->page_title,
						$tab_detail->page_title,
						$tab_detail->capability,
						$tab_detail->menu_slug,
						$this->callback
					);
				}
			}
			if ( isset( $_GET['action'] ) && 'reset_edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
				$mo2fa_hook_page = add_users_page( 'Reset 2nd Factor', null, 'manage_options', 'reset', array( $this, 'mo2f_reset_2fa_for_users_by_admin' ), 66 );
			}
		}

		/**
		 * Adding some options and calling functions after activation.
		 *
		 * @return void
		 */
		public function mo_wpns() {
			global $wpns_db_queries, $mo2fdb_queries;
			$wpns_db_queries->mo_plugin_activate();
			$mo2fdb_queries->mo_plugin_activate();
			add_site_option( 'EmailTransactionCurrent', 30 );
			include dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'controllers/main-controller.php';
		}

		/**
		 * Users page to reset 2FA for specific user
		 *
		 * @return void
		 */
		public function mo2f_reset_2fa_for_users_by_admin() {
			$nonce = wp_create_nonce( 'ResetTwoFnonce' );
			if ( ! isset( $_GET['mo2f_reset-2fa'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['mo2f_reset-2fa'] ) ), 'reset_edit' ) ) {
				wp_send_json( 'ERROR' );
			}
			if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'reset_edit' ) {
				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
				$user_info = get_userdata( $user_id );
				if ( is_numeric( $user_id ) && $user_info ) {
					?>
				<div class="wrap">
					<form method="post" name="reset2fa" id="reset2fa" action="<?php echo esc_url( admin_url() . 'admin.php?page=mo_2fa_reports&subpage=users2fastatus' ); ?>">
						<h1>Reset 2nd Factor</h1>

						<p>You have specified this user for reset:</p>

						<ul>
							<li>ID #<?php echo esc_html( $user_info->ID ); ?>: <?php echo esc_html( $user_info->user_login ); ?></li>
						</ul>
						<input type="hidden" name="userid" value="<?php echo esc_attr( $user_id ); ?>">
						<input type="hidden" name="miniorange_reset_2fa_option" value="mo_reset_2fa">
						<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Confirm Reset"></p>
					</form>
				</div>

					<?php
				} else {
					?>
				<h2> Invalid User Id </h2>
					<?php
				}
			}
		}

	}
}
