<?php
/**
 * This file includes the UI for 2fa methods options.
 *
 * @package miniorange-2-factor-authentication/ipblocking/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TwoFA\Helper\MoWpnsHandler;
?>
		<div class="mo2f-settings-div" id="mo2f_manual_ip_blocking">
		<div class="mo2f-settings-head justify-between -ml-mo-9">
			<span><?php esc_html_e( 'Manual IP Blocking', 'miniorange-2-factor-authentication' ); ?></span>
			<a href='<?php echo esc_url( $two_factor_premium_doc['Manual IP Blocking'] ); ?>' target="_blank"><span class="dashicons dashicons-text-page mo2f-dash-icons-doc"></span></a>
		</div>
			<div class="mo2f-sub-settings-div">
				<div class="my-mo-3 mb-mo-3 flex">
					<div class="py-mo-2 pr-mo-2"><?php esc_html_e( 'Manually block an IP address here:', 'miniorange-2-factor-authentication' ); ?></div>
					<div class="pr-mo-2"><input type="text" name="ManuallyBlockIP" id="ManuallyBlockIP" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/></div>
					<div><input type="button" name="BlockIP" id="BlockIP" value="Manual Block IP" class="mo2f-save-settings-button" /></div>
				</div>
				<div class="mt-mo-3"><?php esc_html_e( 'Blocked IPs', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="m-mo-3" id="blockIPtable">
					<table id="blockedips_table" class="display">
						<thead class="">
							<tr class="text-mo-caption">
								<th>IP Address</th>
								<th>Reason</th>
								<th>Blocked Until</th>
								<th>Blocked Date</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>

							<?php
							global $wpns_db_queries;
							$blockedips      = $wpns_db_queries->mo2f_get_blocked_ip_list();
							$whitelisted_ips = $wpns_db_queries->mo2f_get_whitelisted_ips_list();
							$disabled        = '';
							global $mo2f_dir_name;
							foreach ( $blockedips as $blockedip ) {
								echo '<tr class="text-mo-caption font-normal"><td>' . esc_html( $blockedip->ip_address ) . '</td><td>' . esc_html( $blockedip->reason ) . '</td><td>';
								if ( empty( $blockedip->blocked_for_time ) ) {
									echo '<span class="text-red-500">Permanently</span>';
								} else {
									echo esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->blocked_for_time ) );
								}
								echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $blockedip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $disabled ) . " onclick=unblockip('" . esc_js( $blockedip->id ) . "')>Unblock IP</a></td></tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="mo2f-settings-div" id="mo2f_ip_whitelisting">
			<div class="mo2f-settings-head justify-between -ml-mo-9">
				<span><?php esc_html_e( 'IP Whitelisting', 'miniorange-2-factor-authentication' ); ?></span>
				<a href="https://developers.miniorange.com/docs/security/wordpress/wp-security/IP-blocking-whitelisting-lookup#wp-ip-whitelisting" target="_blank"><span class="dashicons dashicons-text-page mo2f-dash-icons-doc"></span></a>
			</div>
			<div class="mo2f-sub-settings-div">
				<div class="my-mo-3 mb-mo-3 flex">
					<div class="py-mo-2 pr-mo-2"><?php esc_html_e( 'Add new IP address to whitelist:', 'miniorange-2-factor-authentication' ); ?></div>
					<div class="pr-mo-2"><input type="text" name="IPWhitelist" id="IPWhitelist" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/></div>
					<div><input type="button" name="WhiteListIP" id="WhiteListIP" value="WhiteList IP" class="mo2f-save-settings-button" /></div>
				</div>
				<div class="mt-mo-3"><?php esc_html_e( 'Whitelisted IPs', 'miniorange-2-factor-authentication' ); ?></div>
				<div class="m-mo-3" id="WhiteListIPtable">
					<table id="whitelistedips_table" class="display">
						<thead>
							<tr class="text-mo-caption">
								<th>IP Address</th>
								<th>Whitelisted Date</th>
								<th>Remove from Whitelist</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $whitelisted_ips as $whitelisted_ip ) {
								echo '<tr class="text-mo-caption font-normal"><td>' . esc_html( $whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $whitelisted_ip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $disabled ) . " onclick=removefromwhitelist('" . esc_js( $whitelisted_ip->id ) . "')>Remove</a></td></tr>";
							}

							echo '			</tbody>
					</table>';
							?>
				</div>
			</div>
		</div>



		<div class="mo2f-settings-div" id="mo2f_ip_lookup">
			<div class="mo2f-settings-head justify-between -ml-mo-9">
				<span><?php esc_html_e( 'IP LookUp', 'miniorange-2-factor-authentication' ); ?></span>
				<a href='<?php echo esc_url( $two_factor_premium_doc['IP LookUp'] ); ?>' target="_blank"><span class="dashicons dashicons-text-page mo2f-dash-icons-doc"></span></a>
			</div>
			<div class="mo2f-sub-settings-div">
				<div class="my-mo-3 mb-mo-3 flex">
					<div class="py-mo-2 pr-mo-2"><?php esc_html_e( 'Enter IP address you Want to check:', 'miniorange-2-factor-authentication' ); ?></div>
					<div class="pr-mo-2"><input type="text" name="ipAddresslookup" id="ipAddresslookup" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/></div>
					<div><input type="button" name="LookupIP" id="LookupIP" value="Lookup IP" class="mo2f-save-settings-button" /></div>
				</div>
				<div class="m-mo-3" id="resultsIPLookup">
				</div>
			</div>
		</div>

<script>
	jQuery("#mo_2fa_advancedblocking").addClass("side-nav-active");
	jQuery("#ipblacklist").addClass("mo2f-subtab-active");
	jQuery('#BlockIP').click(function() {
		var ip = jQuery('#ManuallyBlockIP').val();
		var nonce = '<?php echo esc_js( wp_create_nonce( 'mo2f-ip-black-list-ajax-nonce' ) ); ?>';
		if('' !== ip)
		{
		var data = {
			'action'					: 'mo2f_ip_black_list_ajax',
			'IP'						:  ip,
			'nonce'						:  nonce,
			'option'					: 'mo_wpns_manual_block_ip'
			};
		jQuery.post(ajaxurl, data, function(response) {
			var response = response.replace(/\s+/g,' ').trim();
			if(response == 'empty IP')
			{
				error_msg("IP can not be blank.");
			} else if (response == 'already blocked') {
				error_msg("IP is already blocked.");
			} else if (response == 'INVALID_IP_FORMAT') {
				error_msg("IP does not match required format.");
			} else if (response == "IP_IN_WHITELISTED") {
				error_msg("IP is whitelisted can not be blocked.");
			} else {
				console.log('asdfsfsfsfsdf');
				refreshblocktable(response);
				success_msg("IP Blocked Sucessfully.");
			}
		});

		}

	});
	jQuery('#WhiteListIP').click(function(){
		var ip 	= jQuery('#IPWhitelist').val();
		var nonce ='<?php echo esc_js( wp_create_nonce( 'mo2f-ip-black-list-ajax-nonce' ) ); ?>' ;
		if(ip != '')
		{
			var data = {
				'action'					: 'mo2f_ip_black_list_ajax',
				'IP'						:  ip,
				'nonce'						:  nonce,
				'option'					: 'mo_wpns_whitelist_ip'
			};
			jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g,' ').trim();
				if(response == 'EMPTY IP')
				{
					error_msg("IP can not be empty.");
				}
				else if(response == 'INVALID_IP')
				{
					error_msg(" IP does not match required format.");
				}
				else if(response == 'IP_ALREADY_WHITELISTED')
				{
					error_msg("IP is already whitelisted.");
				}
				else
				{
					refreshWhiteListTable(response);
					success_msg("IP whitelisted Sucessfully.");
				}
			});		
		}
	});
	jQuery('#LookupIP').click(function() {
		jQuery('#resultsIPLookup').empty();
		var ipAddress = jQuery('#ipAddresslookup').val();
		var nonce = '<?php echo esc_js( wp_create_nonce( 'mo2f-ip-black-list-ajax-nonce' ) ); ?>';
		jQuery("#resultsIPLookup").empty();
		var img_loader_url = '<?php echo isset( $img_loader_url ) ? esc_url( $img_loader_url ) : ''; ?>';
		jQuery("#resultsIPLookup").append(
			"<img src=" + img_loader_url + ">");
		jQuery("#resultsIPLookup").slideDown(400);
		var data = {
			'action': 'mo2f_ip_black_list_ajax',
			'option': 'wpns_ip_lookup',
			'nonce': nonce,
			'IP': ipAddress
		};
		jQuery.post(ajaxurl, data, function(response) {
			if (response === 'INVALID_IP_FORMAT') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP did not match required format.");
			} else if (response === 'INVALID_IP') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP entered is invalid.");
			} else if (response.geoplugin_status === 404) {
				jQuery("#resultsIPLookup").empty();
				success_msg(" IP details not found.");
			} else if (response.geoplugin_status === 200 || response.geoplugin_status === 206) {
				jQuery('#resultsIPLookup').empty();
				jQuery('#resultsIPLookup').append(response.ipDetails);
			}

		});
	});
	jQuery("#blockedips_table").DataTable({
		"order": [[ 3, "desc" ]]
	});
	jQuery("#whitelistedips_table").DataTable({
		"order": [[ 1, "desc" ]]
	});
	function unblockip(id) {
		var nonce = '<?php echo esc_js( wp_create_nonce( 'mo2f-ip-black-list-ajax-nonce' ) ); ?>';
		if(id != '')
		{
			var data = {
			'action'					: 'mo2f_ip_black_list_ajax',
			'id'						:  id,
			'nonce'						:  nonce,
			'option'					: 'mo_wpns_unblock_ip'
			};
			jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g,' ').trim();
				if(response=="UNKNOWN_ERROR")
				{
					error_msg(" Unknow Error occured while unblocking IP.");
				}
				else
				{
					refreshblocktable(response);
					success_msg("IP unblocked Sucessfully.");
				}
			});				
		}
	}
	function removefromwhitelist(id) {
		var nonce = '<?php echo esc_js( wp_create_nonce( 'mo2f-ip-black-list-ajax-nonce' ) ); ?>';
		if (id !== '') {
			var data = {
				'action': 'mo2f_ip_black_list_ajax',
				'id': id,
				'nonce': nonce,
				'option': 'mo_wpns_remove_whitelist'
			};
			jQuery.post(ajaxurl, data, function(response) {
				var response = response.replace(/\s+/g, ' ').trim();
				if (response === 'UNKNOWN_ERROR') {
					error_msg(" Unknow Error occured while removing IP from Whitelist.");
				} else {
					refreshWhiteListTable(response);
					success_msg("IP removed from Whitelist.");
				}
			});

		}
	}
	function refreshblocktable(html) {
		jQuery('#blockIPtable').html(html);
	}

	function refreshWhiteListTable(html) {

		jQuery('#WhiteListIPtable').html(html);
	}
</script>
