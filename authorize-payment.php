<?php
/**
 * @package authorize
 */
/*
Plugin Name: Authorize Payment Gateway
Plugin URI: https://stallioni.com/
Description: Authorize Payment Gateway.
Version: 1.0
Author: Stallioni
License: GPLv2 or later
Text Domain: authorize-payment
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

// Make sure we don't expose any info if called directly

define( 'AUTHORIZE_PAYEMNT_VERSION', '1.0' );
define( 'AUTHORIZE_PAYEMNT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AUTHORIZE_PAYEMNT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

if( ! function_exists('authorize_form_func')){
	function authorize_form_func($data){
		
		echo '<form action="'. AUTHORIZE_PAYEMNT_PLUGIN_URL .'authorize/PaymentTransactions/charge-credit-card.php" method="post">';
			echo '<input type="text" name="username">';
			echo '<input type="submit" name="submit" value="submit">';
		echo '</form>';
	}
}
add_filter("authorize_form", "authorize_form_func", 5);

add_action("admin_menu", "authorize_menu_pages");

function authorize_menu_pages(){
	add_menu_page("Authorize Payment", "Authorize Payment", "manage_options", "authorize-payment", "authorize_menu_ouput");
	add_submenu_page('authorize-payment', 'Transaction List', 'Transaction List', 'manage_options', 'authorize-payment-transaction' ,'authorize_payment_transaction' );
	add_action( 'admin_init', 'register_authorize_payment_settings' );
}
function register_authorize_payment_settings(){
	register_setting( 'authorize-payment-settings', 'authorize_payment_login' );
	register_setting( 'authorize-payment-settings', 'authorize_payment_transactionkey' );
	register_setting( 'authorize-payment-settings', 'payment_commission' );
}
function authorize_menu_ouput(){
	?>
	<div class="wrap">
<h1>Authorize Payment Gateway Settings</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'authorize-payment-settings' ); ?>
    <?php do_settings_sections( 'authorize-payment-settings' ); ?>
    <table class="form-table">
        <tr valign="top">
			<th scope="row">Login ID</th>
			<td>
				<input type="text" name="authorize_payment_login" value="<?php echo esc_attr( get_option('authorize_payment_login') ); ?>" />
			</td>
        </tr>
        <tr valign="top">
			<th scope="row">Transaction Key</th>
			<td>
				<input type="text" name="authorize_payment_transactionkey" value="<?php echo esc_attr( get_option('authorize_payment_transactionkey') ); ?>" />
			</td>
        </tr>
        <tr valign="top">
			<th scope="row">Commission(%)</th>
			<td>
				<input type="text" name="payment_commission" value="<?php echo esc_attr( get_option('payment_commission') ); ?>" />
			</td>
        </tr>
    </table>
    
    <?php submit_button(); ?>

</form>
<?php 
}
function authorize_payment_transaction(){
	?>
	<h1>Authorize Payment Transactions</h1>
<?php }

//
if(is_admin())
{
    new Authorize_Wp_List_Table();
}

/**
 * Authorize_Wp_List_Table class will create the page to load the table
 */
class Authorize_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'add_menu_authorize_list_table_page' ));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_authorize_list_table_page()
    {
        add_menu_page( 'Payment Transactions', 'Payment Transactions', 'manage_options', 'authorize-transaction-list.php', array($this, 'transaction_list_page') );
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function transaction_list_page()
    {
        $transactionListTable = new Transaction_List_Table();
        $transactionListTable->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Transaction List Page</h2>
                <?php $transactionListTable->display(); ?>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Transaction_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 2;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'          		=> 'ID',
            'transaction_id'    => 'Transaction ID',
            'customer' 			=> 'Photographer',
            'model'        		=> 'Modal',
            'paid_amt'   	 	=> 'Paid Amount',
            'date'      		=> 'Date',
			'status'      		=> 'Status',
			'tr_action'      	=> 'Action'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('id' => array('id', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
		$columns = array(
            'id'          		=> 'ID',
            'transaction_id'    => 'Transaction ID',
            'customer' 			=> 'Photographer',
            'model'        		=> 'Modal',
            'paid_amt'   	 	=> 'Paid Amount',
            'date'      		=> 'Date',
			'status'      		=> 'Status',
			'tr_action'      	=> 'Action'
        );
		global $wpdb;
		$transaction_data = $wpdb->get_results( "SELECT * FROM payment_transaction");
		//$data[] = '';
		foreach($transaction_data as $trans_data ){
			$customer_id 	= $trans_data->customer_id;
			$model_id 		= $trans_data->model_id;
			$customer_data = get_user_by( 'ID', $customer_id );
			$model_data = get_user_by( 'ID', $model_id );
			if($trans_data->status == 1){
				$status = 'Event Pending';
			}elseif($trans_data->status == 2){
				$status = 'Event Confirmed';
			}else{
				$status = 'Event Canceled';
			}
			$data[] = array(
				'id'          		=> 	$trans_data->id,
				'transaction_id'	=> 	$trans_data->transaction_id,
				'customer' 			=> 	$customer_data->user_nicename,
				'model'        		=> 	$model_data->user_nicename,
				'paid_amt'    		=> 	$trans_data->payment,
				'date'      		=> 	$trans_data->created_at,
				'status'			=> 	$status,
				'tr_action'			=>	'<p class="">paid</p>'
			);
			 
		}
       return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'transaction_id':
            case 'customer':
            case 'model':
            case 'paid_amt':
            case 'date':
			case 'status':
			case 'tr_action':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}