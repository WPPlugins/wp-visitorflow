<?php
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();;		

	if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	// Calculate number of days since overall database start
	$data_startdate = new DateTime( $WP_VisitorFlow->get_setting('db-startdatetime') );
	$today = new DateTime();
	$date_diff = $today->diff($data_startdate);
	$max_days_back_db = $date_diff->format('%a');
	
	// Calculate number of days since flow database start
	$data_startdate = new DateTime( $WP_VisitorFlow->get_setting('flow-startdatetime') );
	$today = new DateTime();
	$date_diff = $today->diff($data_startdate);
	$max_days_back_flow = $date_diff->format('%a');
	
	$periods = array( array('days_back' => 0, 'label' => __('Today', 'wp_visitorflow'), 'start' => '+0', 'end' => '+0'),
					  array('days_back' => 1, 'label' => __('Yesterday', 'wp_visitorflow'), 'start' => '-1', 'end' => '-1'),
					  array('days_back' => 1, 'label' => __('Last 7 days', 'wp_visitorflow'), 'start' => '-7', 'end' => '+0'),
					  array('days_back' => 7, 'label' => __('Last 14 days', 'wp_visitorflow'), 'start' => '-14', 'end' => '+0'),
					  array('days_back' => 14, 'label' => __('Last 30 days', 'wp_visitorflow'), 'start' => '-30', 'end' => '+0'),
					  array('days_back' => 30, 'label' => __('Last 60 days', 'wp_visitorflow'), 'start' => '-60', 'end' => '+0'),
					  array('days_back' => 60, 'label' => __('Last 365 days', 'wp_visitorflow'), 'start' => '-365', 'end' => '+0')
					);
					
	echo '<ul>';

	echo '<li><strong>' . __('Visitor Flow', 'wp_visitorflow') . ':</strong> ';
	echo __('Show me the Visitor Flow for', 'wp_visitorflow') . ' ' ;
	for($i = 0; $i < count($periods); $i++) {
		$period = $periods[$i];
		if ($period['days_back'] < $max_days_back_flow) {
			if ($i > 0) { echo ' | '; }
			$link = htmlspecialchars('?page=wpvf_mode_website&tab=flow&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
			echo '<a class="wpvf" href="' . $link . '">';
			echo $period['label'];
			echo '</a>';
		}
	}
	echo '.</li>';

	echo '<li><strong>' . __('Referrers', 'wp_visitorflow') . ':</strong> ';
	echo __('Where are my visitors coming from? ', 'wp_visitorflow') . ' ' ;
	for($i = 0; $i < count($periods); $i++) {
		$period = $periods[$i];
		if ($period['days_back'] < $max_days_back_db) {
			if ($i > 0) { echo ' | '; }
			$link = htmlspecialchars('?page=wpvf_mode_website&tab=referrers&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
			echo '<a class="wpvf" href="' . $link . '">';
			echo $period['label'];
			echo '</a>';
		}
	}
	echo '.</li>';

	echo '<li><strong>' . __('Page Views', 'wp_visitorflow') . ':</strong> ';
	echo __('What are my most viewed posts or pages? ', 'wp_visitorflow') . ' ' ;
	for($i = 0; $i < count($periods); $i++) {
		$period = $periods[$i];
		if ($period['days_back'] < $max_days_back_db) {
			if ($i > 0) { echo ' | '; }
			$link = htmlspecialchars('?page=wpvf_mode_website&tab=pages&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
			echo '<a class="wpvf" href="' . $link . '">';
			echo $period['label'];
			echo '</a>';
		}
	}
	echo '.</li>';
	
	echo '</ul>';

