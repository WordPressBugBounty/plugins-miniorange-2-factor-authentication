<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/views/ipblocking
 */

// Needed in both.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo '<div class="mo2f-settings-div"  id= "mo2f_ip_range_blocking">';

echo '		<div class="mo2f-settings-head justify-between -ml-mo-9"><span>' . esc_html__( 'IP Address Range Blocking', 'miniorange-2-factor-authentication' ) . '</span><a href=' . esc_url( $mo2f_two_factor_premium_doc['IP Address Range Blocking'] ) . ' target="_blank"><span class="dashicons dashicons-text-page mo2f-dash-icons-doc"></span></a></div>
			You can block range of IP addresses here  ( Examples: 192.168.0.100 - 192.168.0.190 )
			<form name="f" method="post" action="" id="iprangeblockingform" >
				<input type="hidden" name="option" value="mo_wpns_block_ip_range" />
				<input type="hidden" name="mo2f_security_features_nonce" value="' . esc_attr( wp_create_nonce( 'mo2f_security_nonce' ) ) . '" />

			<br>
			<table id="iprangetable">		
';
for ( $mo2f_i = 1; $mo2f_i <= $mo2f_range_count; $mo2f_i++ ) {
	echo '<tr><td>Start IP	<input style="width :30%" type ="text" class="mo_wpns_table_textbox" name="start_' . intval( esc_html( $mo2f_i ) ) . '" value ="' . esc_html( $mo2f_start[ $mo2f_i ] ) . '" placeholder=" e.g 192.168.0.100" />End IP	<input style="width :30%" type ="text" placeholder=" e.g 192.168.0.190" class="mo_wpns_table_textbox" value="' . esc_html( $mo2f_end[ $mo2f_i ] ) . '"  name="end_' . intval( esc_html( $mo2f_i ) ) . '"/></td></tr>';
}
echo '
		</table>
		<a style="cursor:pointer" id="add_ran">Add IP Range</a>
			';

echo '	<br> <br><input type="submit" class="mo2f-save-settings-button" value="Block IP range" />
				
			</form>
		</div>';
echo '	<script>		
jQuery("#mo_2fa_advancedblocking").addClass("side-nav-active");
jQuery("#advancedblocking").addClass("mo2f-subtab-active");
        jQuery("#add_ran").click(function() {
            var last_index_name = $("#iprangetable tr:last .mo_wpns_table_textbox").attr("name");
            
            var splittedArray = last_index_name.split("_");
            var last_index = parseInt(splittedArray[splittedArray.length-1])+1;
            var new_row = \'<tr><td>Start IP<input style="width :30%" type ="text" class="mo_wpns_table_textbox" name="start_\'+last_index+\'" value="" placeholder=" e.g 192.168.0.100" >&nbsp;&nbsp;End IP	<input style="width :30%" type ="text" placeholder=" e.g 192.168.0.190" class="mo_wpns_table_textbox" value="" name="end_\'+last_index+\'"></td></tr>\';
            $("#iprangetable tr:last").after(new_row);
        
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

</script>';
