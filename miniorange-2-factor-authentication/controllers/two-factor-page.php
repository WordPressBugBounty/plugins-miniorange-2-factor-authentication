<?php
/**
 * This file is controller for views/twofa/two-fa.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Helper\MoWpnsMessages;

// Needed in both.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Including the file for frontend.
 */

$mo2f_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'mo_2fa_two_fa'; //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.
?>
<div id="mo_scan_message" style=" padding-top:8px"></div>

		<div class="mo2f-tw-flexbox">
			<?php
			$side_tabs = $tab_details->tab_details;
			if ( isset( $side_tabs ) ) {
				foreach ( $side_tabs as $mo2f_tab ) {
					if ( $mo2f_tab->menu_slug === $mo2f_page ) {
							echo '<div class="mo2f-tw-table-layout" id="' . esc_attr( $mo2f_page ) . '_div">';
							$nav_tabs = $mo2f_tab->nav_tabs;
							echo '<div class="mo2f-tw-subtab-wrapper">';
						foreach ( $nav_tabs as $nav_tab ) {
							$nav_tab_id = strtolower( str_replace( ' ', '', $nav_tab ) );
							echo '<a href="' . esc_attr( admin_url() ) . 'admin.php?page=' . esc_attr( $mo2f_page ) . '&subpage=' . esc_attr( $nav_tab_id ) . '" class="mo2f-tw-subtab" id="' . esc_attr( $nav_tab_id ) . '"> ' . esc_html( MoWpnsMessages::lang_translate( $nav_tab ) ) . '</a>';
						}
							echo '</div>';
							echo empty( $nav_tabs ) ? '' : '<hr>';
						if ( isset( $_GET['subpage'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.
							$navtab = sanitize_text_field( wp_unslash( $_GET['subpage'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.
							foreach ( $nav_tabs as $nav_tab ) {
								$nav_tab_id = strtolower( str_replace( ' ', '', $nav_tab ) );
								if ( $navtab === $nav_tab_id ) {
									require_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . strtolower( str_replace( ' ', '', $mo2f_tab->page_title ) ) . DIRECTORY_SEPARATOR . $nav_tab_id . '.php';
								}
							}
						} else {
							require_once $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . $mo2f_tab->view;
						}
						?>
						</div>
							<?php
					}
				}
			}
			?>
		</div>
		<?php
