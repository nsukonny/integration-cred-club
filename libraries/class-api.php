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
	 */
	public function get_order( WP_REST_Request $request ) {

		$secret_key = get_option( 'icclub-secret' );
		$secret_key = 'that_is_sekret_key_3342'; //TODO remove it

		if ( $request->get_header( 'secret' ) !== $secret_key ) {
			return new WP_Error(
				'woring_secret',
				__( 'Error. Your secret key is wrong.' ),
				array( 'status' => 404 )
			);
		}

		$order_id = $this->make_order( $request );

	}

	private function make_order( WP_REST_Request $request ) {
		global $woocommerce;

		$countries_object = new WC_Countries();
		$default_country  = $countries_object->get_base_country();
		$customer_name    = explode( ' ', $request['customer_name'] );
		$shipping_address = array(
			'first_name' => isset( $customer_name[0] ) ? sanitize_text_field( $customer_name[0] ) : '',
			'last_name'  => isset( $customer_name[1] ) ? sanitize_text_field( $customer_name[1] ) : '',
			'company'    => __( 'Order from cred add', 'icclub' ),
			'email'      => isset( $request['shipping_address_email'] )
				? sanitize_text_field( $request['shipping_address_email'] ) : '',
			'phone'      => isset( $request['phone'] ) ? sanitize_text_field( $request['phone'] ) : '',
			'address_1'  => isset( $request['shipping_address_line1'] )
				? sanitize_text_field( $request['shipping_address_line1'] ) : '',
			'address_2'  => isset( $request['shipping_address_line2'] )
				? sanitize_text_field( $request['shipping_address_line2'] ) : '',
			'city'       => isset( $request['shipping_address_city'] )
				? sanitize_text_field( $request['shipping_address_city'] ) : '',
			'state'      => $this->get_state_code( $request ),
			'postcode'   => isset( $request['shipping_address_pincode'] )
				? sanitize_text_field( $request['shipping_address_pincode'] ) : '',
			'country'    => $default_country,
		);

		$total_payed = isset( $request['order_sales_value'] )
			? sanitize_text_field( $request['order_sales_value'] ) : 0;

		$order   = wc_create_order();
		$product = wc_get_product( 291 );
		$order->add_product( $product, 1 ); // This is an existing SIMPLE product
		$order->set_address( $shipping_address, 'billing' );
		//$order->calculate_totals();
		$order->set_total( $total_payed );
		$order->set_discount_total( $product->get_price() - $total_payed );
		$order->update_status( 'processing', __( 'Getted from cred app', 'icclub' ), true );

		$note = __( 'Offer ID', 'icclub' ) . ' : ' . ( isset( $request['offer_id'] )
				? sanitize_text_field( $request['offer_id'] ) : '' ) . '<br>';
		$note .= __( 'Order ID', 'icclub' ) . ' : ' . ( isset( $request['order_id'] )
				? sanitize_text_field( $request['order_id'] ) : '' ) . '<br>';
		$note .= __( 'Order SKU =', 'icclub' ) . ' : ' . ( isset( $request['order_SKU'] )
				? sanitize_text_field( $request['order_SKU'] ) : '' ) . '<br>';
		$note .= __( 'Order value', 'icclub' ) . ' : ' . $total_payed . '<br>';
		$order->add_order_note( $note );

		wp_send_json( array( 'status' => 'success' ), null );
	}

	/**
	 * Get state code by state name
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return int|string
	 */
	private function get_state_code( WP_REST_Request $request ) {
		global $woocommerce;

		$state = isset( $request['shipping_address_state'] )
			? sanitize_text_field( $request['shipping_address_state'] ) : '';

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

}


function icclub_api_runner() {

	return new ICClub_API();
}

icclub_api_runner();