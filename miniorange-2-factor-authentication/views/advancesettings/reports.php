<?php
/**
 * Display login transations report and Error report.
 *
 * @package miniorange-2-factor-authentication/reports/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'reports.php';
echo '<div>
		<div class="mo2f-settings-div">';

echo '	<div>

		<form name="f" method="post" action="" id="manualblockipform" >
		<table>
            <tr>
                <td style="width: 100%">
                    <div class="mo2f-settings-head">
					<label class="mo2f_checkbox_container"><input type="checkbox" onChange="mo2f_enable_login_transactions_toggle()" id="mo2f_enable_login_report" name="mo2f_enable_login_report" value="1"';
					checked( get_site_option( 'mo2f_enable_login_report' ), 'true' );
					echo '><span class="mo2f-settings-checkmark"></span></label>
                        Enable Login Transactions Report
                    </div>
                </td>
		        <td>
                    <input type="button"" id="mo2f_clear_login_report" class="mo2f-reset-settings-button" value="Clear Login Reports" />
                </td>
            </tr>
        </table>
		<br>
	</form>
		</div>
		
			<div class="mo2f-settings-div hidden">	
				<div style="float:right;margin-top:10px">
					<input type="submit" name="printcsv" style="width:100px;" value="Print PDF" class="mo2f-reset-settings-button">
					<input type="submit" name="printpdf" style="width:100px;" value="Print CSV" class="mo2f-reset-settings-button">
				</div>
				<h3>Advanced Report</h3>
				
				<form id="mo_wpns_advanced_reports" method="post" action="">
					<input type="hidden" name="option" value="mo_wpns_advanced_reports">
					<table style="width:100%">
					<tr>
					<td width="33%">WordPress Username : <input class="mo_wpns_table_textbox" type="text" name="username" required="" placeholder="Search by username" value=""></td>
					<td width="33%">IP Address :<input class="mo_wpns_table_textbox" type="text" name="ip" required="" placeholder="Search by IP" value=""></td>
					<td width="33%">Status : <select name="status" style="width:100%;">
						  <option value="success" selected="">Success</option>
						  <option value="failed">Failed</option>
						</select>
					</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
					<td width="33%">User Action : <select name="action" style="width:100%;">
						  <option value="login" selected="">User Login</option>
						  <option value="register">User Registeration</option>
						</select>
					</td>
					<td width="33%">From Date : <input class="mo_wpns_table_textbox" type="date"  name="fromdate"></td>
					<td width="33%">To Date :<input class="mo_wpns_table_textbox" type="date"  name="todate"></td>
					</tr>
					</table>
					<br><input type="submit" name="Search" style="width:100px;" value="Search" class="mo2f-save-settings-button">
				</form>
				<br>
			</div>
			
			<table id="login_reports" class="display" cellspacing="0" width="100%">
		        <thead>
		            <tr>
		                <th>IP Address</th>
						<th>Username</th>
						<th>Status</th>
		                <th>TimeStamp</th>
		            </tr>
		        </thead>
		        <tbody>';

				show_login_transactions( $logintranscations );

echo '	        </tbody>
		    </table>
		</div>
	</div>
<script>
	jQuery("#reports").addClass("mo2f-subtab-active");
	jQuery("#mo_2fa_advance_settings").addClass("side-nav-active");	
</script>';



