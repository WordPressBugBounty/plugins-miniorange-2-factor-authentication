<?php
/**Load Interface PluginPageDetails
 *
 * @package miniOrange-2-factor-authentication/objects
 */

namespace TwoFA\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_PluginPageDetails' ) ) {
	/**
	 *  This class is used to generate notification settings
	 *  specific to email or sms settings. These settings are then passed
	 *  to the cURL function to send notifications.
	 */
	class Mo2f_PluginPageDetails {
		/**
		 * Constructor.
		 *
		 * @param string $page_title page title param.
		 * @param string $menu_slug menu slug param.
		 * @param string $capability Capability.
		 * @param string $request_uri request url.
		 * @param string $view view page details.
		 * @param bool   $show_in_nav Shows in the nav.
		 * @param array  $nav_tabs nav tabs.
		 */
		public function __construct( $page_title, $menu_slug, $capability, $request_uri, $view, $show_in_nav, $nav_tabs ) {
			$this->page_title  = $page_title;
			$this->menu_slug   = $menu_slug;
			$this->capability  = $capability;
			$this->url         = add_query_arg( array( 'page' => $this->menu_slug ), $request_uri );
			$this->url         = remove_query_arg( array( 'addon', 'form', 'sms', 'subpage' ), $this->url );
			$this->view        = $view;
			$this->show_in_nav = $show_in_nav;
			$this->nav_tabs    = $nav_tabs;
		}

		/**
		 * The page title
		 *
		 * @var string $page_title
		 */
		public $page_title;

		/**
		 * The menuSlug
		 *
		 * @var string $menu_slug
		 */
		public $menu_slug;

		/**
		 * URL of the NavBar
		 *
		 * @var String $url
		 */
		public $url;

		/**
		 * The php page having the view
		 *
		 * @var String $view
		 */
		public $view;

		/**
		 * The php page having the icon
		 *
		 * @var String $icon
		 */
		public $capability;

		/**
		 * The Attribute which decides if this page should be shown
		 * in the Navbar
		 *
		 * @var bool $show_in_nav
		 */
		public $show_in_nav;

		/**
		 * The Attribute which decides if this page should be shown
		 * in the Navbar
		 *
		 * @var array $nav_tabs
		 */
		public $nav_tabs;

	}
}
