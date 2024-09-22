<?php
/**Load Interface TabDetails
 *
 * @package miniOrange-2-factor-authentication/objects
 */

namespace TwoFA\Objects;

use TwoFA\Helper\MoWpnsUtility;
use TwoFA\Traits\Instance;
use TwoFA\Objects\Mo2f_Nav_Tab_Details;

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
		 * The parent menu slug
		 *
		 * @var array $mo2fa_nav_tab_details
		 */
		public $mo2fa_nav_tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var array $wl_tab_details
		 */
		public $wl_tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var array $adv_tab_details
		 */
		public $adv_tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var array $ip_blocking_tab_details
		 */
		public $ip_blocking_tab_details;

		/** Private constructor to avoid direct object creation */
		private function __construct() {
			$this->parent_slug             = 'mo_2fa_two_fa';
			$url                           = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$request_uri                   = remove_query_arg( 'addon', $url );
			$can_user_manage_options       = current_user_can( 'manage_options' );
			$this->mo2fa_nav_tab_details   = ( $can_user_manage_options ? array(
				'Login Settings',
				'2FA For Me',
				'Notifications',
				'Forms',
			) : array(
				'2FA For Me',
			) );
			$this->wl_tab_details          = array(
				'2FA Customizations',
				'Email Templates',
				'Login Popup',
			);
			$this->adv_tab_details         = array(
				'Reports',
				'Users 2FA Status',
				'Passwordless Login',
				'Remember Device',
				'Session Management',
			);
			$this->ip_blocking_tab_details = array(
				'Advanced Blocking',
				'IP Blacklist',
			);
			$this->tab_details             = array(
				Mo2f_Tabs::TWO_FACTOR           => new Mo2f_PluginPageDetails(
					'Two Factor Authentication',
					$this->parent_slug,
					'read',
					$request_uri,
					'twofactorauthentication' . DIRECTORY_SEPARATOR . ( $can_user_manage_options ? 'loginsettings.php' : '2faforme.php' ),
					true,
					$this->mo2fa_nav_tab_details,
				),
				Mo2f_Tabs::WHITE_LABELLING      => new Mo2f_PluginPageDetails(
					'White Labelling',
					'mo_2fa_white_labelling',
					'manage_options',
					$request_uri,
					'whitelabelling' . DIRECTORY_SEPARATOR . '2facustomizations.php',
					$can_user_manage_options,
					$this->wl_tab_details,
				),
				Mo2f_Tabs::ADVANCE_SETTINGS     => new Mo2f_PluginPageDetails(
					'Advance Settings',
					'mo_2fa_advance_settings',
					'manage_options',
					$request_uri,
					'advancesettings' . DIRECTORY_SEPARATOR . 'reports.php',
					$can_user_manage_options,
					$this->adv_tab_details,
				),
				Mo2f_Tabs::UPGRADE              => new Mo2f_PluginPageDetails(
					'Upgrade',
					'mo_2fa_upgrade',
					'manage_options',
					$request_uri,
					'upgrade.php',
					$can_user_manage_options,
					array(),
				),
				Mo2f_Tabs::MY_ACCOUNT           => new Mo2f_PluginPageDetails(
					'My Account',
					'mo_2fa_my_account',
					'manage_options',
					$request_uri,
					'myaccount.php',
					$can_user_manage_options,
					array(),
				),
				Mo2f_Tabs::SETUPWIZARD_SETTINGS => new Mo2f_PluginPageDetails(
					'Setup Wizard',
					'mo2f-setup-wizard',
					'manage_options',
					$request_uri,
					'setupwizard.php',
					$can_user_manage_options,
					array(),
				),
				Mo2f_Tabs::TROUBLESHOOTING      => new Mo2f_PluginPageDetails(
					'FAQs',
					'mo_2fa_troubleshooting',
					'manage_options',
					$request_uri,
					'faqs.php',
					$can_user_manage_options,
					array(),
				),
			);
			if ( MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' ) ) {
				array_push(
					$this->tab_details,
					new Mo2f_PluginPageDetails(
						'IP Blocking',
						'mo_2fa_advancedblocking',
						'manage_options',
						$request_uri,
						'ipblocking' . DIRECTORY_SEPARATOR . 'advancedblocking.php',
						$can_user_manage_options && MoWpnsUtility::get_mo2f_db_option( 'mo_wpns_2fa_with_network_security', 'get_option' ),
						$this->ip_blocking_tab_details,
					)
				);
			}
		}
	}
}
