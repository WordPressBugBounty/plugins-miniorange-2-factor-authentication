<?php
/**
 * Frontend for navigation bar containing 2fa tabs.
 *
 * @package miniorange-2-factor-authentication/views/
 */

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '<div class="mo2f_plugin_dashboard">';

if ( isset( $mo2f_tab_details->tab_details ) ) {
	$mo2f_side_tab_list = $mo2f_tab_details->tab_details;
	echo '<aside class="sidebar">';
	echo '<div class="card">';
	echo '<nav class="nav">';
	foreach ( $mo2f_side_tab_list as $mo2f_side_tabs ) {
		if ( $mo2f_side_tabs->show_in_nav ) {
			$mo2f_redirection_page = 'Upgrade' === $mo2f_side_tabs->page_title ? MoWpnsConstants::MO2F_UPGRADE_PRICING_URL : esc_url( admin_url() ) . 'admin.php?page=' . esc_attr( $mo2f_side_tabs->menu_slug );
			$mo2f_target           = 'Upgrade' === $mo2f_side_tabs->page_title ? '_blank' : '';

			// Get icon for menu item.
			$mo2f_icon         = '';
			$mo2f_is_html_icon = false;
			switch ( $mo2f_side_tabs->page_title ) {
				case 'Quick Setup':
					$mo2f_icon = '⚡';
					break;
				case 'Settings':
					$mo2f_icon = '⚙️';
					break;
				case 'Advanced Features':
					$mo2f_icon = '✨';
					break;
				case 'Form Integration':
					$mo2f_icon = '🧾';
					break;
				case 'My Account':
					$mo2f_icon = '👤';
					break;
				case 'Reports':
					$mo2f_icon = '📊';
					break;
				case 'IP Blocking':
					$mo2f_icon = '🛡️';
					break;
				case 'White Labelling':
					$mo2f_icon = '🎨';
					break;
				case 'Contact Us':
					$mo2f_icon = '📞';
					break;
				case 'FAQs':
					$mo2f_icon = '❓';
					break;
				case 'Upgrade':
					$mo2f_icon = '⬆️';
					break;
				case '2FA Configurations':
					$mo2f_icon = '🔐';
					break;
				case 'Setup Wizard':
					$mo2f_icon = '🧙‍♂️';
					break;
				case 'WhatsApp':
					$mo2f_whatsapp_icon_url = plugin_dir_url( __DIR__ ) . 'includes/images/whatsapp.png';
					$mo2f_icon              = '<img src="' . esc_url( $mo2f_whatsapp_icon_url ) . '" style="width:18px;height:18px;vertical-align:middle;" alt="' . esc_attr__( 'WhatsApp', 'miniorange-2-factor-authentication' ) . '">';
					$mo2f_is_html_icon      = true;
					break;
				default:
					$mo2f_icon = '🔧';
			}

			$mo2f_icon_output = $mo2f_is_html_icon ? wp_kses_post( $mo2f_icon ) : esc_html( $mo2f_icon );

			if ( 'My Account' === $mo2f_side_tabs->page_title ) {
				$mo2f_myaccount_chevron = '<span class="nav-item__chevron" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span>';
				echo '<div class="mo2f-sidebar-nav-group mo2f-sidebar-nav-group--myaccount">';
				echo '<a href="' . esc_url( $mo2f_redirection_page ) . '" target="' . esc_attr( $mo2f_target ) . '" class="nav-item nav-item--has-submenu" id="' . esc_attr( $mo2f_side_tabs->menu_slug ) . '" aria-haspopup="true" title="' . esc_attr__( 'Has more options — hover to open Account Details and Setup 2FA', 'miniorange-2-factor-authentication' ) . '">' . $mo2f_icon_output . ' <span class="nav-item__label">' . esc_html( $mo2f_side_tabs->page_title ) . '</span>' . $mo2f_myaccount_chevron . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $mo2f_icon_output is already escaped via wp_kses_post() or esc_html().
				echo '<div id="mo2f-myaccount-submenu" class="mo2f_myaccount_submenu">
					<ul>';
				if ( current_user_can( 'manage_options' ) ) {
					echo '<li><a href="' . esc_url( $mo2f_redirection_page ) . '" class="mo2f_myaccount_submenu-item" id="mo2f-myaccount-details"> ' . esc_html__( 'Account Details', 'miniorange-2-factor-authentication' ) . '</a></li>';
				}
				echo '<li> <a href="' . esc_url( $mo2f_redirection_page ) . '&subpage=setupyour2fa" class="mo2f_myaccount_submenu-item" id="mo2f-myaccount-setup-2fa"> ' . esc_html__( 'Setup 2FA', 'miniorange-2-factor-authentication' ) . '</a></li>
					</ul>
				</div>';
				echo '</div>';
			} else {
				echo '<a href="' . esc_url( $mo2f_redirection_page ) . '" target="' . esc_attr( $mo2f_target ) . '" class="nav-item" id="' . esc_attr( $mo2f_side_tabs->menu_slug ) . '">' . $mo2f_icon_output . ' ' . esc_html( $mo2f_side_tabs->page_title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $mo2f_icon_output is already escaped via wp_kses_post() or esc_html().
			}
		}
	}
	echo '</nav>';
	echo '</div>';
	echo '</aside>';
}
