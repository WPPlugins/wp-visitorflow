<?php
	// Set queries in Timeframe Page
	$orderby = 0;
	$order = 0;
	if (array_key_exists('orderby', $_POST)) {									  
		$orderby = htmlspecialchars( stripslashes( $_POST['orderby'] ) );
		$order   = htmlspecialchars( stripslashes( $_POST['order'] ) );
	}
	elseif (array_key_exists('orderby', $_GET)) {									  
		$orderby = htmlspecialchars( stripslashes( $_GET['orderby'] ) );
		$order   = htmlspecialchars( stripslashes( $_GET['order'] ) );
	}
	
	$queries = array();
	$queries['page'] = 'wpvf_mode_website';
	$queries['tab'] = 'visitors';
	if ($order)   { $queries['order'] = $order; }
	if ($orderby) {	$queries['orderby'] = $orderby; }
		
	$timeframePage->setQueries($queries );								   

	// Print Timeframe Menu
	$timeframePage->printTimeframeMenu( $WP_VisitorFlow->get_setting('flow-startdatetime') );
	
	// Print Tab Menu
	$timeframePage->printTabsMenu();
	
?>
	<div style="clear:both;"></div>
	<br />
<?php

	// Flow of a single visitor requested?
	if ( array_key_exists('visit_id', $_GET) ) {
		include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_print_flow.php';
		return;
	}

    // Timeframe interval in SQL query
	$sql_where = "$flow_table.datetime>='%s' AND $flow_table.datetime<=date_add('%s', interval 1 day)";
	$sql_array = array($datetimestart, $datetimestop);

	// Trying to delete a visitor?
	if ( array_key_exists('del_visit_id', $_GET) ) {

		$del_id = htmlspecialchars( stripslashes( $_GET['del_visit_id'] ) );
	
		echo "<br />\n";
		if (! array_key_exists('confirm_del', $_POST)) {

			$sql_where = "f_visit_id='%d'";
			$sql_array = array( $del_id );
?>
			<div class="wpvf_warning">
				<p><?php echo __('Do you really want to delete this visit from database?', 'wp_visitorflow'); ?><p>
				<form id="wpvf_del_visit" method="post">
<?php	
			foreach($queries as $name => $value) {
				echo '<input type="hidden" name="'. $name . '" value="' . $value . '" />';
			}
			echo '<input type="hidden" name="confirm_del" value="1" />';
			echo '<input type="hidden" name="del_visit_id" value="' . $del_id . '" />';
?>
				<?php submit_button(__('Yes, delete it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm_reset'); ?>
				</form>
				<form action="?page=wpvf_mode_website&amp;tab=visitors" id="wpvf_cancel" method="post">
				<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
				</form>
			</div>
<?php						
		}
		else {
			$date_results = $db->get_results( $db->prepare("SELECT datetime FROM $flow_table WHERE f_visit_id='%s';", $del_id) );
			// Delete from "visits" table
			$result = $db->query( $db->prepare("DELETE FROM $visits_table WHERE id='%d';", $del_id) );
			$message = sprintf(__('%s visit deleted.', 'wp_visitorflow'), $result) . '<br />';
			// Delete from "flow" table
			$result = $db->query( $db->prepare("DELETE FROM $flow_table WHERE f_visit_id='%s';", $del_id) );
			$message .= sprintf(__('%s page views deleted.', 'wp_visitorflow'), $result) . '<br />';
			echo '<p class="wpvf_message">' . $message . "</p>\n";
			$WP_VisitorFlow->store_Meta('log', 'delvisit', $message);

			// Trigger data aggregation for visitor's dates (plural, if around midnight)
			foreach($date_results as $result) {
				$update_date = new DateTime( $result->datetime );
				$WP_VisitorFlow->data_aggregation_perday( $update_date->format('Y-m-d') );
			}

		}
	}
	
	// Get visitor data from db
	$sql = $db->prepare("SELECT $flow_table.f_visit_id, 
								COUNT($visits_table.id) AS vcount,
								MAX($visits_table.last_visit) AS datetime,
								$visits_table.agent_name AS agent_name,
								$visits_table.agent_version AS agent_version,
								$visits_table.agent_engine AS agent_engine,
								$visits_table.os_name AS os_name,
								$visits_table.os_version AS os_version,
								$visits_table.os_platform AS os_platform,
								$visits_table.ip AS ip
						   FROM $flow_table 
						   JOIN $visits_table ON $visits_table.id=$flow_table.f_visit_id
						   WHERE $sql_where
						   GROUP BY f_visit_id;",
						   $sql_array);
	$results = $db->get_results( $sql );
									
	if (count($results) == 0) {
?>		
		<div class="wpvf_warning">
			<p><?php echo __('No data found in the selected timeframe.', 'wp_visitorflow'); ?></p>
		</div><br />
<?php
		return;
	}

	$agent_count = array();
	$engine_count = array();
	$os_count = array();
	$agent_os_count = array();
	
	$table_data = array();
	$query_string = "page=wpvf_mode_website&amp;tab=" . $timeframePage->get_current_tab() . "&amp;order=$order&amp;orderby=$orderby";

	$count = 1;
	foreach ($results as $res) {
		
		if (! array_key_exists($res->agent_name, $agent_count)) { $agent_count[$res->agent_name] = 0; }
		$agent_count[$res->agent_name]++;
		if (! array_key_exists($res->agent_engine, $engine_count)) { $engine_count[$res->agent_engine] = 0; }
		$engine_count[$res->agent_engine]++;
		if (! array_key_exists($res->os_name, $os_count)) { $os_count[$res->os_name] = 0; }
		$os_count[$res->os_name]++;
		$agent_os = $res->agent_name . '/' . $res->os_name;
		if (! array_key_exists($agent_os, $agent_os_count)) { $agent_os_count[$agent_os] = 0; }
		$agent_os_count[$agent_os]++;
		
		$vcount = 0;
		if (isset($res->vcount)) {
			$vcount = $res->vcount - 1; // Because two entries are created at first visit in table flows: referer and actual page
		}
		$link = '?' . $query_string . '&amp;visit_id=' . $res->f_visit_id;
		$agentlink = '<a href="' . $link . '">'. $res->agent_name . '</a>';

		$entry = array(
						'count'     => $vcount,
						'nice_datetime'	=> sprintf(__('%s ago', 'wp_visitorflow'), 
													wp_visitorflow_getNiceTimeDifferenz($res->datetime, $WP_VisitorFlow->get_datetime() ) 
												   ),
						'lastvisit' 	=> $res->datetime,
						'agent_name'    => $agentlink,
						'agent_version' => $res->agent_version,
						'agent_engine'  => $res->agent_engine,
						'os_name'  		=> $res->os_name,
						'os_version' 	=> $res->os_version,
						'os_platform'  	=> $res->os_platform,
						'ip'        	=> $res->ip,
						'action' 	 	=> '<a class="wpvf" href="?' . $query_string . '&amp;del_visit_id=' .  $res->f_visit_id . '">' . __('Delete', 'wp_visitorflow') . '</a>'
					);
		
		if (! preg_match('/\./',  $res->ip) ) { 
			$entry['ip'] = __('encrypted', 'wp_visitorflow');
		}
		array_push($table_data, $entry);
		
	}

	// Draw Pie Charts
	if (! array_key_exists('del_visit_id', $_GET) ) {

		include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_jqplot_piechart.php';

?>
	<div class="wpvf-background" style="height:500px;">		
	<h2><?php echo __('User Agents and Operation Systems Used within the Selected Timeframe', 'wp_visitorflow'); ?></h2>
<?php

		// User Agents Pie Chart
		$pie_data = array();
		foreach($agent_count as $label => $count) {
			if (! $label) { $label .= __('Unknown', 'wp_visitorflow'); }
			$pie_data[$label] = $count;
		}
		
		wp_visitorflow_piechart( __('User Agents', 'wp_visitorflow'),
								 $pie_data,
								 array('id' => 'pie_useragents',
									   'width' => '350px', 
									   'height' => '450px',
									   'legendcolumns' => 2,
									   'style' => 'float:left;')
								);
		
		// Agent Enginges Pie Chart
		$pie_data = array();
		foreach($engine_count as $label => $count) {
			if (! $label) { $label .= __('Unknown', 'wp_visitorflow'); }
			$pie_data[$label] = $count;
		}
		
		wp_visitorflow_piechart( __('Agent Engines', 'wp_visitorflow'),
								 $pie_data,
								 array('id' => 'pie_engines',
									   'width' => '350px', 
									   'height' => '450px',
									   'legendcolumns' => 2,
									   'style' => 'float:left;')
								);
		
		// Operation Systems Pie Chart
		$pie_data = array();
		foreach($os_count as $label => $count) {
			if (! $label) { $label .= __('Unknown', 'wp_visitorflow'); }
			$pie_data[$label] = $count;
		}
		
		wp_visitorflow_piechart( __('Operation Systems', 'wp_visitorflow'),
								 $pie_data,
								 array('id' => 'pie_oss',
									   'width' => '350px', 
									   'height' => '450px',
									   'legendcolumns' => 2,
									   'style' => 'float:left;')
								);
		
		// Agents/Operation Systems Pie Chart
		$pie_data = array();
		foreach($agent_os_count as $label => $count) {
			if (! $label) { $label .= __('Unknown', 'wp_visitorflow'); }
			$pie_data[$label] = $count;
		}
		
		wp_visitorflow_piechart( __('Agents/Operation Systems', 'wp_visitorflow'),
								 $pie_data,
								 array('id' => 'pie_agentoss',
									   'width' => '400px', 
									   'height' => '450px',
									   'legendcolumns' => 2,
									   'style' => 'float:left;')
								);

	}

	?>
	</div>

	<br />
<?php
				
	
	//Get data from today (if necessary):
	$today = $WP_VisitorFlow->get_datetime('Y-m-d');
	$todays_data = array();
	if ($datetimestop == $today) {
		$todays_data = $WP_VisitorFlow->get_data( $today );
	}
	
	// Get visitors from aggregation table
	$visits_data = array();
	$results = $db->get_results($db->prepare("SELECT date, value AS count 
											  FROM $aggregation_table 
											  WHERE type='visits'
											    AND date>='%s' AND date<='%s'
											  ORDER BY date ASC;",
											 $datetimestart, $datetimestop)
								);
	foreach ($results as $res) {
		$visits_data[$res->date] = $res->count;
	}
	if ( isset($todays_data['visits']) ) {
		$visits_data[$today] = $todays_data['visits'];
	}
	$chart_data = array();
	array_push($chart_data, array('label' => __('Visitors per day', 'wp_visitorflow'),
								  'data' => $visits_data) );


	// Draw visits diagram 
	include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_jqplot_plot.php';
	
?>
	<div class="wpvf-background">	
		<h2><?php echo __('Development of Visitors within the Selected Timeframe', 'wp_visitorflow'); ?></h2>
<?php	
	wp_visitorflow_plot( '',
						 $chart_data,
						 array('id' => 'chart_pages',
							   'width' => '98%', 
							   'height' => '500px')
						);

?>	
	</div>
	<br />
	<br />
	<div class="wpvf-background">	
		<h2><?php echo sprintf(	__('Visitors since %s (since %s)', 'wp_visitorflow'), 
								date_i18n( get_option( 'date_format' ), strtotime($WP_VisitorFlow->get_setting('flow-startdatetime'))),
								wp_visitorflow_getNiceTimeDifferenz($WP_VisitorFlow->get_setting('flow-startdatetime'), $WP_VisitorFlow->get_datetime() )
							); ?></h2>

<?php	


				
	// Draw table with all visitors in the selected timeframe
	include_once dirname( __FILE__ ) . '/../../includes/classes/wp_visitorflow-table.class.php';
	
	$columns = array('agent_name'    => __('Agent', 'wp_visitorflow'),
					 'agent_version' => __('Agent Version', 'wp_visitorflow'),
					 'agent_engine'  => __('Agent Engine', 'wp_visitorflow'),
					 'os_name'  	 => __('OS', 'wp_visitorflow'),
					 'os_version'  	 => __('OS Version', 'wp_visitorflow'),
					 'os_platform'   => __('OS Platform', 'wp_visitorflow'),
					 'ip'        	 => __('IP Address', 'wp_visitorflow'),
					 'count'     	 => __('Count', 'wp_visitorflow'),
					 'nice_datetime' => __('Last visit', 'wp_visitorflow'),
					 'lastvisit' 	 => __('Date/Time', 'wp_visitorflow'),
					 'action' 	 	 => __('Action', 'wp_visitorflow'),
					 ); 
	$sortable_columns = array( 'lastvisit' => array('lastvisit', false),
							   'count' => array('count', false),					  
							  );
	
	$myTable = new Visitor_Table( $columns, $sortable_columns, $table_data);
	$myTable->prepare_items();

	$myTable->display(); 
?>
	</div>