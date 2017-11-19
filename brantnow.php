<?php
/*
 * Plugin Name: BrantNOW App Integration
 * Plugin URI: http://radleysustaire.com/
 * Description: Adds a section for Real Estate/Open House, Garage Sales and Events - to be integrated into the BrantNOW mobile app.
 * Version: 1.0.3
 * Author: Radley Sustaire
 * Author URI: http://radleysustaire.com/
 * License: Copyright (c) 2017 Jamie Stephens, Owner of BrantNOW
 *
 * Requires at least: 3.8
 * Tested up to: 4.8.1
 */

if ( !defined( 'ABSPATH' ) ) exit; // Do not allow direct access

/**
 * Main plugin class for the BrantNOW plugin.
 * @class BrantNOW
 */
if ( !class_exists( 'BrantNOW' ) ) {
	class BrantNOW
	{
		// Plugin settings
		public $version = '1.0.3';
		public $plugin_dir = null;
		public $plugin_url = null;
		public $plugin_basename = null;
		
		/**
		 * BrantNOW constructor
		 */
		public function __construct() {
			$this->plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
			$this->plugin_url = plugins_url( '', __FILE__ );
			$this->plugin_basename = plugin_basename( __FILE__ );
			
			// Finish setting up the plugin once other plugins have loaded, for compatibility.
			add_action( 'plugins_loaded', array( &$this, 'setup_plugin' ), 20 );
		}
		
		/**
		 * Initializes the rest of our plugin
		 */
		public function setup_plugin() {
			if ( !class_exists( 'acf' ) ) {
				add_action( 'admin_notices', 'bn_acf_not_running' );
				return;
			}
			
			include( 'includes/general.php' );
			include( 'includes/open-house.php' );
			include( 'includes/estate-agencies.php' );
			include( 'includes/garage-sales-and-events.php' );
			include( 'includes/restaurants.php' );
			include( 'includes/users.php' );
			include( 'includes/memberpress.php' );
			
			include( 'shortcodes/bn_manage_open_houses.php' );
			include( 'shortcodes/bn_add_garage_sale.php' );
			include( 'shortcodes/bn_add_event.php' );
			include( 'shortcodes/bn_add_restaurant.php' );
			
			/*
			if ( !class_exists('GeoQueryContext') ) {
				if ( file_exists('/assets/geo-query/geo-query.php') ) {
					include( '/assets/geo-query/geo-query.php' );
				}else{
					wp_die('Error: WordPress Geo Query is required - https://github.com/birgire/geo-query');
					exit;
				}
			}
			*/
		}
	}
}

function bn_acf_not_running() {
	?>
	<div class="error">
		<p><strong>BrantNOW App Integration: Error</strong></p>
		<p>The required plugin <strong>Advanced Custom Fields Pro</strong> is not running. Please activate ACF Pro or disable this plugin.</p>
	</div>
	<?php
}

function bn_activate_plugin() {
	include_once( 'includes/users.php' );
	bn_add_user_roles_and_capabilities();
}
register_activation_hook( __FILE__, 'bn_activate_plugin' );


// Create our plugin object, accessible via a global variable.
global $BrantNOW;
$BrantNOW = new BrantNOW();