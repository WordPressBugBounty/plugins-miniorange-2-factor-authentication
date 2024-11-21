<?php
/**
 * Frontend for navigation bar containing 2fa tabs.
 *
 * @package miniorange-2-factor-authentication/views/
 */

use TwoFA\Helper\MoWpnsMessages;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '<div class="mo2f_plugin_dashboard">';

if ( isset( $tab_details->tab_details ) ) {
	$side_tab_list = $tab_details->tab_details;
	echo '<div class="side-nav-wrapper">';
	foreach ( $side_tab_list as $side_tabs ) {
		if ( $side_tabs->show_in_nav ) {
			$abr_page_title   = strtolower( str_replace( ' ', '', $side_tabs->page_title ) );
			$redirection_page = 'Upgrade' === $side_tabs->page_title ? 'https://plugins.miniorange.com/2-factor-authentication-for-wordpress-wp-2fa#pricing' : esc_url( admin_url() ) . 'admin.php?page=' . esc_attr( $side_tabs->menu_slug );
			$target           = 'Upgrade' === $side_tabs->page_title ? '_blank' : '';
			echo '<a href="' . esc_url( $redirection_page ) . '" target="' . esc_attr( $target ) . '" class="side-nav-item" id="' . esc_attr( $side_tabs->menu_slug ) . '"><svg class="side-nav-icons">
			<image href="' . esc_url( plugin_dir_url( dirname( __FILE__ ) ) ) . 'includes/images/tabicons/' . esc_attr( $abr_page_title ) . '.svg" />
		  </svg>' . esc_html( MoWpnsMessages::lang_translate( $side_tabs->page_title ) ) . '</a>';
		}
	}
	echo '</div>';
}
