<?php
		$WP_VisitorFlow = WP_VisitorFlow::getInstance();

		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = $WP_VisitorFlow->get_DB();
		$meta_table = $WP_VisitorFlow->get_table_name('meta');
		$flow_table = $WP_VisitorFlow->get_table_name('flow');


		if ($WP_VisitorFlow->get_setting('store_useragent')) {
			$results = $db->get_results("SELECT $meta_table.value AS ua_string, 
												MAX($flow_table.datetime) AS datetime,
												$meta_table.label AS visit_id,
												COUNT($flow_table.id) AS page_hits
										   FROM $meta_table
										   JOIN $flow_table
											 ON $flow_table.f_visit_id=$meta_table.label
										  WHERE type='useragent'
									   GROUP BY $flow_table.f_visit_id
									   ORDER BY $meta_table.id DESC LIMIT 50;");
		
?>
		<table class="wpvftable">
		<tr>
			<th><?php echo __('HTTP User Agent String', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Page Views', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Last Visit', 'wp_visitorflow'); ?></th>
		</tr>
<?php
				$count = 1;
				foreach ($results as $result) {
					
					if ($count % 2 != 0) {
						echo '<tr>';
					}
					else {
						echo '<tr class="darker">';
					}
					$count++;		
					
					$url = '?page=wpvf_mode_website&amp;tab=visitors&amp;visit_id=' . $result->visit_id;
					$agentlink = '<a class="wpvf" href="' . $url . '">'. $result->ua_string . '</a>';
					echo '<td>' . $agentlink . ' </td>';
					$nice_datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
										  wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
										    );
					$nice_datetime = str_replace(' ' ,'&nbsp;',$nice_datetime);
					echo '<td class="right">' . ($result->page_hits - 1) . '</td>';
					echo '<td>' . $nice_datetime . '</td>';
					echo '</tr>'; 
				}
			if (! is_array($results) || count($results) == 0) {
				echo '<tr><td colspan="3"><em>' . __('Table still empty.', 'wp_visitorflow') . '</em></td></tr>';
			}
?>
		</table>
<?php		
		}