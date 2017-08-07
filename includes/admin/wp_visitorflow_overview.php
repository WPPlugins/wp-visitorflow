<?php

	/**
	 * Content of the "Overview" Page (in form of WordPress Metaboxes)
	 **/
	function wp_visitorflow_overview_content() {
		
		wp_visitorflow_update_db_info();

		add_meta_box('quickanswers',  __('Quick Answers to Routine Inquiries', 'wp_visitorflow'),   'wp_visitorflow_overview_quickanswers', get_current_screen(), 'normal','high');
		add_meta_box('shortsummary',  __('Short Summary', 'wp_visitorflow'),   'wp_visitorflow_overview_summary', get_current_screen(), 'normal','high');
		add_meta_box('lastmonthplot', __('Page Views and Visits in the Last 30 Days', 'wp_visitorflow'), 'wp_visitorflow_overview_lastmonthplot', get_current_screen(), 'normal','high');
		add_meta_box('latestkeywords', __('Latest Search Keys Words', 'wp_visitorflow'), 'wp_visitorflow_overview_latestkeywords', get_current_screen(), 'normal','low');
		add_meta_box('botcounts', __('Recorded Bot Visits', 'wp_visitorflow'), 'wp_visitorflow_overview_botcounts', get_current_screen(), 'side','high');
		add_meta_box('exclusioncounts', __('Excluded Page Views', 'wp_visitorflow'), 'wp_visitorflow_overview_exclusioncounts', get_current_screen(), 'side','low');
		add_meta_box('dbinfo', __('Database Summary', 'wp_visitorflow'), 'wp_visitorflow_overview_dbinfo', get_current_screen(), 'side','low');
		add_meta_box('mostfrequentpages', __('Most Visited Pages last 7 Days', 'wp_visitorflow'), 'wp_visitorflow_overview_mostfrequentpages', get_current_screen(), 'normal','low');
		add_meta_box('latestuastrings', __('Latest HTTP User Agent Strings', 'wp_visitorflow'), 'wp_visitorflow_overview_uastrings', get_current_screen(), 'normal','low');
			
	}
	
	
	/**
	 * Metabox content: Latest Search Keywords
	 **/
	function wp_visitorflow_overview_quickanswers() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getQuickAnswers.php';
	}
	
	/**
	 * Metabox content: Latest Search Keywords
	 **/
	function wp_visitorflow_overview_latestkeywords() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getSEKeywordsTable.php';
	}
	
	/**
	 * Metabox content: Crawlers/Bots/Spiders Counts
	 **/
	function wp_visitorflow_overview_botcounts() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getBotsTable.php';
	}
	
	/**
	 * Metabox content: Page View Exclusions
	 **/
	function wp_visitorflow_overview_exclusioncounts() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getExlusionsTable.php';
	}

	/**
	 * Metabox content: Database Status 
	 */
	function wp_visitorflow_overview_dbinfo() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getDBOverviewTable.php';
	}
	
	/**
	 * Metabox content: Latest Recorded User Agent Strings
	 **/
	function wp_visitorflow_overview_uastrings() {
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getLatestUAStringsTable.php';
	}


	/**
	 * Metabox content: Plot with Visits and Page Views in the Last 30 Days
	 **/
	function wp_visitorflow_overview_lastmonthplot() {
		$WP_VisitorFlow = WP_VisitorFlow::getInstance();		

		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = $WP_VisitorFlow->get_DB();
		$aggregation_table  = $WP_VisitorFlow->get_table_name('aggregation');

		//Get data for today
		$today = $WP_VisitorFlow->get_datetime('Y-m-d');
		$todays_data = $WP_VisitorFlow->get_data( $today );

		// Draw Visits/Page Views Timeline Diagram
		$chart_data = array();
		$results = $db->get_results($db->prepare("SELECT date, value AS count
												  FROM $aggregation_table 
												  WHERE type='visits'
												  AND date>=subdate('%s', interval 30 day)
												  ORDER BY date ASC;",
												  $WP_VisitorFlow->get_datetime() )
									);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['visits']) ) {
			$data[$today] = $todays_data['visits'];
		}
		array_push($chart_data, array('label' => __('Visits', 'wp_visitorflow'),
									  'data' => $data) );

		$results = $db->get_results($db->prepare("SELECT date, value AS count 
												  FROM $aggregation_table 
												  WHERE type='views-all'
												  AND date>=subdate('%s', interval 30 day)
												  ORDER BY date ASC;",
												  $WP_VisitorFlow->get_datetime() )
									);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['views-all']) ) {
			$data[$today] = $todays_data['views-all'];
		}
		array_push($chart_data, array('label' => __('Page views', 'wp_visitorflow'),
									  'data' => $data) );
		
		
		include_once dirname( __FILE__ ) . '/../functions/wp_visitorflow_jqplot_plot.php';
			
		echo '<br />';
		wp_visitorflow_plot( '',
							 $chart_data,
							 array('id' => 'chart_overview',
								   'width' => '98%', 
								   'height' => '300px')
							);
	}	
		
	/**
	 * Metabox content: Most Frequented Pages within the Last 7 Days
	 **/
	function wp_visitorflow_overview_mostfrequentpages() {
		$WP_VisitorFlow = WP_VisitorFlow::getInstance();	

		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = $WP_VisitorFlow->get_DB();
		$flow_table   		= $WP_VisitorFlow->get_table_name('flow');
		$pages_table		= $WP_VisitorFlow->get_table_name('pages');

		$datetimestart = date( 'Y-m-d', strtotime( '-7 days') );
		$datetimestop =  date( 'Y-m-d' );
		$hit_count = 20;
	
		$min_post_id = 0;
		if (! $WP_VisitorFlow->get_setting('exclude_404') ) { $min_post_id = -1; }
		
		$sql = $db->prepare("SELECT COUNT($flow_table.id) AS pcount,
								$pages_table.id AS page_id,
								$pages_table.title AS title,
								$pages_table.f_post_id AS post_id,
								MAX($flow_table.datetime) AS last_datetime
							 FROM $flow_table
							 JOIN $pages_table
							 ON $pages_table.id=$flow_table.f_page_id
							 WHERE $flow_table.datetime>='%s' AND $flow_table.datetime<=date_add('%s', interval 1 day)
							   AND $flow_table.step>'1'
							   AND $pages_table.f_post_id>='$min_post_id'
							 GROUP BY $flow_table.f_page_id
							 ORDER BY pcount DESC LIMIT %d;",
							 $datetimestart, $datetimestop, $hit_count
							);

		$results = $db->get_results( $sql );
?>  
		<table class="wpvftable">
		<tr>
			<th><?php echo __('Page Views', 'wp_visitorflow'); ?></th>
			<th colspan="2"><?php echo __('Page', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Last Visit', 'wp_visitorflow'); ?></th>
		</tr>
<?php
		$step = 0;
		foreach ($results as $res) {

			if ($step % 2 == 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$step++;			
			
			echo '<td class="right">' . $res->pcount . '</td>';
			
			$title = $res->title;
			if ($res->post_id == -1) {
				$title = '<font color="red"><em>404 error:</em></font> ' . $title;
			}
			
			$pagelink = $res->title;
			if ($res->post_id > 0) {
				$pagelink = site_url() . '?p=' . $res->post_id;
			}
			else {
				$pagelink = site_url() . $res->title;;
			}
			
			echo '<td><a class="wpvf wpvfpage" href="'. $pagelink . '">' .  $title . '</a></td>';
			echo '<td><a class="wpvf wpvfflow" href="?page=wpvf_mode_singlepage&amp;select_page_id=' .  $res->page_id . '">' . __('Flow', 'wp_visitorflow') . '</a></td>';
			echo '<td>' . sprintf(__('%s ago', 'wp_visitorflow'), 
														wp_visitorflow_getNiceTimeDifferenz($res->last_datetime, $WP_VisitorFlow->get_datetime() ) 
													   ) . '</td>';
			echo '</tr>'; 

		}
?>
		</table>
		<br />
<?php	
	}
	
	
	
	/**
	 * Metabox content: WP VisitorFlow Short Summary (Visits, Page Views etc.)
	 **/
	function wp_visitorflow_overview_summary() {

		$WP_VisitorFlow = WP_VisitorFlow::getInstance();	
	
		$db_info = $WP_VisitorFlow->get_setting('db_info');

		$screen = get_current_screen();
		
		// Print Info Boxes
		$info_box_class = '';
		$title_class = 'wpvf_info_title';
		$arrow_class = 'wpvf_info_arrow';
		if (wp_is_mobile() || $screen->id == "dashboard" )  { 
			$info_box_class .= ' wpvf_info_mobile'; 
			$title_class = 'wpvf_info_title_mobile';
			$arrow_class = 'wpvf_info_arrow_mobile';
		}

		$counters = array( array('label' => __('Visitors', 'wp_visitorflow'),
								 'label_period' => __('last 24h', 'wp_visitorflow'),
								 'db_info_column' => 'visits_24h_count',
								 'db_info_compare' => 'visits_24h_before',
								 'db_info_average' => 'visits_count',
								 'hours' => 24,
								 'color' => 'blue'),
						   array('label' => __('Visitors', 'wp_visitorflow'),
								 'label_period' => __('last 7 days', 'wp_visitorflow'),
								 'db_info_column' => 'visits_7d_count',
								 'db_info_compare' => 'visits_7d_before',
								 'db_info_average' => 'visits_count',
								 'hours' => 24*7,
								 'color' => 'darkblue'),
						   array('label' => __('Page views', 'wp_visitorflow'),
								 'label_period' => __('last 24h', 'wp_visitorflow'),
								 'db_info_column' => 'hits_24h_count',
								 'db_info_compare' => 'hits_24h_before',
								 'db_info_average' => 'hits_count',
								 'hours' => 24,
								 'color' => 'pink'),
						   array('label' => __('Page views', 'wp_visitorflow'),
								 'label_period' => __('last 7 days', 'wp_visitorflow'),
								 'db_info_column' => 'hits_7d_count',
								 'db_info_compare' => 'hits_7d_before',
								 'db_info_average' => 'hits_count',
								 'hours' => 24*7,
								 'color' => 'darkpink'),
						);
					
		foreach ($counters as $counter) {
			$count = $db_info[ $counter['db_info_column'] ];
			if (! $count) { $count = 0; }
		
			$arrow_html = '';
			$average_html = '';
			
			$minutes_run = $db_info['db_minutes_run'];
			if ($counter['hours'] <= 24) { $minutes_run = $db_info['minutes_run']; }
			
			if ($minutes_run > 60*$counter['hours']) { // if db (re)start was later than the hour interval
				$compare = $db_info[ $counter['db_info_compare'] ];

				if ($compare > 0) {
					$arrow = '0';
					$change = 100 * ($count - $compare) / $compare;
					$changes = array(5, 10, 20, 50, 100, 200);
					for ($i = 0; $i < count($changes); $i++) {
						if ( $change > $changes[$i] ) {
							$arrow = ($i+1) . 'p';
						}
						elseif ( - $change > $changes[$i] ) {
							$arrow = ($i+1) . 'm';
						}
					}
					$arrow_html = '<img class="' . $arrow_class . '" src="' . plugin_dir_url( __FILE__ ) . '../../assets/images/Arrow-' . $arrow . '.png" alt="arrow" />';
					$average = $db_info[ $counter['db_info_average'] ] * $counter['hours'] / ($db_info['db_minutes_run'] / 60);
					$average_html = '(&#216; ' . round($average) . ')';
				}
			}
		
?>
			<div class="wpvf_info wpvf_info_<?php echo $counter['color'] . $info_box_class; ?>">
				<?php echo $arrow_html; ?>
				<span class="<?php echo $title_class; ?>"><?php echo number_format_i18n($count); ?></span><br />
				<?php echo  $counter['label']; ?><br />
				<?php echo  $counter['label_period']; ?><br />
				<?php echo $average_html; ?><br />
			</div>
<?php		
		}
	
?>
		<div style="clear:both;"></div><br />

		<div class="wpvf_info wpvf_info_green<?php echo $info_box_class; ?>">
			<span class="<?php echo $title_class; ?>"><?php echo  str_replace(" ", "&nbsp;", wp_visitorflow_getNiceTimeDifferenz($WP_VisitorFlow->get_setting('db-startdatetime'), $WP_VisitorFlow->get_datetime() ) ); ?></span><br />
			<?php echo __('since DB start on', 'wp_visitorflow'); ?><br /> 
			<?php echo date_i18n( get_option( 'date_format' ), strtotime($WP_VisitorFlow->get_setting('db-startdatetime'))); ?>

		</div>

		<div class="wpvf_info wpvf_info_darkred<?php echo $info_box_class; ?>">
			<span class="<?php echo $title_class; ?>"><?php echo number_format_i18n($db_info['bots_count']); ?></span><br />
			<?php echo __('Recorded bots visits', 'wp_visitorflow'); ?><br />
			&#216; <?php echo sprintf( __('%s per day', 'wp_visitorflow'),
			 number_format_i18n($db_info['bots_count'] * $db_info['counters_perdayfactor']) ); ?>
		</div>
<?php		

	}
