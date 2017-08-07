<?php
	if ( is_admin() ) {
	
		GLOBAL $wpdb;
		$wp_prefix = $wpdb->prefix;

		// Create tables (if not exist)
		$visits_table_name = $wp_prefix . 'visitorflow_visits';
		$sql_create_visits_table = ("CREATE TABLE $visits_table_name (
									 id int(11) NOT NULL AUTO_INCREMENT,
									 last_visit datetime NOT NULL,
									 agent_name varchar(255) DEFAULT NULL,
									 agent_version varchar(63) DEFAULT NULL,
									 agent_engine varchar(255) DEFAULT NULL,
									 os_name varchar(255) DEFAULT NULL,
									 os_version varchar(63) DEFAULT NULL,
									 os_platform varchar(255) DEFAULT NULL,
									 ip varchar(255) DEFAULT NULL,
									 PRIMARY KEY  (id),
									 KEY agent_name (agent_name),
									 KEY agent_version (agent_version),
									 KEY agent_engine (agent_engine),
									 KEY os_name (os_name),
									 KEY os_version (os_version),
									 KEY os_platform (os_platform)
									) CHARSET=utf8;");
		
		$pages_table_name = $wp_prefix . 'visitorflow_pages';
		$sql_create_pages_table  = ("CREATE TABLE $pages_table_name (
									 id int(11) NOT NULL AUTO_INCREMENT,
									 internal BOOL DEFAULT 0 NOT NULL,
									 f_post_id int(11) NOT NULL DEFAULT '0',
									 title varchar(255) NOT NULL,
									 PRIMARY KEY  (id)
									) CHARSET=utf8;");
		
		$flow_table_name = $wp_prefix . 'visitorflow_flow';
		$sql_create_flow_table   = ("CREATE TABLE $flow_table_name (
									 id int(11) NOT NULL AUTO_INCREMENT,
									 f_visit_id int(11),
									 step int(11) NOT NULL,
									 datetime datetime NOT NULL,
									 f_page_id int(11) NOT NULL,
									 PRIMARY KEY  (id),
									 KEY f_visit_id (f_visit_id),
									 KEY step (step),
									 KEY datetime (datetime),
									 KEY f_page_id (f_page_id)
									) CHARSET=utf8;");
		
		$meta_table_name = $wp_prefix . 'visitorflow_meta';
		$sql_create_meta_table  = ("CREATE TABLE $meta_table_name (
									id int(11) NOT NULL AUTO_INCREMENT,
									datetime datetime NOT NULL,
									type varchar(31) NOT NULL,
									label varchar(255) NOT NULL,
									value varchar(1023) NOT NULL,
									PRIMARY KEY  (id),
									KEY type (type),
									KEY label (label)
									) CHARSET=utf8;");

		$aggregation_table_name = $wp_prefix . 'visitorflow_aggregation';
		$sql_create_aggregation_table  = ("CREATE TABLE $aggregation_table_name (
										   id int(11) NOT NULL AUTO_INCREMENT,
										   type varchar(31) NOT NULL,
										   date date NOT NULL,
										   value int(11) NOT NULL,
										   PRIMARY KEY  (id),
										   KEY type (type),
										   UNIQUE KEY typedate (type,date)
										   ) CHARSET=utf8;");

		// Include the dbDelta function from WP:
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
		// Create/Alter tables:
		dbDelta($sql_create_visits_table);							
		dbDelta($sql_create_pages_table);							
		dbDelta($sql_create_flow_table);	
		dbDelta($sql_create_meta_table);	
		dbDelta($sql_create_aggregation_table);	
		
		// Set WP VisitorFlow version
		update_option('wp_visitorflow_plugin_version', WP_VISITORFLOW_VERSION);
	
	}