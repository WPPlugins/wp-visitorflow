<?php
	
	/**
	 * Update DB info
	 *
	 * Updates the 'db-info' setting, which includes information on the database tables, which is used on several
	 * places in the data analysis.
	 **/
	function wp_visitorflow_update_db_info($force = FALSE) {
		$WP_VisitorFlow = WP_VisitorFlow::getInstance();

		// Return, if last update was less than 60 seconds ago (to reduce database access9
		if (! $force && $WP_VisitorFlow->get_setting('db-info-timestamp') > time() - 60) { 
			return;
		}
	
		// Short variables for database object and table names from visitorflow class
		$db = $WP_VisitorFlow->get_DB();
		$visits_table = $WP_VisitorFlow->get_table_name('visits');
		$flow_table = $WP_VisitorFlow->get_table_name('flow');
		$pages_table = $WP_VisitorFlow->get_table_name('pages');
		$meta_table = $WP_VisitorFlow->get_table_name('meta');
		$aggregate_table  = $WP_VisitorFlow->get_table_name('aggregation');
		
		$datetime_now = $WP_VisitorFlow->get_datetime();
		$date_now = $WP_VisitorFlow->get_datetime('Y-m-d');;

		// Calculate the minutes since flow startdatetime
		$datetime_diff = date_diff( date_create( $WP_VisitorFlow->get_setting('flow-startdatetime') ), 
									date_create( $datetime_now )	
								  );
		$minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$perdayfactor = $minutes_run > 0 ? (24*60 / $minutes_run) : 1;

		// Calculate the minutes since db startdatetime
		$datetime_diff = date_diff( date_create( $WP_VisitorFlow->get_setting('db-startdatetime') ), 
									date_create( $datetime_now )	
								  );
		$db_minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$db_perdayfactor = $db_minutes_run > 0 ? (24*60 / $db_minutes_run) : 1;

		// Calculate the minutes since counters startdatetime
		$datetime_diff = date_diff( date_create( $WP_VisitorFlow->get_setting('counters-startdatetime') ), 
									date_create( $datetime_now )	
								  );
		$counters_minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$counters_perdayfactor = $counters_minutes_run > 0 ? (24*60 / $counters_minutes_run) : 1;
	
		// Get counts from DB tables
		$visits_count     = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits';");
		$visits_14d_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 14 day);");
		$visits_7d_count  = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 7 day);");					   
		$visits_24h_count = $db->get_var("SELECT COUNT(*) FROM $flow_table 
										   WHERE step='2'
										   AND date_add(datetime, interval 24 hour)>'$datetime_now';");
		$visits_14d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 28 day)
										   AND date<subdate('$date_now', interval 14 day);");
		$visits_7d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 14 day)
										   AND date<subdate('$date_now', interval 7 day);");
		$visits_24h_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										   WHERE type='visits'
										   AND date=subdate('$date_now', interval 7 day);");
	
		$pages = $db->get_row("SELECT COUNT(*) AS count_total,
											SUM(internal) AS count_internal
									 FROM   $pages_table
									 WHERE  id>='3';" );
		
		
		$hits_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
								    WHERE type='views-all';" ); 
		$hits_14d_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										WHERE type='views-all'
										AND date>=subdate('$date_now', interval 14 day);");
		$hits_7d_count  = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										WHERE type='views-all'
										AND date>=subdate('$date_now', interval 7 day);");
		$hits_24h_count = $db->get_var("SELECT COUNT(*) FROM $flow_table 
										WHERE step>'1'
										AND date_add(datetime, interval 24 hour)>'$datetime_now';");
		$hits_14d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										 WHERE type='views-all'
										 AND date>=subdate('$date_now', interval 28 day)
										 AND date<subdate('$date_now', interval 14 day);");
		$hits_7d_before  = $db->get_var("SELECT SUM(value) FROM $aggregate_table 
										 WHERE type='views-all'
										 AND date>=subdate('$date_now', interval 14 day)
										 AND date<subdate('$date_now', interval 7 day);");
		$hits_24h_before = $db->get_var( "SELECT COUNT(*) FROM $flow_table 
												 WHERE step>'1'
												 AND date_add(datetime, interval 192 hour)>'$datetime_now'
												 AND date_add(datetime, interval 168 hour)<='$datetime_now';");
		
		$meta_useragents_count = $db->get_var( "SELECT COUNT(*) FROM $meta_table WHERE type='useragent';" );
		
		$bots_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count bot';" ); 
		$uastring_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count uastring';" );
		$pagestring_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count pagestring';" );
		$exclusions_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count exclusion';" );
		
		// Set DB info
		$db_info = array('last_update_timestamp' => $WP_VisitorFlow->get_setting('db-info-timestamp'),
						 'minutes_run' => $minutes_run,
						 'db_minutes_run' => $db_minutes_run,
						 'perdayfactor' => $perdayfactor,
						 'db_perdayfactor' => $db_perdayfactor,
						 'visits_24h_count' => $visits_24h_count,
						 'visits_7d_count' => $visits_7d_count,
						 'visits_14d_count' => $visits_14d_count,
						 'visits_24h_before' => $visits_24h_before,
						 'visits_7d_before' => $visits_7d_before,
						 'visits_14d_before' => $visits_14d_before,
						 'visits_count' => $visits_count,
						 'pages_count' => $pages->count_total, 			
						 'pages_internal_count' => $pages->count_internal,
						 'hits_24h_count' => $hits_24h_count,
						 'hits_7d_count' => $hits_7d_count,
						 'hits_14d_count' => $hits_14d_count,
						 'hits_24h_before' => $hits_24h_before,
						 'hits_7d_before' => $hits_7d_before,
						 'hits_14d_before' => $hits_14d_before,
						 'hits_count' => $hits_count,
						 'meta_useragents_count' => $meta_useragents_count,
						 'bots_count' => $bots_count,
						 'counters_perdayfactor' => $counters_perdayfactor,
						 'exclusions_count' => $bots_count +  $uastring_count + $pagestring_count + $exclusions_count,
						);
		$WP_VisitorFlow->set_setting('db_info', $db_info, 1);
		$WP_VisitorFlow->set_setting('db-info-timestamp', time() );
	}

	
	/**
	 * Clean-up database
	 *
	 * Deletes all rows in the visits, pages, flow and meta table older then the 'flowdata_storage_time' (in days) setting
	 * set by the user.
	 * Returns a messsage sting with the amount of deleted data rows.
	 **/
	function wp_visitorflow_db_cleanup($clean_date = FALSE) {
		
		$WP_VisitorFlow = WP_VisitorFlow::getInstance();	
		
		$db = $WP_VisitorFlow->get_DB();
	
		$visits_table = $WP_VisitorFlow->get_table_name('visits');
		$pages_table   = $WP_VisitorFlow->get_table_name('pages');
		$flow_table   = $WP_VisitorFlow->get_table_name('flow');
		$meta_table   = $WP_VisitorFlow->get_table_name('meta');

		if (! $clean_date) { 
			$clean_date = new DateTime( $WP_VisitorFlow->get_datetime('Y-m-d') );
			$clean_date->modify('-' . $WP_VisitorFlow->get_setting('flowdata_storage_time') . ' day');
		}
		
		$clean_data_string = $clean_date->format('Y-m-d');
		$message = '';
		
		// Clear "Flow" table
		$result = $db->query( $db->prepare("DELETE FROM $flow_table WHERE datetime<'%s';", $clean_data_string) );
		if ($result) {
			$message = sprintf(__('%s page hits older than %s cleaned.', 'wp_visitorflow'), $result, $clean_data_string) . '<br />';
		}
		
		// Clear "Pages" table
		$result = $db->query("DELETE FROM $pages_table 
								WHERE NOT EXISTS (
									SELECT id
									FROM $flow_table
									WHERE $flow_table.f_page_id=$pages_table.id
								)
								AND id>'3';"
							   );
		if ($result) {
			$message .= sprintf(__('%s pages older than %s cleaned.', 'wp_visitorflow'), $result, $clean_data_string) . '<br />';
		}
		
		// Clear "Visits" table
		$result = $db->query("DELETE FROM $visits_table 
								WHERE NOT EXISTS (
									SELECT id
									FROM $flow_table
									WHERE $flow_table.f_visit_id=$visits_table.id
								)"
							   );
		if ($result) {
			$message .= sprintf(__('%s visits older than %s cleaned.', 'wp_visitorflow'), $result, $clean_data_string) . '<br />';
		}
		
		// Clear "Meta" table
		$result = $db->query( $db->prepare("DELETE FROM $meta_table WHERE datetime<'%s';", $clean_data_string) );
		if ($result) {
			$message .= sprintf(__('%s meta entries older than %s cleaned.', 'wp_visitorflow'), $result, $clean_data_string) . '<br />';
		}
		
		if ($message) {
			$WP_VisitorFlow->store_Meta('log', 'cleanup', $message);
		}

		$message .= $WP_VisitorFlow->set_startdatetimes();
		
		// Set last db clean-up to current date
		$WP_VisitorFlow->set_setting('last_dbclean_date', date("Y-m-d"), 1);

		return $message;
	}
	
	
	/**
	 * Get time difference in words
	 **/
	function wp_visitorflow_getNiceTimeDifferenz($datetime1, $datetime2) {
		$date1 = new DateTime($datetime1);
		$date2 = new DateTime($datetime2);
		
		$date_diff = $date2->diff($date1);
		$years =$date_diff->format('%y');
		$months = $date_diff->format('%m');
		if ($months > 3) { return ( $years * 12 +  $months ) . ' ' . __('months', 'wp_visitorflow'); }
		$days = $date_diff->format('%a');
		if ($days > 1) { return number_format_i18n($days) . ' ' . __('days', 'wp_visitorflow'); }
		$hours = $date_diff->format('%h') + $days * 24;
		if ($hours > 1) { return $hours . ' ' . __('hours', 'wp_visitorflow'); }
		$mins = $hours * 60 + $date_diff->format('%i');
		return $mins . ' ' . __('minutes', 'wp_visitorflow'); 
	}
	
?>