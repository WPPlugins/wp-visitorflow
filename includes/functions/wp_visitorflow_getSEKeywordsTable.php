<?php
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();	

	if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Get DB object and table names from visitorflow class
	$db = $WP_VisitorFlow->get_DB();
	$meta_table  = $WP_VisitorFlow->get_table_name('meta');
	$flow_table  = $WP_VisitorFlow->get_table_name('flow');
	$pages_table  = $WP_VisitorFlow->get_table_name('pages');
	
	$results = $db->get_results("SELECT $meta_table.label AS label, 
										$meta_table.datetime AS datetime,
										$pages_table.id AS page_id,
										$pages_table.f_post_id AS post_id,
										$pages_table.title AS page_title
								 FROM $meta_table
								 JOIN $pages_table
								   ON $meta_table.value=$pages_table.id
								   WHERE type='se keywords'
								 ORDER BY $meta_table.datetime DESC LIMIT 10;");

?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('Time', 'wp_visitorflow'); ?></th>
		<th><?php echo __('Search Engine', 'wp_visitorflow'); ?></th>
		<th><?php echo __('Keywords', 'wp_visitorflow'); ?></th>
		<th colspan="2"><?php echo __('Target', 'wp_visitorflow'); ?></th>
	</tr>
<?php
	
	$searchengines = $WP_VisitorFlow->get_SearchEngines();
	$count = 1;
	foreach ($results as $result) {
			
		list($se_key, $keywords) = explode('#', $result->label, 2);
		$se_name = $searchengines[$se_key]['label'];

		if ($count % 2 != 0) {
			echo '<tr>';
		}
		else {
			echo '<tr class="darker">';
		}
		$count++;	
		$nice_datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
					  wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
						);
		$nice_datetime = str_replace(' ' ,'&nbsp;',$nice_datetime);
		echo '<td>' . $nice_datetime . '</td>';
		echo '<td>'.  $se_name . '</td>';
		echo '<td>' . $keywords . '</td>';
		
		$title = $result->page_title;
		if ($result->post_id == -1) {
			$title = '<font color="red"><em>404 error:</em></font> ' . $title;
		}
		
		$pagelink = $result->page_title;
		if ($result->post_id > 0) {
			$pagelink = site_url() . '?p=' . $result->post_id;
		}
		else {
			$pagelink = site_url() . $result->page_title;;
		}

		echo '<td><a class="wpvf wpvfpage" href="'. $pagelink . '">' . $title . '</a></td>';
		echo '<td><a class="wpvf wpvfflow" href="?page=wpvf_mode_singlepage&amp;select_page_id=' .  $result->page_id . '">' . __('Flow', 'wp_visitorflow') . '</a></td>';
		

		echo '</tr>'; 
	}
	if (! is_array($results) || count($results) == 0) {
		echo '<tr><td colspan="4"><em>' . __('Table still empty.', 'wp_visitorflow') . '</em></td></tr>';
	}
?>
		</table>
<?php		
