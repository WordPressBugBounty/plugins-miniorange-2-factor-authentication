<?php
/**
 * This file shows the Dashboard on frontend.
 *
 * @package miniorange-2-factor-authentication/views/2faconfigurations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mo2f-dash-stat-row">
	<div class="mo2f-dash-stat-hero">
		<div>
			<div class="mo2f-dash-stat-hero-num"><?php echo esc_html( $mo2f_adoption_percent ); ?>%</div>
			<div class="mo2f-dash-stat-hero-lbl"><?php esc_html_e( 'of required users have 2FA set up', 'miniorange-2-factor-authentication' ); ?></div>
		</div>
		<div class="mo2f-dash-stat-hero-bar"><span style="width:<?php echo esc_attr( $mo2f_adoption_percent ); ?>%"></span></div>
	</div>
	<div class="mo2f-dash-stat-mini">
		<div class="mo2f-dash-stat-mini-num"><?php echo esc_html( number_format_i18n( $mo2f_configured_in_required ) ); ?></div>
		<div class="mo2f-dash-stat-mini-lbl"><?php esc_html_e( 'Users protected', 'miniorange-2-factor-authentication' ); ?></div>
	</div>
	<div class="mo2f-dash-stat-mini mo2f-dash-stat-mini--warn">
		<div class="mo2f-dash-stat-mini-num"><?php echo esc_html( number_format_i18n( $mo2f_pending_users_total ) ); ?></div>
		<div class="mo2f-dash-stat-mini-lbl"><?php esc_html_e( 'Still need to set up 2FA', 'miniorange-2-factor-authentication' ); ?></div>
	</div>
</div>

<div class="mo2f-settings-div mo2f-section mo2f-dash-card">
	<div class="mo2f-section-head">
		<h2><?php esc_html_e( 'Setup checklist', 'miniorange-2-factor-authentication' ); ?></h2>
	</div>
	<div class="mo2f-dash-checklist">
		<div class="mo2f-dash-check-row">
			<div class="mo2f-dash-check-dot <?php echo $mo2f_account_connected ? 'done' : 'todo'; ?>"><?php echo $mo2f_account_connected ? '&#10003;' : '!'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML entity, not user input. ?></div>
			<div class="mo2f-dash-check-body">
				<div class="mo2f-dash-check-title"><?php esc_html_e( 'Connect your miniOrange account', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-dash-check-desc">
					<?php
					if ( $mo2f_account_connected ) {
						esc_html_e( 'Connected -- Email and SMS OTPs are delivered through your miniOrange account.', 'miniorange-2-factor-authentication' );
					} else {
						esc_html_e( 'Required to deliver Email and SMS OTPs. Free to create, and includes 10 free email transactions.', 'miniorange-2-factor-authentication' );
					}
					?>
				</div>
			</div>
			<?php if ( ! $mo2f_account_connected ) : ?>
				<a class="mo2f-dash-check-action" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_my_account' ) ); ?>"><?php esc_html_e( 'Register/Login', 'miniorange-2-factor-authentication' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="mo2f-dash-check-row">
			<div class="mo2f-dash-check-dot <?php echo $mo2f_enable_2fa ? 'done' : 'todo'; ?>"><?php echo $mo2f_enable_2fa ? '&#10003;' : '!'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML entity, not user input. ?></div>
			<div class="mo2f-dash-check-body">
				<div class="mo2f-dash-check-title"><?php esc_html_e( '2FA is enabled', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-dash-check-desc">
					<?php
					if ( count( $mo2f_required_roles ) > 0 ) {
						/* Translators: %d: number of roles required to have 2FA. */
						printf( esc_html__( '%d role(s) required', 'miniorange-2-factor-authentication' ), count( $mo2f_required_roles ) );
					} else {
						esc_html_e( 'No roles are required to have 2FA yet', 'miniorange-2-factor-authentication' );
					}
					?>
				</div>
			</div>
			<?php if ( ! $mo2f_enable_2fa || 0 === count( $mo2f_required_roles ) ) : ?>
				<a class="mo2f-dash-check-action" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_two_fa&subpage=accessrules' ) ); ?>"><?php esc_html_e( 'Review', 'miniorange-2-factor-authentication' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="mo2f-dash-check-row">
			<div class="mo2f-dash-check-dot <?php echo count( $mo2f_methods_selected ) > 0 ? 'done' : 'todo'; ?>"><?php echo count( $mo2f_methods_selected ) > 0 ? '&#10003;' : '!'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML entity, not user input. ?></div>
			<div class="mo2f-dash-check-body">
				<div class="mo2f-dash-check-title"><?php esc_html_e( 'Login methods available', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-dash-check-desc">
					<?php
					if ( count( $mo2f_methods_selected ) > 0 ) {
						echo esc_html( implode( ', ', array_map( 'strtolower', $mo2f_methods_selected ) ) );
					} else {
						esc_html_e( 'No methods selected yet', 'miniorange-2-factor-authentication' );
					}
					?>
				</div>
			</div>
			<?php if ( 0 === count( $mo2f_methods_selected ) ) : ?>
				<a class="mo2f-dash-check-action" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_two_fa&subpage=methods' ) ); ?>"><?php esc_html_e( 'Choose methods', 'miniorange-2-factor-authentication' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="mo2f-dash-check-row">
			<div class="mo2f-dash-check-dot <?php echo $mo2f_change_alerts_on ? 'done' : 'todo'; ?>"><?php echo $mo2f_change_alerts_on ? '&#10003;' : '!'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML entity, not user input. ?></div>
			<div class="mo2f-dash-check-body">
				<div class="mo2f-dash-check-title"><?php esc_html_e( 'Notify users when their 2FA method changes', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="mo2f-dash-check-desc"><?php echo $mo2f_change_alerts_on ? esc_html__( 'On', 'miniorange-2-factor-authentication' ) : esc_html__( 'Currently off', 'miniorange-2-factor-authentication' ); ?></div>
			</div>
			<?php if ( ! $mo2f_change_alerts_on ) : ?>
				<a class="mo2f-dash-check-action" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_two_fa&subpage=notifications' ) ); ?>"><?php esc_html_e( 'Turn on', 'miniorange-2-factor-authentication' ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $mo2f_pending_users_total > 0 ) : ?>
		<div class="mo2f-dash-check-row">
			<div class="mo2f-dash-check-dot todo">!</div>
			<div class="mo2f-dash-check-body">
				<div class="mo2f-dash-check-title">
					<?php
					/* Translators: %d: number of required users who haven't configured 2FA. */
					printf( esc_html__( '%d required user(s) haven\'t configured 2FA yet', 'miniorange-2-factor-authentication' ), (int) $mo2f_pending_users_total );
					?>
				</div>
				<div class="mo2f-dash-check-desc"><?php esc_html_e( 'Remind them, or review who\'s left', 'miniorange-2-factor-authentication' ); ?></div>
			</div>
			<a class="mo2f-dash-check-action" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'Review users', 'miniorange-2-factor-authentication' ); ?></a>
		</div>
		<?php endif; ?>
	</div>
</div>

<div class="mo2f-dash-two-col">
	<div class="mo2f-settings-div mo2f-section mo2f-dash-card">
		<div class="mo2f-section-head">
			<h2><?php esc_html_e( 'Recent activity', 'miniorange-2-factor-authentication' ); ?></h2>
		</div>
		<div class="mo2f-dash-activity">
			<?php if ( empty( $mo2f_recent_activity ) ) : ?>
				<div class="mo2f-dash-check-desc" style="padding:16px 18px;"><?php esc_html_e( 'No login activity recorded yet.', 'miniorange-2-factor-authentication' ); ?></div>
			<?php endif; ?>
			<?php foreach ( $mo2f_recent_activity as $mo2f_event ) : ?>
				<?php
				$mo2f_status    = strtolower( (string) $mo2f_event->status );
				$mo2f_dot_class = 'success' === $mo2f_status ? 'ok' : ( 'failed' === $mo2f_status ? 'warn' : 'info' );
				?>
				<div class="mo2f-dash-activity-row">
					<span class="mo2f-dash-activity-dot <?php echo esc_attr( $mo2f_dot_class ); ?>"></span>
					<div class="mo2f-dash-activity-text">
						<b><?php echo esc_html( $mo2f_event->username ); ?></b>
						<?php echo 'success' === $mo2f_status ? esc_html__( 'logged in successfully', 'miniorange-2-factor-authentication' ) : esc_html__( 'had a failed login attempt', 'miniorange-2-factor-authentication' ); ?>
						<div class="mo2f-dash-activity-time"><?php echo esc_html( human_time_diff( (int) $mo2f_event->created_timestamp ) . ' ' . __( 'ago', 'miniorange-2-factor-authentication' ) . ' &middot; ' . $mo2f_event->ip_address ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above. ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="mo2f-settings-div mo2f-section mo2f-dash-card">
		<div class="mo2f-section-head">
			<h2><?php esc_html_e( 'Quick actions', 'miniorange-2-factor-authentication' ); ?></h2>
		</div>
		<div class="mo2f-dash-qa">
			<a class="mo2f-dash-qa-row" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
				<span class="mo2f-dash-qa-ico">&#128101;</span>
				<div class="mo2f-dash-qa-txt"><b><?php esc_html_e( 'Review who still needs 2FA', 'miniorange-2-factor-authentication' ); ?></b><span><?php echo esc_html( number_format_i18n( $mo2f_pending_users_total ) ); ?> <?php esc_html_e( 'required users pending', 'miniorange-2-factor-authentication' ); ?></span></div>
				<span class="mo2f-dash-qa-arrow">&rarr;</span>
			</a>
			<a class="mo2f-dash-qa-row" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_two_fa&subpage=methods' ) ); ?>">
				<span class="mo2f-dash-qa-ico">&#128272;</span>
				<div class="mo2f-dash-qa-txt"><b><?php esc_html_e( 'Add another login method', 'miniorange-2-factor-authentication' ); ?></b><span><?php esc_html_e( 'SMS, WhatsApp, Push (Premium)', 'miniorange-2-factor-authentication' ); ?></span></div>
				<span class="mo2f-dash-qa-arrow">&rarr;</span>
			</a>
			<a class="mo2f-dash-qa-row" href="<?php echo esc_url( admin_url( 'admin.php?page=mo_2fa_white_labelling' ) ); ?>">
				<span class="mo2f-dash-qa-ico">&#127912;</span>
				<div class="mo2f-dash-qa-txt"><b><?php esc_html_e( 'Customize the login screen', 'miniorange-2-factor-authentication' ); ?></b><span><?php esc_html_e( 'Logo, colors, email templates', 'miniorange-2-factor-authentication' ); ?></span></div>
				<span class="mo2f-dash-qa-arrow">&rarr;</span>
			</a>
		</div>
	</div>
</div>
<script>
jQuery("#mo_2fa_dashboard").addClass("side-nav-active");
</script>
