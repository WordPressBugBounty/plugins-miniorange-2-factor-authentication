<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/views/ipblocking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mo2f-settings-div" id="mo2f_ip_range_blocking">
	<div class="mo2f-settings-head justify-between -ml-mo-9">
		<span><?php esc_html_e( 'IP Address Range Blocking', 'miniorange-2-factor-authentication' ); ?></span>
	</div>
	<p class="mo2f-setting-desc mt-mo-2"><?php esc_html_e( 'You can block a range of IP addresses here (e.g. 192.168.0.100 - 192.168.0.190), or enter CIDR notation like 192.168.0.0/24 in the Start IP field and leave End IP blank.', 'miniorange-2-factor-authentication' ); ?></p>
	<form name="f" method="post" action="" id="iprangeblockingform">
		<input type="hidden" name="option" value="mo_wpns_block_ip_range" />
		<input type="hidden" name="mo2f_security_features_nonce" value="<?php echo esc_attr( wp_create_nonce( 'mo2f_security_nonce' ) ); ?>" />
		<table id="iprangetable" style="margin-top:14px;">
			<?php for ( $mo2f_i = 1; $mo2f_i <= $mo2f_range_count; $mo2f_i++ ) : ?>
			<tr>
				<td>
					<div class="mo-input-wrapper mo2f-start-ips mb-mo-3 mr-mo-4" style="display:inline-block;width:220px;vertical-align:top;">
						<label class="mo-input-label"><?php esc_html_e( 'Start IP', 'miniorange-2-factor-authentication' ); ?></label>
						<input type="text" class="mo2f-adv-set-input mo_wpns_table_textbox" style="width:220px;" name="start_<?php echo (int) $mo2f_i; ?>" value="<?php echo esc_attr( $mo2f_start[ $mo2f_i ] ); ?>" placeholder="e.g 192.168.0.100 or 192.168.0.0/24" />
					</div>
					<div class="mo-input-wrapper mo2f-end-ips mb-mo-3" style="display:inline-block;width:220px;vertical-align:top;">
						<label class="mo-input-label"><?php esc_html_e( 'End IP', 'miniorange-2-factor-authentication' ); ?></label>
						<input type="text" class="mo2f-adv-set-input mo_wpns_table_textbox" style="width:220px;" placeholder="e.g 192.168.0.190" value="<?php echo esc_attr( $mo2f_end[ $mo2f_i ] ); ?>" name="end_<?php echo (int) $mo2f_i; ?>" />
					</div>
				</td>
			</tr>
			<?php endfor; ?>
		</table>
		<a style="cursor:pointer" id="add_ran"><?php esc_html_e( 'Add IP Range', 'miniorange-2-factor-authentication' ); ?></a>
		<br><br>
		<input type="submit" class="mo2f-save-settings-button" value="<?php esc_attr_e( 'Block IP range', 'miniorange-2-factor-authentication' ); ?>" />
	</form>
</div>
<script>
jQuery("#mo_2fa_advancedblocking").addClass("side-nav-active");
jQuery("#advancedblocking").addClass("mo2f-subtab-active");
jQuery("#add_ran").click(function() {
	var last_index_name = jQuery("#iprangetable tr:last .mo_wpns_table_textbox").attr("name");
	var splittedArray = last_index_name.split("_");
	var last_index = parseInt(splittedArray[splittedArray.length-1])+1;
	var new_row = '<tr><td>' +
		'<div class="mo-input-wrapper mo2f-start-ips mb-mo-3 mr-mo-4" style="display:inline-block;width:220px;vertical-align:top;">' +
		'<label class="mo-input-label"><?php echo esc_js( __( 'Start IP', 'miniorange-2-factor-authentication' ) ); ?></label>' +
		'<input type="text" class="mo2f-adv-set-input mo_wpns_table_textbox" style="width:220px;" name="start_' + last_index + '" value="" placeholder=" e.g 192.168.0.100 or 192.168.0.0/24" />' +
		'</div>' +
		'<div class="mo-input-wrapper mo2f-end-ips mb-mo-3" style="display:inline-block;width:220px;vertical-align:top;">' +
		'<label class="mo-input-label"><?php echo esc_js( __( 'End IP', 'miniorange-2-factor-authentication' ) ); ?></label>' +
		'<input type="text" class="mo2f-adv-set-input mo_wpns_table_textbox" style="width:220px;" placeholder=" e.g 192.168.0.190" value="" name="end_' + last_index + '" />' +
		'</div></td></tr>';
	jQuery("#iprangetable tr:last").after(new_row);
});
function mo2f_wpns_block_function(elmt){
	var tabname = elmt.id;
	var tabarray = ["mo2f_block_list","mo2f_adv_block"];
	for (var i = 0; i < tabarray.length; i++) {
		if(tabarray[i] == tabname){
			jQuery("#"+tabarray[i]).addClass("side-nav-active");
			jQuery("#"+tabarray[i]+"_div").css("display", "block");
		}else{
			jQuery("#"+tabarray[i]).removeClass("side-nav-active");
			jQuery("#"+tabarray[i]+"_div").css("display", "none");
		}
	}
	localStorage.setItem("ip_last_tab", tabname);
}
</script>
