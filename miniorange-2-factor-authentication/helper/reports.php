<?php
/**
 * This file is controller for views/twofa/two-fa-rba.php.
 *
 * @package miniorange-2-factor-authentication/reports/helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use TwoFA\Helper\MoWpnsConstants;

/**
 * Function to show Login Transactions
 *
 * @param array $usertranscations - Database entries that needs to be shown.
 * @return void
 */
function show_login_transactions( $usertranscations ) {
	foreach ( $usertranscations as $usertranscation ) {
			echo '<tr><td>' . esc_html( $usertranscation->ip_address ) . '</td><td>' . esc_html( $usertranscation->username ) . '</td><td>';
		if ( MoWpnsConstants::FAILED === $usertranscation->status || MoWpnsConstants::PAST_FAILED === $usertranscation->status ) {
			echo '<span style=color:red>' . esc_html( MoWpnsConstants::FAILED ) . '</span>';
		} elseif ( MoWpnsConstants::SUCCESS === $usertranscation->status ) {
			echo '<span style=color:green>' . esc_html( MoWpnsConstants::SUCCESS ) . '</span>';
		} else {
			echo 'N/A';
		}
		echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $usertranscation->created_timestamp ) ) . '</td></tr>';
	}
}

/**
 * Function to show 404 and 403 Reports
 *
 * @param array $usertransactions - Database entries that needs to be shown.
 * @return void
 */
function show_error_transactions( $usertransactions ) {
	foreach ( $usertransactions as $usertranscation ) {
		echo '<tr><td>' . esc_html( $usertranscation->ip_address ) . '</td><td>' . esc_html( $usertranscation->username ) . '</td>';
		echo '<td>' . esc_html( $usertranscation->url ) . '</td><td>' . esc_html( $usertranscation->type ) . '</td>';
		echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $usertranscation->created_timestamp ) ) . '</td></tr>';
	}
}
