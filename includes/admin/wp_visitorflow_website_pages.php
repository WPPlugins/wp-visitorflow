<?php
	// Set queries in Timeframe Page
	$timeframePage->setQueries(array( 'page' => 'wpvf_mode_website',
									  'tab' => 'pages') );								   

	// Print Timeframe Menu
	$timeframePage->printTimeframeMenu( $WP_VisitorFlow->get_setting('db-startdatetime') );

	if ($WP_VisitorFlow->get_user_setting('datetimestart') !=  $timeframePage->getTimeframeStart() ) {
		$WP_VisitorFlow->set_user_setting('datetimestart', $timeframePage->getTimeframeStart(), 0);
	}
	if ($WP_VisitorFlow->get_user_setting('datetimestop')  !=  $timeframePage->getTimeframeStop() ) {
		$WP_VisitorFlow->set_user_setting('datetimestop',  $timeframePage->getTimeframeStop(), 0);
	}
	$datetimestart = $timeframePage->getTimeframeStart();
	$datetimestop = $timeframePage->getTimeframeStop();
	
	// Print Tab Menu
	$timeframePage->printTabsMenu();
	
?>
	<div style="clear:both;"></div>
	<br />
<?php

	// Get existing pages
	$page_title = array();
	$post_id = array();
	$results = $db->get_results("SELECT id, f_post_id, title FROM $pages_table;");
	foreach ($results as $result) {
		$page_title[$result->id] = $result->title;
		$post_id[$result->id] = $result->f_post_id;
	}
	
	// Get aggregated data					
	$sql = $db->prepare("SELECT SUM($aggregation_table.value) AS hits,
							    $aggregation_table.type AS type
						 FROM $aggregation_table
						 WHERE type LIKE '%s' AND date>='%s' AND date<='%s'
						 GROUP BY type
						 ORDER BY hits DESC;",
						 'view%', $datetimestart, $datetimestop
						);
					
	$results = $db->get_results( $sql );

	if (count($results) == 0) {
?>		
		<div class="wpvf_warning">
			<p><?php echo __('No data found in the selected timeframe.', 'wp_visitorflow'); ?></p>
		</div><br />
<?php
		return;
	}

?>

<?php

	$count = 1;
	$max_plot_count = 15;
	$top_pages = array();
	$table_html = '';
	foreach ($results as $res) {

		list ($foo, $page_id) = explode('-', $res->type);
		if ($page_id != 'all' && isset($page_title[$page_id]) && $count <= $hit_count ) {
			
			if ($count <= $max_plot_count) {
				array_push($top_pages, array('title' => $page_title[$page_id],
											   'id' => $page_id) );
			}			
		
			if ($count % 2 != 0) {
				$table_html .= '<tr>';
			}
			else {
				$table_html .= '<tr class="darker">';
			}
			$count++;	
				
			$table_html .= '<td class="right">' . $res->hits . '</td>';
			
			$title = $page_title[$page_id];
			if ($post_id[$page_id] == -1) {
				$title = '<font color="red"><em>404 error:</em></font> ' . $page_title[$page_id];
			}
			
			$pagelink = $page_title[$page_id];
			if ($post_id[$page_id] > 0) {
				$pagelink = site_url() . '?p=' . $post_id[$page_id];
			}
			else {
				$pagelink = site_url() . $page_title[$page_id];;
			}
			
			$page_label = $title;
			if (strlen($title) > 60) {
				$title = substr($title, 0, 60) . '[...]';
			}

			$table_html .= '<td><a class="wpvf wpvfpage" href="'. $pagelink . '" title="' . $page_title[$page_id] . '">' . $title . '</a></td>';
			$table_html .= '<td><a class="wpvf wpvfflow" href="?page=wpvf_mode_singlepage&amp;select_page_id=' .  $page_id . '">' . __('Flow', 'wp_visitorflow') . '</a></td>';
			$table_html .= '</tr>'; 
		}
	}
	

	// Draw Pages Timeline Diagram

	$chart_data = array();

	//Get data from today (if necessary):
	$today = $WP_VisitorFlow->get_datetime('Y-m-d');
	$todays_data = array();
	if ($datetimestop == $today) {
		$todays_data = $WP_VisitorFlow->get_data( $today );
	}

	// Add total views to chart data
	$total_views_data = array();
	$results = $db->get_results($db->prepare("SELECT date, value AS count 
											  FROM $aggregation_table 
											  WHERE type='views-all'
											    AND date>='%s' AND date<='%s'
											  ORDER BY date ASC;",
											 $datetimestart, $datetimestop)
								);
	foreach ($results as $res) {
		$total_views_data[$res->date] = $res->count;
	}
	if ( isset($todays_data['views-all']) ) {
		$total_views_data[$today] = $todays_data['views-all'];
	}
	array_push($chart_data, array('label' => __('Sum of all page views', 'wp_visitorflow'),
								  'data' => $total_views_data) );


	// Add top 10 page views to chart data
	foreach ($top_pages as $page) {
		$results = $db->get_results($db->prepare("SELECT date, value AS count
												  FROM $aggregation_table 
												  WHERE type='views-%d'
												  AND (date BETWEEN '%s' AND date_add('%s', interval 1 day));",
												  $page['id'], $datetimestart, $datetimestop)
									);
		
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		
		if ( isset($todays_data['views-' . $page['id']]) ) {
			$data[$today] = $todays_data['views-' . $page['id']];
		}
		
		array_push($chart_data, array('label' => $page['title'],
									  'data' => $data) );
	}

	
	// Get hourly data
	$hourly_datetimestart = $datetimestart;
	$hourly_datetimestop =  $datetimestop;
	$hourly_startDateTime =  new DateTime($hourly_datetimestart);
	$hourly_stopDateTime =  new DateTime( $hourly_datetimestop );
	$flow_startDateTime = new DateTime( $WP_VisitorFlow->get_setting('flow-startdatetime') );
			
	if ($hourly_startDateTime > $flow_startDateTime ) {
		$hourly_datetimestart = $WP_VisitorFlow->get_setting('flow-startdatetime');
	}
	if ($hourly_stopDateTime < $flow_startDateTime ) {
		$hourly_datetimestop = $WP_VisitorFlow->get_setting('flow-startdatetime');
	}

	$hourly_data = array();
	foreach ($top_pages as $page) {
		$results = $db->get_results($db->prepare("SELECT COUNT(id) AS count,
														 HOUR(datetime) as hour
												  FROM $flow_table 
												  WHERE f_page_id='%s'
												  AND step>1
												  AND (datetime BETWEEN '%s' AND date_add('%s', interval 1 day))
												  GROUP BY HOUR(datetime);",
												  $page['id'], $hourly_datetimestart, $hourly_datetimestop)
									);
		
		$data = array();
		foreach ($results as $res) {
			$data[$res->hour] = $res->count;
		}
		
		for ($h = 0; $h <= 23; $h++) {
			if (! isset($data[$h])) { 
				$data[$h] = 0;
			}
		}
		
		ksort($data);

		array_push($hourly_data, array('label' => $page['title'],
									   'data' => $data) );
	}
	
	
	include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_jqplot_plot.php';
	include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_jqplot_filledarea.php';
	
	
?>
	<div class="twocol_left">

		<table class="wpvftable wpvftable-background">
		<tr>
			<th class="wpvftable-title" colspan="3"><?php echo sprintf(__('The %s Most Visited Pages within the Selected Timeframe', 'wp_visitorflow'), $hit_count); ?></th>
		</tr>
		<tr>
			<th><?php echo __('Page Views', 'wp_visitorflow'); ?></th>
			<th colspan="2"><?php echo __('Page', 'wp_visitorflow'); ?></th>
		</tr>		
		<?php echo $table_html; ?>
		</table>
		
	</div>
	<div class="twocol_right">
	
		<div class="wpvf-background">	
			<h2><?php echo __('Development of Top Page Views within the Selected Timeframe', 'wp_visitorflow'); ?></h2>
<?php	
	wp_visitorflow_plot( '',
						 $chart_data,
						 array('id' => 'chart_pages',
							   'width' => '100%', 
							   'height' => '500px')
						);

?>	
		</div>
		
		<br />
		<br />
		
		<div class="wpvf-background">	
			<h2><?php echo __('Top Page Views over Time of the Day', 'wp_visitorflow'); ?></h2>
			<em>(<?php echo sprintf(__('Data from %s to %s', 'wp_visitorflow'),		
									date_i18n( get_option( 'date_format' ), strtotime($hourly_datetimestart)),
									date_i18n( get_option( 'date_format' ), strtotime($hourly_datetimestop))
									); ?>)</em><br />
<?php	
	wp_visitorflow_filledarea( '',
						 $hourly_data,
						 array('id' => 'hourly_pages',
							   'width' => '100%', 
							   'height' => '500px')
						);

?>
		</div>
	</div>
<?php
