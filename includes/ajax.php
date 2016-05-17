<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPVW_Ajax {

	public static function init(){
		$ajax_events = array(
			'adjust_user_wallet' => false,
			'get_user_balance'	=> false,
			'bulk_deposit'	=> flase
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ){
			add_action( 'wp_ajax_wpvw_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ){
				add_action( 'wp_ajax_nopriv_wpvw_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Get a refreshed cart fragment
	 */
	public static function adjust_user_wallet (){
		
		// check to ensure the current user can manage WC
		if( !current_user_can( 'manage_woocommerce' ) ){
			exit;
		}

		parse_str( $_POST['data'], $params );
		extract( $params );

		/** get the user current balance */
		$current_user_balance = floatval( get_user_meta($user,'_uw_balance', true ) );
		
		// Check if there is a balance and define default if not
		if( $current_user_balance == '' || !$current_user_balance ){
			$current_user_balance = floatval( 0 );
		}

		$credit_amount = floatval( $credit_amount );
		$new_balance = floatval( 0 );

		if( $adjustment_type == 'add' ){
			$new_balance = $current_user_balance+$credit_amount;
		}elseif ($adjustment_type == 'subtract' ){
			$new_balance = $current_user_balance-$credit_amount;
		}elseif($adjustment_type == 'update' ){
			$new_balance = $credit_amount;
		}
		
		// Update the user wallet
		update_user_meta( $user, '_uw_balance', $new_balance );

		// Setup a return array
		$return = array(
			'status' => true,
			'credit_amount' => $credit_amount,
			'new_balance' => wc_price( $new_balance ),
			'message' => "Users blance has been updated to: " . $new_balance
			);

		// Return JSON response
		print json_encode($return);
		exit;
	}

	/**
	 * return user balance formated as dollars
	 * @return [type] [description]
	 */
	function get_user_balance (){
		
		// check to ensure the current user can manage WC
		if( !current_user_can( 'manage_woocommerce' )){
			exit;
		}

		$current_user_balance = get_user_meta( $_POST['user_id'], '_uw_balance', true);
		$return = array(
			'status' => true,
			'balance' => wc_price( $current_user_balance )
			);
		print json_encode($return);
		exit;
	}

	/**
	 * Makes a bulk deposit to all users in WordPress
	 * @return [type] [description]
	 */
	function bulk_deposit(){

		// check to ensure the current user can manage WC
		if( !current_user_can( 'manage_woocommerce' ) ){
			exit;
		}
		parse_str( $_POST['data'], $params );

		// Get the amount that needs to be deposited
		$credit_amount = floatval( $params['credit_amount'] );

		// Update each users wallet
		$users = get_users();
		foreach ( $users as $user ) {

			$current_user_balance = get_user_meta( $user->ID, '_uw_balance', true );
			if( $current_user_balance == '' || !$current_user_balance ){
				$current_user_balance = floatval( 0 );
			}

			// Determine the new balance
			$new_balance = $current_user_balance+$credit_amount;
			update_user_meta( $user->ID, '_uw_balance', $new_balance );
		}

		$return = array(
			'status' => true,
			'message' => 'Deposit has been made'
			);

		print json_encode($return);
		exit;
	}

}
WPVW_Ajax::init();