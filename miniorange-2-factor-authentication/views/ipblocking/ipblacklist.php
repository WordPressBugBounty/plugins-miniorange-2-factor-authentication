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
		</div>
		<p class="mo2f-setting-desc mt-mo-2"><?php esc_html_e( 'Block a specific IP address from accessing your entire site, either permanently or for a set duration.', 'miniorange-2-factor-authentication' ); ?></p>
			<div class="mo2f-sub-settings-div" style="border-left:none;">
				<div class="my-mo-3 mb-mo-3" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
					<div class="mo-input-wrapper" style="width:260px;">
						<label class="mo-input-label"><?php esc_html_e( 'Manually block an IP address here:', 'miniorange-2-factor-authentication' ); ?></label>
						<input type="text" class="mo-input w-full" name="ManuallyBlockIP" id="ManuallyBlockIP" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/>
					</div>
					<input type="button" name="BlockIP" id="BlockIP" value="<?php esc_attr_e( 'Manual Block IP', 'miniorange-2-factor-authentication' ); ?>" class="mo2f-save-settings-button" style="margin-bottom:0;" />
				</div>
				<div class="mt-mo-3 mb-mo-2" style="font-weight:600;"><?php esc_html_e( 'Blocked IPs', 'miniorange-2-factor-authentication' ); ?></div>
				<div id="blockIPtable">
					<table id="blockedips_table" class="display">
						<thead>
							<tr class="text-mo-caption">
								<th><?php esc_html_e( 'IP Address', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Blocked Until', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Blocked Date', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Action', 'miniorange-2-factor-authentication' ); ?></th>
							</tr>
						</thead>
						<tbody>

							<?php
							global $mo2f_wpns_db_queries;
							$mo2f_blocked_ips     = $mo2f_wpns_db_queries->mo2f_get_blocked_ip_list();
							$mo2f_whitelisted_ips = $mo2f_wpns_db_queries->mo2f_get_whitelisted_ips_list();
							$mo2f_disabled        = '';
							global $mo2f_dir_name;
							foreach ( $mo2f_blocked_ips as $mo2f_blocked_ip ) {
								echo '<tr class="text-mo-caption font-normal"><td>' . esc_html( $mo2f_blocked_ip->ip_address ) . '</td><td>' . esc_html( $mo2f_blocked_ip->reason ) . '</td><td>';
								if ( empty( $mo2f_blocked_ip->blocked_for_time ) ) {
									echo '<span class="text-red-500">Permanently</span>';
								} else {
									echo esc_html( gmdate( 'M j, Y, g:i:s a', $mo2f_blocked_ip->blocked_for_time ) );
								}
								echo '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $mo2f_blocked_ip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $mo2f_disabled ) . " onclick=unblockip('" . esc_js( $mo2f_blocked_ip->id ) . "')>Unblock IP</a></td></tr>";
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
			</div>
			<p class="mo2f-setting-desc mt-mo-2"><?php esc_html_e( 'Exempt a trusted IP address from all blocking and lockout checks on this page, so it is never auto-blocked or locked out after failed login attempts. This does not bypass 2FA.', 'miniorange-2-factor-authentication' ); ?></p>
			<div class="mo2f-sub-settings-div" style="border-left:none;">
				<div class="my-mo-3 mb-mo-3" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
					<div class="mo-input-wrapper" style="width:260px;">
						<label class="mo-input-label"><?php esc_html_e( 'Add new IP address to whitelist:', 'miniorange-2-factor-authentication' ); ?></label>
						<input type="text" class="mo-input w-full" name="IPWhitelist" id="IPWhitelist" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/>
					</div>
					<input type="button" name="WhiteListIP" id="WhiteListIP" value="<?php esc_attr_e( 'WhiteList IP', 'miniorange-2-factor-authentication' ); ?>" class="mo2f-save-settings-button" style="margin-bottom:0;" />
				</div>
				<div class="mt-mo-3 mb-mo-2" style="font-weight:600;"><?php esc_html_e( 'Whitelisted IPs', 'miniorange-2-factor-authentication' ); ?></div>
				<div id="WhiteListIPtable">
					<table id="whitelistedips_table" class="display">
						<thead>
							<tr class="text-mo-caption">
								<th><?php esc_html_e( 'IP Address', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Whitelisted Date', 'miniorange-2-factor-authentication' ); ?></th>
								<th><?php esc_html_e( 'Remove from Whitelist', 'miniorange-2-factor-authentication' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $mo2f_whitelisted_ips as $mo2f_whitelisted_ip ) {
								echo '<tr class="text-mo-caption font-normal"><td>' . esc_html( $mo2f_whitelisted_ip->ip_address ) . '</td><td>' . esc_html( gmdate( 'M j, Y, g:i:s a', $mo2f_whitelisted_ip->created_timestamp ) ) . '</td><td><a ' . esc_attr( $mo2f_disabled ) . " onclick=removefromwhitelist('" . esc_js( $mo2f_whitelisted_ip->id ) . "')>Remove</a></td></tr>";
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
			</div>
			<p class="mo2f-setting-desc mt-mo-2"><?php esc_html_e( 'Look up the location, ISP and ownership details of an IP address before deciding whether to block it.', 'miniorange-2-factor-authentication' ); ?></p>
			<div class="mo2f-sub-settings-div" style="border-left:none;">
				<div class="my-mo-3 mb-mo-3" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
					<div class="mo-input-wrapper" style="width:260px;">
						<label class="mo-input-label"><?php esc_html_e( 'Enter IP address you want to check:', 'miniorange-2-factor-authentication' ); ?></label>
						<input type="text" class="mo-input w-full" name="ipAddresslookup" id="ipAddresslookup" required placeholder='IP address' pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}"/>
					</div>
					<input type="button" name="LookupIP" id="LookupIP" value="<?php esc_attr_e( 'Lookup IP', 'miniorange-2-factor-authentication' ); ?>" class="mo2f-save-settings-button" style="margin-bottom:0;" />
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
			if (response === 'ajax-error') {
				error_msg("Unknown error occurred while blocking IP.");
			} else if (response == 'empty IP')
			{
				error_msg("IP can not be blank.");
			} else if (response == 'already blocked') {
				error_msg("IP is already blocked.");
			} else if (response == 'INVALID_IP_FORMAT') {
				error_msg("IP does not match required format.");
			} else if (response == "IP_IN_WHITELISTED") {
				error_msg("IP is whitelisted can not be blocked.");
			} else if (response == "CANNOT_BLOCK_OWN_IP") {
				error_msg("That is your own current IP address. Blocking it would lock you out of the entire site, so it was not saved.");
			} else {
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
				if (response === 'ajax-error') {
					error_msg("Unknown error occurred while whitelisting IP.");
				}
				else if(response == 'EMPTY IP')
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
			'option': 'mo_wpns_ip_lookup',
			'nonce': nonce,
			'IP': ipAddress
		};
		jQuery.post(ajaxurl, data, function(response) {
			if (response === 'ajax-error') {
				error_msg("Unknown error occurred while looking up IP.");
			} else if (response === 'INVALID_IP_FORMAT') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP did not match required format.");
			} else if (response === 'INVALID_IP') {
				jQuery("#resultsIPLookup").empty();
				error_msg("IP entered is invalid.");
			} else if (response === 'INVALID_RESPONSE') {
				jQuery("#resultsIPLookup").empty();
				success_msg(" IP details not found.");
			} else if (response.status === 'SUCCESS') {
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
				if(response=="ajax-error")
				{
					error_msg("Unknown error occurred while unblocking IP.");
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
				if (response === 'ajax-error') {
					error_msg("Unknown error occurred while removing IP from Whitelist.");
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
