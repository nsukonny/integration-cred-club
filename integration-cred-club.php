<?php
/**
 * Plugin Name: Integration Cred.club
 * Plugin URI: https://wordpress.org/plugins/integration-cred-club/
 * Description: API integration with Cred.club app, for get users data by orders
 * Version: 1.0.1
 * Author: NSukonny
 * Author URI: https://nsukonny.ru
 * Text Domain: icclub
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 4.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ICClub' ) ) {

	include_once dirname( __FILE__ ) . '/libraries/class-icclub.php';

}

/**
 * The main function for returning ICClub instance
 *
 * @since 1.0.0
 *
 * @return object The one and only true ICClub instance.
 */
function icclub_runner() {

	return ICClub::instance();
}

icclub_runner();