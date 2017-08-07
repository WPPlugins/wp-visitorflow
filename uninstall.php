<?php
    if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();


	delete_option( 'wp_visitorflow' );

	// For site options in multisite
	delete_site_option( 'wp_visitorflow' ); 

	delete_option( 'wp_visitorflow_plugin_version' );

	// For site options in multisite
	delete_site_option( 'wp_visitorflow_plugin_version' ); 

	//drop tables
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_visits" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_pages" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_flow" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_meta" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_aggregation" );
?>