<?php

		$WP_VisitorFlow = WP_VisitorFlow::getInstance();

		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = $WP_VisitorFlow->get_DB();
		$meta_table  = $WP_VisitorFlow->get_table_name('meta');

		$db_info = $WP_VisitorFlow->get_setting('db_info');
	
?>
		<table class="wpvftable">
		<tr>
			<th><?php echo __('Reason for Exclusion', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Page Views', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Page Views per Day', 'wp_visitorflow'); ?></th>
			<th><?php echo __('Last View', 'wp_visitorflow'); ?></th>
		</tr>
<?php
		
		// Exclusions due to User Agent String:
		$exclude_strings = array();
		foreach ($WP_VisitorFlow->get_setting('crawlers_exclude_list') as $crawler_string) {
			array_push($exclude_strings, $crawler_string);
		}
		array_push($exclude_strings, 'unknown');
		
		$table_empty = TRUE;
		$some_hidden = FALSE;
		$count = 1;
		foreach ($exclude_strings as $exclude_string) {
			
			$result = $db->get_row( 
						$db->prepare("SELECT value, datetime
										FROM $meta_table
										WHERE type='count uastring' AND label='%s';",
										$exclude_string) 
									);
	
			$value = 0;
			$datetime = '-';
			if (isset($result->value)) {
				$value = $result->value;
				$datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
														wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
													   );
			}
			if ($value > 0 || array_key_exists('wpvf_exclusions_showall', $_GET)) {
				$table_empty = FALSE;
				if ($count % 2 != 0) {
					echo '<tr>';
				}
				else {
					echo '<tr class="darker">';
				}
				$count++;	
				if ($exclude_string == 'unknown') {
					echo '<td><em>UA string</em>: empty/unknown</td>';
				}
				else {
					echo '<td><em>UA string</em>: ' . $exclude_string . '</td>';
				}
				if ($value > 0) {
					echo '<td class="right"><strong>' . $value . '</strong></td>';
					echo '<td class="right">' . sprintf("%3.1f", $value * $db_info['perdayfactor']) . '</td>';
				}
				else {
					echo '<td class="right">' . $value . '</td>';
					echo '<td class="right">&minus;</td>';
				}
				echo '<td>' . $datetime . '</td>';
				echo '</tr>'; 
			}
			else {
				$some_hidden = TRUE;
			}
		}
		
		// Exclusions due to Page String:
		$exclude_strings = array();
		foreach ($WP_VisitorFlow->get_setting('pages_exclude_list') as $exclusion_string) {
			array_push($exclude_strings, $exclusion_string);
		}
		
		foreach ($exclude_strings as $exclude_string) {
			
			$result = $db->get_row( 
						$db->prepare("SELECT value, datetime
										FROM $meta_table
										WHERE type='count pagestring' AND label='%s';",
										$exclude_string) 
									);
	
			$value = 0;
			$datetime = '-';
			if (isset($result->value)) {
				$value = $result->value;
				$datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
										wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
									);
			}
			if ($value > 0 || array_key_exists('wpvf_exclusions_showall', $_GET)) {
				$table_empty = FALSE;
				if ($count % 2 != 0) {
					echo '<tr>';
				}
				else {
					echo '<tr class="darker">';
				}
				$count++;	
				echo '<td><em>' . __('Page', 'wp_visitorflow') . '</em>: ' . $exclude_string . '</td>';
				if ($value > 0) {
					echo '<td class="right"><strong>' . number_format_i18n($value) . '</strong></td>';
					echo '<td class="right">' . sprintf("%3.1f", $value * $db_info['counters_perdayfactor']) . '</td>';
				}
				else {
					echo '<td class="right">' . number_format_i18n($value) . '</td>';
					echo '<td class="right">&minus;</td>';
				}
				echo '<td>' . $datetime . '</td>';
				echo '</tr>'; 
			}
			else {
				$some_hidden = TRUE;
			}
		}
		
		// Exclusions due 404 errors?
		$result = $db->get_row("SELECT value, datetime
								FROM $meta_table 
								WHERE type='count exclusion' AND label='404'");
		if (isset($result) && $result->value) {
			if ($result->value) {
				$table_empty = FALSE;
				$datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
										wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
									);
				if ($count % 2 != 0) {
					echo '<tr>';
				}
				else {
					echo '<tr class="darker">';
				}
				$count++;	
				echo '<td><em>' . __('404 errors', 'wp_visitorflow') . '</em></td>';
				echo '<td class="right"><strong>' . number_format_i18n($result->value) . '</strong></td>';
				echo '<td class="right">' . sprintf("%3.1f", $result->value * $db_info['perdayfactor']) . '</td>';
				echo '<td>' . $datetime . '</td>';
				echo '</tr>'; 
			}
		}
		
		// Exclusions due to self-referrers?
		$result = $db->get_row("SELECT value, datetime
								FROM $meta_table 
								WHERE type='count exclusion' AND label='self-referrer'");
		if (isset($result) && $result->value) {
			if ($result->value) {
				$table_empty = FALSE;
				$datetime = sprintf(__('%s ago', 'wp_visitorflow'), 
										wp_visitorflow_getNiceTimeDifferenz($result->datetime, $WP_VisitorFlow->get_datetime() ) 
									);
				if ($count % 2 != 0) {
					echo '<tr>';
				}
				else {
					echo '<tr class="darker">';
				}
				$count++;	
				echo '<td><em>' . __('Self-referrers', 'wp_visitorflow') . '</em></td>';
				echo '<td class="right"><strong>' . number_format_i18n($result->value) . '</strong></td>';
				echo '<td class="right">' . sprintf("%3.1f", $result->value * $db_info['perdayfactor']) . '</td>';
				echo '<td>' . $datetime . '</td>';
				echo '</tr>'; 
			}
		}


		if ($table_empty) {
			echo '<tr><td colspan="4"><em>' . __('Table still empty.', 'wp_visitorflow') . '</em></td></tr>';
		}
		
		echo "</table>\n";
		
		if ($some_hidden) {
?>
			<a class="wpvf" href="?page=wpvf_menu&amp;wpvf_exclusions_showall=1">[ <?php echo  __('Show all', 'wp_visitorflow'); ?> ]</a>
<?php		
		}