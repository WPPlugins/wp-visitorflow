<?php

		$event_types = array('setstart'   => __('Reset DB start date/time', 'wp_visitorflow'),
							 'cleanup'    => __('DB clean-up', 'wp_visitorflow'),
							 'aggregat'   => __('Data aggregation', 'wp_visitorflow'),
							 'initpages'  => __('Data table initialization', 'wp_visitorflow'),
							 'newversion' => __('Plugin update', 'wp_visitorflow'),
							 'delvisit'   => __('Visit manually deleted', 'wp_visitorflow'),
							 );
		
		$results = $db->get_results( "SELECT *
									   FROM $meta_table 
									   WHERE type='log' 
									   ORDER BY datetime,id ASC
									   LIMIT 1000;" );								
		
		$table_data = array();
		
		foreach ($results as $res) {

			$event_name = $res->label;
			if (array_key_exists($event_name, $event_types)) { $event_name = $event_types[$event_name]; }
			
			$entry = array( 'datetime' => $res->datetime,
							'datetime_nice' => sprintf(__('%s ago', 'wp_visitorflow'), 
														wp_visitorflow_getNiceTimeDifferenz($res->datetime, $WP_VisitorFlow->get_datetime() ) 
												 ),
							'label'    => $event_name,
							'value'    => $res->value
						);

			array_push($table_data, $entry);
		}
		
		// Include and initialize table class
		include_once dirname( __FILE__ ) . '/../../includes/classes/wp_visitorflow-table.class.php';
		
		$columns = array('datetime' => __('Date/Time', 'wp_visitorflow'),
						 'datetime_nice' => '&nbsp;',
						 'label' => __('Event', 'wp_visitorflow'),
						 'value' => __('Message', 'wp_visitorflow'),
						 ); 
		$sortable_columns = array( 'datetime' => array('datetime', false),
								   'label' => array('label', false),					  
								  );
		
		$myTable = new Visitor_Table( $columns, $sortable_columns, $table_data);
		$myTable->prepare_items();

		// Show visits table
?>
		<h2>Logfile</h2>        
<?php
		$myTable->display(); 
	