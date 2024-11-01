<?php
/*
 * Plugin Name: SWE Easy Orders Export
 * Description: Export order by status, and export orders by limit, Export orders in CSV file. 
 * Version: 1.1.0
 * Author: SanjayWebExpert
 * Author URI: http://sanjaywebexpert.com
 * License:    GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sweeasyexportorders
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'SWE_EXPORT_ORDERS_VERSION', '1.1.0' );

add_action( 'manage_posts_extra_tablenav', 'swe_easyexport_order_admin_order_list_top_bar_button', 20, 1 );
function swe_easyexport_order_admin_order_list_top_bar_button( $which ) {
    global $typenow;
    if ( 'shop_order' === $typenow && 'top' === $which ) {
        ?>
        <div class="alignleft actions custom">
			<button type="button" name="export_orders" class="button" id="export_order_Btn">Export Orders</button>
        </div>
		<div id="export_order_Modal" class="export_modal">
		  <div class="exportmodal-content">
			<span class="close_export">&times;</span>
			<h3>Swe Easy Export Orders By Status</h3>
			<?php
				$order_statuses = wc_get_order_statuses();
			?>
			<form name="swe_export_status_frm" id="swe_export_status_frm">
				<input type="hidden" name="swe_export_url" id="swe_export_url">
				<table class="exp_echeck_table">
				<?php
				foreach($order_statuses as $order_stats_key => $order_stats_val){
					echo '<tr><th>'.$order_stats_val.'</th><td><input name="swe_echeck_status_export[]" type="checkbox" class="order-fields woooe-field" value="'.$order_stats_key.'"></td></tr>';
				}
				?>
				</table>
				<table class="bottom_tbl">
					<tr><th>Limit</th><td><input type="number" id="order_limit" name="order_limit" value="10" placeholder="10"></td></tr>
				<tr><td></td><td><button type="button" class="button save_order button-primary" id="export_echeck_submit" name="export_echeck" value="Export Orders">Export</button></td>
				</tr>
				</table>
			
			</form>
		  </div>
		</div>
        <?php
    }	
}

add_action('init','swe_easyexport_orders_callfunc', 20);
function swe_easyexport_orders_callfunc(){
	if(isset($_GET['swe_export']) && !empty($_GET['swe_export']) && $_GET['swe_export']=="order"){
		$delimiter = ",";
		$swefilename = "orders_export_" . date('Y-m-d-h-m-s') . ".csv";
		$swef = fopen('php://memory', 'w');
		$fields = array('Order ID', 'Status', 'Total', 'Billing First Name', 'Billing Last Name', 'Billing Email', 'Billing Phone', 'Payment Method', 'Payment Method Title', 'Date');
		fputcsv($swef, $fields, $delimiter);
		global $wpdb;
		$limit = !empty($_GET['limit']) ? absint($_GET['limit']) : 999999999;
		$export_order_statuses = !empty($_GET['swe_echeck_status_export']) ? wc_clean($_GET['swe_echeck_status_export']) : 'any';
		
		$orderquery_args = array(
			'fields' => 'ids',
			'post_type' => 'shop_order',
			'post_status' => $export_order_statuses,
			'posts_per_page' => $limit,
		);
		$orderquery = new WP_Query($orderquery_args);
		$exportorder_ids = $orderquery->posts;
		
		foreach($exportorder_ids as $order_id){
			$order = wc_get_order($order_id);
			$order_data = $order->get_data();
			$order_id = $order_data['id'];
			$order_status = $order_data['status'];
			$order_currency = $order_data['currency'];
			$order_total = $order_data['total'];
			$order_payment_method_title = $order_data['payment_method_title'];
			$order_payment_method = $order_data['payment_method'];
			$order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
			$order_billing_first_name = $order_data['billing']['first_name'];
			$order_billing_last_name = $order_data['billing']['last_name'];
			$order_billing_email = $order_data['billing']['email'];
			$order_billing_phone = $order_data['billing']['phone'];
			$total = $order_currency.' '.$order_total;
			$lineData = array($order_id, $order_status, $total, $order_billing_first_name, $order_billing_last_name, $order_billing_email, $order_billing_phone, $order_payment_method,  $order_payment_method_title, $order_date_created );
			fputcsv($swef, $lineData, $delimiter);
		}
		fseek($swef, 0);
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $swefilename . '";');
		header('Pragma: no-cache');
		header('Expires: 0');
		fpassthru($swef);
		fclose($swef);
		exit;
	}
}
function swe_easyexport_orders_js() {
	if ( 'shop_order' != get_post_type() ) :
		return;
	endif;
	?>
	<script>
	// Get the modal
	var modal = document.getElementById("export_order_Modal");
	var btn = document.getElementById("export_order_Btn");

	var span = document.getElementsByClassName("close_export")[0];
	btn.onclick = function() {
	  modal.style.display = "block";
	}
	span.onclick = function() {
	  modal.style.display = "none";
	}
	window.onclick = function(event) {
	  if (event.target == modal) {
		modal.style.display = "none";
	  }
	}
	jQuery(function() {
        // listen for changes on the checkboxes
        jQuery('input[name="swe_echeck_status_export[]"]').change(function() {
            // have an empty array to store the values in
            let values = [];
            // check each checked checkbox and store the value in array
            jQuery.each(jQuery('input[name="swe_echeck_status_export[]"]:checked'), function(){
                values.push('swe_echeck_status_export[]='+jQuery(this).val());
            });
            // convert the array to string and store the value in hidden input field
			values_param = values.join("&");
            jQuery('#swe_export_url').val(values_param.toString());
        });
		jQuery('#export_echeck_submit').click(function(){
		var swe_export_url = jQuery('#swe_export_url').val();
		var limit = jQuery('#order_limit').val();
		var url='edit.php?post_type=shop_order&swe_export=order&limit='+limit+'&'+swe_export_url;
		//console.log(url);
		window.location.href='edit.php?post_type=shop_order&swe_export=order&'+url;
		});
    });
	</script>
	<?php
}
function swe_easyexport_orders_custom_style() {
	if ( 'shop_order' != get_post_type() ) :
		return;
	endif;
	?>
	<style>
	.export_modal {
	  display: none; /* Hidden by default */
	  position: fixed; /* Stay in place */
	  z-index: 1; /* Sit on top */
	  padding-top: 100px; /* Location of the box */
	  left: 0;
	  top: 0;
	  width: 100%; /* Full width */
	  height: 100%; /* Full height */
	  overflow: auto; /* Enable scroll if needed */
	  background-color: rgb(0,0,0); /* Fallback color */
	  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
	}
	.exportmodal-content {
	  background-color: #fefefe;
	  margin: auto;
	  padding: 20px;
	  border: 1px solid #888;
	  width: 100%;
	  max-width: 650px;
	  max-height: 430px;
	  overflow-y: scroll;
	}
	.close_export {
	  color: #aaaaaa;
	  float: right;
	  font-size: 28px;
	  font-weight: bold;
	}

	.close_export:hover,
	.close_export:focus {
	  color: #000;
	  text-decoration: none;
	  cursor: pointer;
	}
	.exp_echeck_table .order-fields{
		height: 15px !important;
		width: 15px;
	}
	.exp_echeck_table th{
		padding:5px;
		text-align:left;
			font-weight: normal;
		font-size: 15px;
	}
	table.exp_echeck_table tr {
		width: 25%;
		display: inline-table;
		margin-right: 5%;
	}
	table.exp_echeck_table td {
		text-align: right;
	}
	.bottom_tbl{
		width:98%;
	}
	table.bottom_tbl th {
		padding: 8px;
		font-weight: normal;
		font-size: 15px;
	}
	table.bottom_tbl tr {
		width: 50%;
		display: inline;
	}
	button#export_echeck_submit {
		margin-left: 15px;
		padding-left: 20px;
		padding-right: 20px;
	}
	</style>
	<?php
}
add_action( 'admin_footer', 'swe_easyexport_orders_js' );
add_action('admin_head', 'swe_easyexport_orders_custom_style'); 