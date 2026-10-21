<?php
/**
 * This contains the view for the Compliance Log report (admin resets, method
 * changes, enrollment, and other auditable 2FA events).
 *
 * @package miniorange-2-factor-authentication/reports/views
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div>
		<div class="mo2f-settings-div">';

echo '	<div class="mo2f-settings-head" style="display:flex;justify-content:space-between;align-items:center;">
			<span>' . esc_html__( 'Compliance Log', 'miniorange-2-factor-authentication' ) . '</span>
			<span>' . esc_html__( 'Export (CSV / PDF)', 'miniorange-2-factor-authentication' ) . ' ' . Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ) /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. */ . '</span>
		</div>
		<p class="description">' . esc_html__( 'A record of admin-initiated and self-service 2FA resets for the last 30 days. Upgrade for full history and CSV/PDF export.', 'miniorange-2-factor-authentication' ) . '</p>
		<br>';

$mo2f_compliance_log_headers = array(
	__( 'Date', 'miniorange-2-factor-authentication' ),
	__( 'Event', 'miniorange-2-factor-authentication' ),
	__( 'Subject', 'miniorange-2-factor-authentication' ),
	__( 'Performed By', 'miniorange-2-factor-authentication' ),
	__( 'Method', 'miniorange-2-factor-authentication' ),
	__( 'IP Address', 'miniorange-2-factor-authentication' ),
);

echo '	<div class="mo2f-table-scroll">
			<table id="mo2f_compliance_log_table" class="display" cellspacing="0" width="100%">
				<thead>
					<tr>';
foreach ( $mo2f_compliance_log_headers as $mo2f_compliance_log_header ) {
	echo '<th>' . esc_html( $mo2f_compliance_log_header ) . '</th>';
}
echo '				</tr>
				</thead>
				<tbody>';
					$mo2f_common_helper->mo2f_show_compliance_log_report( $mo2f_compliance_events );
echo '			</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	jQuery("#compliancelog").addClass("mo2f-subtab-active");
	jQuery("#mo_2fa_reports").addClass("side-nav-active");
</script>';
