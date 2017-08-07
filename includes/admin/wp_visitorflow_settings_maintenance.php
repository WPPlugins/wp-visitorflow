<?php
		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
	
		// Save settings?
		if (array_key_exists('wpvf_save_settings', $_POST)) {
			
			echo "<br />\n";
			
			if (array_key_exists('wpvf_reset_counters', $_POST)) {
		
				if (! array_key_exists('wpvf_confirm_reset', $_POST)) {
?>
					<div class="wpvf_warning">
						<p><?php echo __('Do you really want to reset bots and exlusions counters?', 'wp_visitorflow'); ?><p>
						<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp_visitorflow'); ?></font></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_reset_counters" value="1" />
						<?php submit_button(__('Yes, do it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm_reset'); ?>
						</form>
						<form id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
						</form>
					</div>
<?php						
				}
				else {
					// Reset bot counters in "Meta" table
					$sql = "UPDATE $meta_table
							SET value = '0'
							WHERE (type='count bot' || type='count exclusion' 
								   || type='count uastring' || type='count pagestring');";
					$result = $db->query($sql);		
					
					$WP_VisitorFlow->set_setting('counters-startdatetime', $WP_VisitorFlow->get_datetime(), 1);
			
					echo '<div class="wpvf_message">' . sprintf(__('%s bot and exclusion counters reset to zero.', 'wp_visitorflow'), $result) . "</div><br />\n";
				}
			}
			
			if (array_key_exists('wpvf_cleardate', $_POST)) {

				$cleardate = htmlspecialchars( stripslashes($_POST['wpvf_cleardate']));
				
				if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
					<div class="wpvf_warning">
						<p><?php echo sprintf(__('Do you really want to delete all statistics data older than %s?', 'wp_visitorflow'), 
										   date_i18n( get_option( 'date_format' ), strtotime($cleardate))); ?></p>
						<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp_visitorflow'); ?></font></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_cleardate" value="<?php echo $cleardate ?>" />
						<?php submit_button(__('Yes, do it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm'); ?>
						</form>
						<form id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
						</form>
					</div>
<?php
				}
				else {
				
					// Clear "Flow" table
					$result = $db->query( $db->prepare("DELETE FROM $flow_table WHERE datetime<'%s';", $cleardate) );
					$message = sprintf(__('%s page views older than %s cleaned.', 'wp_visitorflow'), $result, $cleardate) . '<br />';
					
					// Clear "Pages" table
					$result = $db->query("DELETE FROM $pages_table 
											WHERE NOT EXISTS (
												SELECT id
												FROM $flow_table
												WHERE $flow_table.f_page_id=$pages_table.id
											)
											AND id>'3';"
										   );
					$message .= sprintf(__('%s pages older than %s cleaned.', 'wp_visitorflow'), $result, $cleardate) . '<br />';
					
					// Clear "Visits" table
					$result = $db->query("DELETE FROM $visits_table 
											WHERE NOT EXISTS (
												SELECT id
												FROM $flow_table
												WHERE $flow_table.f_visit_id=$visits_table.id
											)"
										   );
					$message .= sprintf(__('%s visits older than %s cleaned.', 'wp_visitorflow'), $result, $cleardate) . '<br />';
				
					// Clear "Meta" table
					$result = $db->query( $db->prepare("DELETE FROM $meta_table WHERE datetime<'%s';", $cleardate) );
					$message .= sprintf(__('%s meta entries older than %s cleaned.', 'wp_visitorflow'), $result, $cleardate) . '<br />';
					
					// Clear "Aggregation" table
					$result = $db->query( $db->prepare("DELETE FROM $aggregation_table WHERE date<'%s';", $cleardate) );
					$message .= sprintf(__('%s aggregation entries older than %s cleaned.', 'wp_visitorflow'), $result, $cleardate) . '<br />';
					
					if ($message) {
						$WP_VisitorFlow->store_Meta('log', 'cleanup', $message);
					}
					
					$message .= $WP_VisitorFlow->post_update();
					
					echo '<p class="wpvf_message">' . $message . "</p>\n";
				}
			
			}
			
			if (array_key_exists('wpvf_clear_ua_date', $_POST)) {

				$cleardate = htmlspecialchars( stripslashes($_POST['wpvf_clear_ua_date']));

				if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
					<div class="wpvf_warning">
						<p><?php echo sprintf(__('Do you really want to delete all HTTP User Agent strings older than %s?', 'wp_visitorflow'), 
										   date_i18n( get_option( 'date_format' ), strtotime($cleardate))); ?></p>
						<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp_visitorflow'); ?></font></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_clear_ua_date" value="<?php echo $cleardate ?>" />
						<?php submit_button(__('Yes, do it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm'); ?>
						</form>
						<form id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
						</form>
					</div>
<?php
				}
				else {
					// Clear UA strings from "Meta" table
					$result = $db->query( $db->prepare("DELETE FROM $meta_table WHERE type='useragent' AND datetime<'%s';", $cleardate) );
					echo '<div class="wpvf_message">' . sprintf(__('%s user agent strings removed from database.', 'wp_visitorflow'), $result) . "</div><br />\n";
				}
			}
			
			
			if (array_key_exists('wpvf_trigger_aggregation', $_POST)) {
				if (! array_key_exists('wpvf_confirm', $_POST)) {	
?>
					<div class="wpvf_warning">
						<p><?php echo __('Do you really want to restart the data aggregation process?', 'wp_visitorflow'); ?></p>
						<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp_visitorflow'); ?></font></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_trigger_aggregation" value="1" />
						<?php submit_button(__('Yes, do it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm'); ?>
						</form>
						<form id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
						</form>
					</div>
<?php
				}
				else {
					if (! array_key_exists('wpvf_open_jobs', $_POST)) {	
						$WP_VisitorFlow->set_setting('last_aggregation_date', 0, 1);
					}
					
					$WP_VisitorFlow->set_setting('data_aggregation_running', 0);
					
					$script_start = time();
					
					$open_jobs = $WP_VisitorFlow->data_aggregation();
					while ( $open_jobs && time() - $script_start < 10) {
						$open_jobs = $WP_VisitorFlow->data_aggregation();
					}
					
					if ($open_jobs) {
?>
					<div class="wpvf_warning">
						<p><?php echo sprintf( __('Data aggregration completed until %s.', 'wp_visitorflow'),
											   date_i18n( get_option( 'date_format' ), strtotime( $WP_VisitorFlow->get_setting('last_aggregation_date') ) )
											  ); ?></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_trigger_aggregation" value="1" />
						<input type="hidden" name="wpvf_open_jobs" value="1" />
						<?php submit_button(__('Continue', 'wp_visitorflow'), 'delete', 'wpvf_confirm'); ?>
						</form>
						<p class="description"><font color="red"><?php _e('Data aggretion takes some time. Please press "continue" button until taks is completed.', 'wp_visitorflow'); ?></font></p>
					</div>
<?php
						
					}
					
					else {
						$message = __('Restart of the data aggregation completed.', 'wp_visitorflow') . '<br />';
						echo '<p class="wpvf_message">' . $message . "</p><br />\n";
					}
				}
			}
			
			
			if (array_key_exists('wpvf_trigger_install', $_POST)) {
				if (! array_key_exists('wpvf_confirm', $_POST)) {	
?>
					<div class="wpvf_warning">
						<p><?php echo __('Do you really want to restart the database update process?', 'wp_visitorflow'); ?></p>
						<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp_visitorflow'); ?></font></p>
						<form id="wpvf_settings" method="post">
						<input type="hidden" name="tab" value="maintenance" />
						<input type="hidden" name="wpvf_save_settings" value="1" />
						<input type="hidden" name="wpvf_trigger_install" value="1" />
						<?php submit_button(__('Yes, do it!', 'wp_visitorflow'), 'delete', 'wpvf_confirm'); ?>
						</form>
						<form id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp_visitorflow'), 'no'); ?>
						</form>
					</div>
<?php
				}
				else {
					include_once( dirname( __FILE__ ) . '/../functions/wp-visitorflow_install.php' );
					
					$message = __('Database update process completed.', 'wp_visitorflow') . '<br />';
					$message .= $WP_VisitorFlow->post_update();
					
					echo '<p class="wpvf_message">' . $message . "</p><br />\n";
				}
			}
			
			wp_visitorflow_update_db_info(TRUE);

		}
		
		$db_info = $WP_VisitorFlow->get_setting('db_info');
		
?>
	<br />
	<div class="wpvf-background">		
		<h3><?php echo __('Database Summary', 'wp_visitorflow'); ?></h3>
<?php
		
		include_once dirname( __FILE__ ) . '/../../includes/functions/wp_visitorflow_getDBOverviewTable.php';
?>
	</div>
<?php
	
		if (! array_key_exists('wpvf_save_settings', $_POST)) {

?>
		<br />
		<br />
		
		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>		

			<tr>
				<th scope="row" colspan="2">
					<h3><?php _e('Clean-Up Database', 'wp_visitorflow'); ?></h3>
				</th>
			</tr>
			
			<form id="wpvf_settings" method="post">
			<input type="hidden" name="tab" value="maintenance" />
			<input type="hidden" name="wpvf_save_settings" value="1" />
			<input type="hidden" name="wpvf_reset_counters" value="1" />
			<tr>
				<th scope="row">
					<label for="cleardate"><?php echo __('Reset counters', 'wp_visitorflow'); ?></label>
				</th>
				<td>
					<?php submit_button(__('Reset counters', 'wp_visitorflow'), 'delete'); ?>
					<p class="description"><?php _e('Resets the counters for bot visits and page view exclusions.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			</tbody>
			</table>
			</form>
		</div>
		
		<br />
		<br />

		<div class="wpvf-background">	
			<form id="wpvf_settings" method="post">
			<input type="hidden" name="tab" value="maintenance" />
			<input type="hidden" name="wpvf_save_settings" value="1" />
			
			<table class="form-table">
			<tbody>				
		
			<tr>
				<th scope="row">
					<label for="cleardate"><?php echo __('Clear all statistics data', 'wp_visitorflow'); ?></label>
				</th>
				<td>
					<label for="cleardate"><?php echo __('older than', 'wp_visitorflow'); ?></label>
					<input type="date" id="cleardate" name="wpvf_cleardate" value="<?php echo date( 'Y-m-d', strtotime( '-1 days')); ?>">.<br />
					<p class="description"><?php _e('Delete all entries in the WP VisitorFlow data base older than the selected date.', 'wp_visitorflow'); ?></p>
					<?php submit_button(__('Clear Data', 'wp_visitorflow'), 'delete'); ?>
				</td>
			</tr>
			</tbody>
			</table>
			</form>

		</div>
		
		<br />
		<br />

		<div class="wpvf-background">	
			<form id="wpvf_settings" method="post">
			<input type="hidden" name="tab" value="maintenance" />
			<input type="hidden" name="wpvf_save_settings" value="1" />

			<table class="form-table">
			<tbody>				

			<tr>
				<th scope="row">
					<label for="clearuastrings"><?php echo __('Clear HTTP User Agents strings', 'wp_visitorflow'); ?></label>
				</th>
				<td>
					<label for="clearuastrings"><?php echo __('older than', 'wp_visitorflow'); ?></label>
					<input type="date" id="clearuastrings" name="wpvf_clear_ua_date" value="<?php echo date( 'Y-m-d', strtotime( '-1 days')); ?>">.<br />
					<?php submit_button(__('Clear HTTP UA Strings', 'wp_visitorflow'), 'delete'); ?>
					<p class="description"><?php _e('Delete all HTTP User Agent strings stored before the selected date.', 'wp_visitorflow'); ?><br />
										   <?php _e('The user agent strings are no mandatory data. So if you do not need them anymore (e.g. to identify crawlers and search engines), it is safe to clear these strings.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			</tbody>
			</table>
			</form>
		</div>
		
		<br />
		<br />

		<div class="wpvf-background">			
			
			<form id="wpvf_settings" method="post">
			<input type="hidden" name="tab" value="maintenance" />
			<input type="hidden" name="wpvf_save_settings" value="1" />
			<input type="hidden" name="wpvf_trigger_aggregation" value="1" />
			
			<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Trigger Data Aggregation', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row">
					<label for="triggeraggregation"><?php echo __('Restart data aggregation.', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<?php submit_button(__('Trigger Data Aggregation', 'wp_visitorflow'), 'delete'); ?>
					<p class="description"><?php _e('Restart the data aggregation, e.g. in case of inconsistencies/missing data in the timelines.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			</tbody>
			</table>		
			</form>
		</div>
		
		<br />
		<br />

		<div class="wpvf-background">	
		
			<form id="wpvf_settings" method="post">
			<input type="hidden" name="tab" value="maintenance" />
			<input type="hidden" name="wpvf_save_settings" value="1" />
			<input type="hidden" name="wpvf_trigger_install" value="1" />
			<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Trigger Installer', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row">
					<label for="triggerinstaller"><?php echo __('Update DB tables.', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<?php submit_button(__('Trigger Installer', 'wp_visitorflow'), 'delete'); ?>
					<p class="description"><?php _e('Restart the update process for the database tables, e.g. in case of inconsistancies or interrupted update mechanisms.', 'wp_visitorflow'); ?></p>
					<p class="description"><?php _e('Data will not be affected or changed. However, it is always a safe option to backup the database before any change on its structure is performed.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			</tbody>
			</table>		
			</form>
		</div>
<?php
		}