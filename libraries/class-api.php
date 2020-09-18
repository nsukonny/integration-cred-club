<?php
/**
 * Class ICClub_API
 * Extends WordPress API for integrate with cred.club app
 *
 * @since 1.0.0
 */

class ICClub_API {

	/**
	 * ICClub_API constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'rest_api_init', function () {
			register_rest_route( 'icclub/v1', '/offer/cred', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'get_order' ),
			) );
		} );

	}

	/**
	 * Get order data from app by API
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return false|string
	 * @throws WC_Data_Exception
	 */
	public function get_order( WP_REST_Request $request ) {

		$secret_key = get_option( 'icclub-secret-key' );

		if ( $request->get_header( 'secret' ) !== $secret_key ) {
			return new WP_Error(
				'woring_secret',
				__( 'Error. Your secret key is wrong.' ),
				array( 'status' => 404 )
			);
		}

		$json = json_decode( $request->get_body(), true );
		$this->log( $json );
		$order_id = $this->make_order( $json );
		if ( $order_id ) {
			wp_send_json( array( 'status' => 'success' ), null );
		} else {
			wp_send_json( array( 'status' => 'fail' ), null );
		}
	}

	/**
	 * Make order from taked data
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $json
	 *
	 * @return string
	 * @throws WC_Data_Exception
	 */
	private function make_order( $json ) {
		global $woocommerce;

		$countries_object = new WC_Countries();
		$default_country  = $countries_object->get_base_country();
		$customer_name    = explode( ' ', $json['customer_name'] );
		$shipping_address = array(
			'first_name' => isset( $customer_name[0] ) ? sanitize_text_field( $customer_name[0] ) : '',
			'last_name'  => isset( $customer_name[1] ) ? sanitize_text_field( $customer_name[1] ) : '',
			'company'    => __( 'Order from cred app', 'icclub' ),
			'email'      => isset( $json['shipping_address_email'] )
				? sanitize_text_field( $json['shipping_address_email'] ) : '',
			'phone'      => isset( $json['phone'] ) ? sanitize_text_field( $json['phone'] ) : '',
			'address_1'  => isset( $json['shipping_address_line1'] )
				? sanitize_text_field( $json['shipping_address_line1'] ) : '',
			'address_2'  => isset( $json['shipping_address_line2'] )
				? sanitize_text_field( $json['shipping_address_line2'] ) : '',
			'city'       => isset( $json['shipping_address_city'] )
				? sanitize_text_field( $json['shipping_address_city'] ) : '',
			'state'      => $this->get_state_code( $json ),
			'postcode'   => isset( $json['shipping_address_pincode'] )
				? sanitize_text_field( $json['shipping_address_pincode'] ) : '',
			'country'    => $default_country,
		);

		$total_payed = isset( $json['order_sales_value'] )
			? sanitize_text_field( $json['order_sales_value'] ) : 0;

		$order      = wc_create_order();
		$product_id = ! empty( $json['order_SKU'] ) ? wc_get_product_id_by_sku( $json['order_SKU'] ) : 0;
		if ( 0 !== $product_id && $product_id ) {
			$product = wc_get_product( $product_id );
			$order->add_product( $product, 1 ); // This is an existing SIMPLE product
			$order->set_address( $shipping_address, 'billing' );
			//$order->calculate_totals();
			$order->set_total( $total_payed );
			$order->set_discount_total( $product->get_price() - $total_payed );
			$order->update_status( 'processing', __( 'Getted from cred app', 'icclub' ), true );
			$note = __( 'Offer ID', 'icclub' ) . ' : ' . ( isset( $json['offer_id'] )
					? sanitize_text_field( $json['offer_id'] ) : '' ) . '<br>';
			$note .= __( 'Order ID', 'icclub' ) . ' : ' . ( isset( $json['order_id'] )
					? sanitize_text_field( $json['order_id'] ) : '' ) . '<br>';
			$note .= __( 'Order SKU =', 'icclub' ) . ' : ' . ( isset( $json['order_SKU'] )
					? sanitize_text_field( $json['order_SKU'] ) : '' ) . '<br>';
			$note .= __( 'Order value', 'icclub' ) . ' : ' . $total_payed . '<br>';
			$order->add_order_note( $note );

			return $order->get_order_number();
		}

		return null;
	}

	/**
	 * Get state code by state name
	 *
	 * @since 1.0.0
	 *
	 * @param $json
	 *
	 * @return int|string
	 */
	private function get_state_code( $json ) {
		global $woocommerce;

		$state = isset( $json['shipping_address_state'] )
			? sanitize_text_field( $json['shipping_address_state'] ) : '';

		$countries_object = new WC_Countries();
		$default_country  = $countries_object->get_base_country();
		$states           = $countries_object->get_states( $default_country );

		foreach ( $states as $state_code => $county_title ) {
			if ( strtolower( $county_title ) == strtolower( $state ) ) {
				return $state_code;
			}
		}

		return '';
	}

	/**
	 * Save all request to log for check
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request
	 */
	private function write_log( WP_REST_Request $request ) {

		$file = 'requests.txt';
		if ( file_exists( $file ) ) {
			$current = file_get_contents( $file );
		} else {
			$current = '';
		}

		$current .= date( 'd.m.Y H:i:s' ) . ' ' . print_r( $request, true ) . '\n';
		file_put_contents( $file, $current );

	}

	/**
	 * Write requests to log
	 *
	 * @since 1.0.0
	 *
	 * @param $json
	 */
	private function log( $json ) {

		$log  = '';
		$file = 'requests.log';

		if ( file_exists( $file ) ) {
			$log = file_get_contents( $file );
		}

		$log .= PHP_EOL . date( 'd.m.Y H:i:s' ) . ' : ' . PHP_EOL;
		$log .= print_r( $json, true );

		file_put_contents( $file, $log );
	}

}


function icclub_api_runner() {

	return new ICClub_API();
}

icclub_api_runner();