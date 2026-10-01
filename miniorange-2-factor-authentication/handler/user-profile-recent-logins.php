<?php
/**
 * Self-service "Recent Logins" panel, rendered on a user's own profile page so
 * they can see their own recent login activity without needing admin access.
 *
 * @package miniOrange-2-factor-authentication/handler
 */

use TwoFA\Helper\MoWpnsConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $mo2f_wpns_db_queries;
$mo2f_recent_logins = $mo2f_wpns_db_queries->mo2f_get_user_login_history( $user->user_login, 10 );
?>
<h3><?php esc_html_e( 'Recent Logins', 'miniorange-2-factor-authentication' ); ?></h3>
<table class="form-table" id="mo2f-recent-logins-table">
	<tr>
		<th><?php esc_html_e( 'Last 10 login attempts', 'miniorange-2-factor-authentication' ); ?></th>
		<td>
			<?php if ( empty( $mo2f_recent_logins ) ) : ?>
				<p><?php esc_html_e( 'No login activity recorded yet for this account.', 'miniorange-2-factor-authentication' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:600px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date/Time', 'miniorange-2-factor-authentication' ); ?></th>
							<th><?php esc_html_e( 'IP Address', 'miniorange-2-factor-authentication' ); ?></th>
							<th><?php esc_html_e( 'Status', 'miniorange-2-factor-authentication' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $mo2f_recent_logins as $mo2f_login_row ) : ?>
							<tr>
								<td><?php echo esc_html( $mo2f_login_row->created_timestamp ? gmdate( 'Y-m-d H:i:s', (int) $mo2f_login_row->created_timestamp ) : '-' ); ?></td>
								<td><?php echo esc_html( $mo2f_login_row->ip_address ); ?></td>
								<td>
									<?php if ( MoWpnsConstants::SUCCESS === $mo2f_login_row->status ) : ?>
										<span style="color:#00a32a;font-weight:600;"><?php esc_html_e( 'Success', 'miniorange-2-factor-authentication' ); ?></span>
									<?php else : ?>
										<span style="color:#d63638;font-weight:600;"><?php esc_html_e( 'Failed', 'miniorange-2-factor-authentication' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'If you see a login you don\'t recognize, change your password and reconfigure your 2FA method immediately.', 'miniorange-2-factor-authentication' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>
