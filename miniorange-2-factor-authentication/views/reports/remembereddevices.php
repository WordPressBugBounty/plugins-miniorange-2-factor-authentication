<?php
/**
 * Description: This file is used to show the RBA saved devices.
 *
 * @package miniorange-2-factor-authentication/reports/
 */

use TwoFA\Helper\MoWpnsConstants;
use TwoFA\Helper\Mo2f_Common_Helper;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="mo2f-settings-div mo2f-enterprise-plan">
	<br>
<div class="mo2f_saved_devices">
<span><?php esc_html_e( 'Saved Devices', 'miniorange-2-factor-authentication' ); ?></span>
					<?php echo Mo2f_Common_Helper::mo2f_check_plan( 'enterprise', MoWpnsConstants::MO2F_PREMIUM_2PLAN_NAME ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Only a SVG, doesn't require escaping. ?>

</div>
<br>
<table id="mo2f_device_details" class="display" cellspacing="0" width="100%">
<thead>
<tr>
	<th>Username</th>
	<th>Device</th>
	<th>Fingerprint</th>
	<th>Browser</th>
	<th>Action</th>
</tr>
</thead>
<tbody> 
<?php
foreach ( $mo2f_remembered_devices as $mo2f_remembered_device ) {
	$mo2f_remove_device = 'onclick=removeRememberedDevice(' . $mo2f_remembered_device['mo2f_user_id'] . ',"' . $mo2f_remembered_device['device_data_hash'] . '")';
	?>
<tr id="<?php echo esc_attr( $mo2f_remembered_device['mo2f_user_id'] . $mo2f_remembered_device['device_data_hash'] ); ?>">
	<td><?php echo esc_html( $mo2f_remembered_device['mo2f_user_name'] ); ?></td>
	<td><?php echo esc_html( $mo2f_remembered_device['device'] ); ?></td>
	<td><?php echo esc_html( $mo2f_remembered_device['fingerprint'] ); ?></td>
	<td><?php echo esc_html( $mo2f_remembered_device['browser'] ); ?></td>
	<td><a style='cursor:pointer' <?php echo esc_attr( $mo2f_remove_device ); ?>><?php echo esc_html( 'Remove' ); ?></a></td>
</tr>
	<?php
}
?>
</tbody>
</table>
</div>
<script>
	jQuery("#remembereddevices").addClass("mo2f-subtab-active");
	jQuery("#mo_2fa_reports").addClass("side-nav-active");
</script>
