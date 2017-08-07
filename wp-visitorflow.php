<?php
/*
Plugin Name: WP VisitorFlow
Plugin URI: https://www.datacodedesign.de/index.php/wp-visitorflow/
Description: Detailed web analytics and visualization of your website's visitor flow
Version: 1.4
Author: Onno Gabriel/DataCodeDesign
Author URI: http://www.datacodedesign.de
License: GPL2
Text Domain: wp_visitorflow
*/

/*
 * Copyright 2017 Onno Gabriel (DataCodeDesign)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */
 
// Global constants.
define('WP_VISITORFLOW_VERSION', '1.4');
define('WP_VISITORFLOW_REQUIRED_PHP_VERSION', '5.3.0');

// Set the default timezone.
if ( get_option('timezone_string') ) {
	date_default_timezone_set( get_option('timezone_string') );
}

// Check installed PHP version.
if ( ! version_compare( phpversion(), WP_VISITORFLOW_REQUIRED_PHP_VERSION, ">=" ) ) { 
	// Show warning message inside the dashboard
	if ( is_admin() ) {
		function wp_visitorflow_php_notice() {      
			?>
			<div class="error below-h2"><p>
				<?php printf(__('The WP VisitorFlow plugin requires at least PHP version %s, but installed is version %s.'), 
							 WP_VISITORFLOW_REQUIRED_PHP_VERSION, PHP_VERSION); ?>
			</p></div>
			<?php
		}
		add_action('admin_notices', 'wp_visitorflow_php_notice');
	}
	return;  
}

// Include and instantiate the WP VisitorFlow class 
include_once dirname( __FILE__ ) . '/includes/classes/wp_visitorflow.class.php';
$WP_VisitorFlow = WP_VisitorFlow::getInstance();

// Check for fresh installation/updates
$WP_VisitorFlow_version = get_option('wp_visitorflow_plugin_version');
if ( $WP_VisitorFlow_version != WP_VISITORFLOW_VERSION ) {	
	include_once dirname( __FILE__ ) . '/includes/functions/wp-visitorflow_install.php';
	// Call post_update function is case of performed plugin update
	$WP_VisitorFlow->post_update( $WP_VisitorFlow_version, get_option('wp_visitorflow_plugin_version') );
}

// Check if update finished correctly
if ( get_option('wp_visitorflow_plugin_version') != WP_VISITORFLOW_VERSION ) {	
	// Show warning message inside the dashboard
	if ( is_admin() ) {
		function wp_visitorflow_error_notice() {      
			?>
			<div class="error below-h2"><p>
				<?php echo __('An error occured during installation/update of the WP VisitorFlow plugin.'); ?>
			</p></div>
			<?php
		}
		add_action('admin_notices', 'wp_visitorflow_error_notice');
	}
	return;  
}


// Add the action hook at the end of the page delivery
add_action('shutdown', 'wp_visitorflow_action');
function wp_visitorflow_action() {
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();

	// Record visit
	if ($WP_VisitorFlow->get_setting('record_visitorflow') == TRUE) {
		$WP_VisitorFlow->record_Visit();
	}
	
	// Trigger data aggregation
	$WP_VisitorFlow->data_aggregation();

	// Trigger data clean-up
	if ($WP_VisitorFlow->get_setting('last_dbclean_date') != date("Y-m-d")) {
		include_once dirname( __FILE__ ) . '/includes/functions/wp_visitorflow_functions.php';
		wp_visitorflow_db_cleanup();
	}

}


// REST API
add_action( 'rest_api_init', 'wp_visitorflow_register_rest_routes' );
function wp_visitorflow_register_rest_routes() {
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();
	if ( $WP_VisitorFlow->get_setting('enable_app_access') == TRUE ) {
		include_once dirname( __FILE__ ) . '/includes/functions/wp_visitorflow_rest_api.php';
	}
}
 

// Admin panel functions
if ( is_admin() ) {
	include_once dirname( __FILE__ ) . '/includes/functions/wp_visitorflow_functions.php';
	include_once dirname( __FILE__ ) . '/includes/admin/wp_visitorflow_admin.php';
}
