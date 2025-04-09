<?php
/**
 * This contains view of the login transaction report.
 *
 * @package miniorange-2-factor-authentication/reports/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '<div>
		<div class="mo2f-settings-div">';

echo '	<div>

		<form name="mo2f_manualblockipform" method="post" action="" id="manualblockipform">
		<table>
            <tr>
                <td style="width: 100%">
                    <div class="mo2f-settings-head">
					<label class="mo2f_checkbox_container"><input type="checkbox" onChange="mo2f_enable_login_transactions_toggle()"id="mo2f_enable_login_report" name="mo2f_enable_login_report" value="1"';
					checked( get_site_option( 'mo2f_enable_login_report' ), 'true' );
					echo '><span class="mo2f-settings-checkmark"></span></label>
                        Enable Login Transactions Report
                    </div>
                </td>
		        <td>
                    <input type="button"" id="mo2f_clear_login_report" class="mo2f-reset-settings-button" value="Clear Login Report" />
                </td>
            </tr>
        </table>
		<br>
	</form>
		</div>
			
			<table id="mo2f_login_transactions_table" class="display" cellspacing="0" width="100%">
		        <thead>
		            <tr>
		                <th>IP Address</th>
						<th>Username</th>
						<th>Status</th>
		                <th>TimeStamp</th>
		            </tr>
		        </thead>
		        <tbody>';
				$common_helper->mo2f_show_login_transactions_report( $logintranscations );

echo '	        </tbody>
		    </table>
		</div>
	</div>
<script>
	jQuery("#loginreport").addClass("mo2f-subtab-active");
	jQuery("#mo_2fa_reports").addClass("side-nav-active");
</script>';
