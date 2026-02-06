<?php
/**
 * This file is controller for views/twofa/two-fa.php.
 *
 * @package miniorange-2-factor-authentication/controllers/twofa
 */

use TwoFA\Helper\MoWpnsMessages;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Including the file for frontend.
 */

$mo2f_lv_needed     = apply_filters( 'mo2f_is_lv_needed', false );
$mo2f_common_helper = new Mo2f_Common_Helper();
$mo2f_page          = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : $mo2f_common_helper->mo2f_get_default_page( $mo2f_lv_needed ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.
if ( current_user_can( 'manage_options' ) || 'mo_2fa_my_account' === $mo2f_page ) {
	?>
<div id="mo_scan_message" style="padding-top:8px"></div>

		<div class="mo2f-tw-flexbox">
			<?php
			$mo2f_side_tabs = $mo2f_tab_details->tab_details;
			if ( isset( $mo2f_side_tabs ) ) {
				foreach ( $mo2f_side_tabs as $mo2f_tab ) {
					if ( $mo2f_tab->menu_slug === $mo2f_page ) {
						echo '<div class="mo2f-tw-table-layout" id="' . esc_attr( $mo2f_page ) . '_div">';
						$mo2f_nav_tabs        = $mo2f_tab->nav_tabs;
						$mo2f_current_subpage = filter_input( INPUT_GET, 'subpage', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
						$mo2f_current_subpage = $mo2f_current_subpage ? $mo2f_current_subpage : '';
						if ( 'mo_2fa_my_account' === $mo2f_page && ! current_user_can( 'manage_options' ) ) {
							$mo2f_current_subpage = 'setupyour2fa'; // For no admin user, redirect to setupyour2fa page.
						}
						if ( ! in_array( 'My Account', $mo2f_nav_tabs, true ) && ! empty( $mo2f_nav_tabs ) ) {
							echo '<div class="tabs">';
							echo '<div class="tablist">';
							$mo2f_first_tab = true;
							foreach ( $mo2f_nav_tabs as $mo2f_nav_tab ) {
								$mo2f_nav_tab_id = strtolower( str_replace( ' ', '', $mo2f_nav_tab ) );
								$mo2f_is_active  = '';
								if ( $mo2f_current_subpage && $mo2f_current_subpage === $mo2f_nav_tab_id ) {
									$mo2f_is_active = 'aria-selected="true"';
								} elseif ( ! $mo2f_current_subpage && $mo2f_first_tab ) {
									$mo2f_is_active = 'aria-selected="true"';
									$mo2f_first_tab = false;
								}

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $mo2f_is_active contains valid HTML attribute string.
								echo '<button class="tab" data-tab="' . esc_attr( $mo2f_nav_tab_id ) . '" ' . $mo2f_is_active . ' onclick="window.location.href=\'' . esc_url( admin_url() ) . 'admin.php?page=' . esc_attr( $mo2f_page ) . '&subpage=' . esc_attr( $mo2f_nav_tab_id ) . '\'">' . esc_html( $mo2f_nav_tab ) . '</button>';
							}
							echo '</div>';
						}
						$mo2f_content_loaded = false;
						if ( $mo2f_current_subpage && ! empty( $mo2f_nav_tabs ) ) {
							foreach ( $mo2f_nav_tabs as $mo2f_nav_tab ) {
								$mo2f_nav_tab_id = strtolower( str_replace( ' ', '', $mo2f_nav_tab ) );
								if ( $mo2f_current_subpage === $mo2f_nav_tab_id ) {
									$mo2f_controller_file = $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . strtolower( str_replace( ' ', '', $mo2f_tab->page_title ) ) . DIRECTORY_SEPARATOR . $mo2f_nav_tab_id . '.php';
									if ( file_exists( $mo2f_controller_file ) ) {
										require_once $mo2f_controller_file;
										$mo2f_content_loaded = true;
										break;
									}
								}
							}
						}
						if ( ! $mo2f_content_loaded && ! empty( $mo2f_nav_tabs ) ) {
							$mo2f_first_tab_id    = strtolower( str_replace( ' ', '', $mo2f_nav_tabs[0] ) );
							$mo2f_controller_file = $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . strtolower( str_replace( ' ', '', $mo2f_tab->page_title ) ) . DIRECTORY_SEPARATOR . $mo2f_first_tab_id . '.php';
							if ( file_exists( $mo2f_controller_file ) ) {
								require_once $mo2f_controller_file;
								$mo2f_content_loaded = true;
							}
						}
						// Load controller file directly for pages without nav_tabs (e.g., WhatsApp, FAQs, Upgrade).
						if ( ! $mo2f_content_loaded && empty( $mo2f_nav_tabs ) && isset( $mo2f_tab->view ) ) {
							$mo2f_controller_file = $mo2f_dir_name . 'controllers' . DIRECTORY_SEPARATOR . $mo2f_tab->view;
							if ( file_exists( $mo2f_controller_file ) ) {
								require_once $mo2f_controller_file;
							}
						}
						?>
						</div>
							<?php
					}
				}
			}
			?>
		</div>
		</div>
		<?php
}
