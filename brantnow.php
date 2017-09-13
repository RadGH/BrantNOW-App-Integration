<?php
/*
 * Plugin Name: BrantNOW App Integration
 * Plugin URI: http://radleysustaire.com/
 * Description: Adds a section for Real Estate/Open House, Garage Sales and Events - to be integrated into the BrantNOW mobile app.
 * Version: 1.0.0
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
		public $version = '1.0.0';
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
			
			include( $this->plugin_dir . '/includes/open-house.php' );
			include( $this->plugin_dir . '/includes/general.php' );
			
			if ( !class_exists('GeoQueryContext') ) {
				include( $this->plugin_dir . '/assets/geo-query/geo-query.php' );
			}
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

// Create our plugin object, accessible via a global variable.
global $BrantNOW;
$BrantNOW = new BrantNOW();