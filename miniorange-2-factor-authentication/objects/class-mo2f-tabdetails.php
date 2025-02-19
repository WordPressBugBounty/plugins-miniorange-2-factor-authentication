<?php
/**Load Interface TabDetails
 *
 * @package miniOrange-2-factor-authentication/objects
 */

namespace TwoFA\Objects;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Traits\Instance;
use TwoFA\Objects\Mo2f_Nav_Tab_Details;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_TabDetails' ) ) {
	/**
	 * This class is used to define the Tab details interface functions taht needs to be implementated
	 */
	final class Mo2f_TabDetails {

		use Instance;

		/**
		 * Array of Mo2f_PluginPageDetails Object detailing
		 * all the page menu options.
		 *
		 * @var array[Mo2f_PluginPageDetails] $tab_details
		 */
		public $tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var string $parent_slug
		 */
		public $parent_slug;

		/**
		 * Nav tab variable.
		 *
		 * @var array $mo2fa_nav_tab_details
		 */
		public $mo2fa_nav_tab_details;

		/**
		 * Whitelabel tab variable.
		 *
		 * @var array $wl_tab_details
		 */
		public $wl_tab_details;

		/**
		 * Reports tab variable
		 *
		 * @var array $reports_tab_details
		 */
		public $reports_tab_details;

		/**
		 * My account tab variable
		 *
		 * @var array $ma_tab_details
		 */
		public $ma_tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var array $ip_blocking_tab_details
		 */
		public $ip_blocking_tab_details;

		/** Private constructor to avoid direct object creation */
		private function __construct() {
			$lv_needed               = apply_filters( 'mo2f_is_lv_needed', false );
			$common_helper           = new Mo2f_Common_Helper();
			$this->parent_slug       = $common_helper->mo2f_get_default_page( $lv_needed );
			$url                     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$request_uri             = remove_query_arg( 'addon', $url );
			$can_user_manage_options = current_user_can( 'manage_options' );

			$this->mo2fa_nav_tab_details   = ( $lv_needed ? array(
				'Quick Setup',
				'Settings',
				'Advanced Features',
				'Form Integration',
			) : array(
				'Quick Setup',
				'Settings',
				'Advanced Features',
				'Form Integration',
			) );
			$this->wl_tab_details          = $lv_needed ? array(
				'Login Popup',
				'Email Templates',
				'2FA Customizations',
			) : array(
				'Email Templates',
				'2FA Customizations',
				'Login Popup',
			);
			$this->reports_tab_details     = array(
				'Users 2FA Status',
				'Remembered Devices',
				'Login Report',
			);
			$this->ma_tab_details          = array(
				'My Account',
				'Setup Your 2FA',
			);
			$this->ip_blocking_tab_details = array(
				'Advanced Blocking',
				'IP Blacklist',
			);
			$this->tab_details             = array(
				Mo2f_Tabs::TWO_FACTOR      => new Mo2f_PluginPageDetails(
					'2FA Configurations',
					'mo_2fa_two_fa',
					'read',
					$request_uri,
					'2faconfigurations' . DIRECTORY_SEPARATOR . 'quicksetup.php',
					$can_user_manage_options,
					$this->mo2fa_nav_tab_details,
				),
				Mo2f_Tabs::WHITE_LABELLING => new Mo2f_PluginPageDetails(
					'White Labelling',
					'mo_2fa_white_labelling',
					'manage_options',
					$request_uri,
					$lv_needed ? 'whitelabelling' . DIRECTORY_SEPARATOR . 'loginpopup.php' : 'whitelabelling' . DIRECTORY_SEPARATOR . 'emailtemplates.php',
					$can_user_manage_options,
					$this->wl_tab_details,
				),
				Mo2f_Tabs::REPORTS         => new Mo2f_PluginPageDetails(
					'Reports',
					'mo_2fa_reports',
					'manage_options',
					$request_uri,
					'reports' . DIRECTORY_SEPARATOR . 'users2fastatus.php',
					$can_user_manage_options,
					$this->reports_tab_details,
				),
				Mo2f_Tabs::MY_ACCOUNT      => new Mo2f_PluginPageDetails(
					'My Account',
					'mo_2fa_my_account',
					'read',
					$request_uri,
					$can_user_manage_options ? 'myaccount' . DIRECTORY_SEPARATOR . 'myaccount.php' : 'myaccount' . DIRECTORY_SEPARATOR . 'setupyour2fa.php',
					true,
					$this->ma_tab_details,
				),
				Mo2f_Tabs::UPGRADE         => new Mo2f_PluginPageDetails(
					'Upgrade',
					'mo_2fa_upgrade',
					'manage_options',
					$request_uri,
					'upgrade.php',
					$can_user_manage_options,
					array(),
				),
				Mo2f_Tabs::TROUBLESHOOTING => new Mo2f_PluginPageDetails(
					'FAQs',
					'mo_2fa_troubleshooting',
					'manage_options',
					$request_uri,
					'faqs.php',
					$can_user_manage_options,
					array(),
				),
			);
			if ( ! $lv_needed ) {
				$troubleshooting_index = array_search( Mo2f_Tabs::TROUBLESHOOTING, array_keys( $this->tab_details ), true );
				$setup_wizard_tab      = array(
					Mo2f_Tabs::SETUPWIZARD_SETTINGS => new Mo2f_PluginPageDetails(
						'Setup Wizard',
						'mo2f-setup-wizard',
						'manage_options',
						$request_uri,
						'setupwizard.php',
						$can_user_manage_options,
						array()
					),
				);
				$this->tab_details     = array_slice( $this->tab_details, 0, $troubleshooting_index, true ) + $setup_wizard_tab + array_slice( $this->tab_details, $troubleshooting_index, null, true );
			}
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'site_option' ) ) {
				array_push(
					$this->tab_details,
					new Mo2f_PluginPageDetails(
						'IP Blocking',
						'mo_2fa_advancedblocking',
						'manage_options',
						$request_uri,
						'ipblocking' . DIRECTORY_SEPARATOR . 'advancedblocking.php',
						$can_user_manage_options && MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'site_option' ),
						$this->ip_blocking_tab_details,
					)
				);
			}
		}
	}
}
