<?php
		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
	
		// Save new settings?
		if (array_key_exists('wpvf_save_settings', $_POST)) {
			
			$settings_to_save = array('read_access_capability', 
									  'admin_access_capability',
									  'flowdata_storage_time',
									  );
			foreach ($settings_to_save as $key) {
				if (array_key_exists($key, $_POST)) {
					$WP_VisitorFlow->set_setting($key, htmlspecialchars( stripslashes( $_POST[$key] ) ), 0);
				}
			}
			
			if ($WP_VisitorFlow->get_setting('flowdata_storage_time') < 3) { $WP_VisitorFlow->set_setting('flowdata_storage_time', 3, 0); }
			if ($WP_VisitorFlow->get_setting('flowdata_storage_time') > 365) { $WP_VisitorFlow->set_setting('flowdata_storage_time', 365, 0); }
				
			// Record Visitor Flow?
			$WP_VisitorFlow->set_setting('record_visitorflow', FALSE, 0);
			if (array_key_exists('record_visitorflow', $_POST)) {
				$WP_VisitorFlow->set_setting('record_visitorflow', TRUE, 0);
			}
						
			// Minimum time between:
			if (array_key_exists('minimum_time_between', $_POST)) {
				$WP_VisitorFlow->set_setting('minimum_time_between', htmlspecialchars( stripslashes( $_POST['minimum_time_between'] ) ), 0);
				if ($WP_VisitorFlow->get_setting('minimum_time_between') < 1) { $WP_VisitorFlow->set_setting('minimum_time_between', 1, 0); }
				if ($WP_VisitorFlow->get_setting('minimum_time_between') > 10000) { $WP_VisitorFlow->set_setting('minimum_time_between', 10000, 0); }
			}
			
			// Save settings
			$WP_VisitorFlow->save_settings();
			
			// Start clean-up (in case that flowdata_storage_time was shortened)
			wp_visitorflow_db_cleanup();
			
		}
		
		// Print settings menu
?>
		<br />
		<form id="wpvf_settings" method="post">
		<input type="hidden" name="tab" value="general" />
		<input type="hidden" name="wpvf_save_settings" value="1" />

		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>
			
			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Visitor Flow Recording', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Record Visitor Flow', 'wp_visitorflow'); ?>:</th>
				<td>
					<input id="record_visitorflow" type="checkbox" value="1" name="record_visitorflow" <?php echo $WP_VisitorFlow->get_setting('record_visitorflow') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="record_visitorflow"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('record_visitorflow') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php echo __('This is the <em>main swith</em> :-) ', 'wp_visitorflow'); ?></p>
					<p class="description"><?php echo __('If active, the visitor flow will be recorded. If inactive, no data will be collected.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="timebetween"><?php echo __('Minimum time between visits', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<input id="timebetween" type="number" name="minimum_time_between" value="<?php echo $WP_VisitorFlow->get_setting('minimum_time_between'); ?>" min="0" max="2000">
					<label for="timebetween"><?php echo sprintf(__('minutes (default: %s minutes)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('minimum_time_between') ); ?></label>
					<p class="description"><?php _e('The minimum time period between two visits by a remote client that defines a new visitor. ', 'wp_visitorflow'); ?></p>
					<p class="description"><?php _e('Is the time between two visits by a remote client shorter than this period, the client will be counted as the same visitor. Is it longer, he or she will be counted as a new visitor.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Flow Data Storage Time', 'wp_visitorflow'); ?>:</th>
				<td>
					<input id="storagetime" type="number" name="flowdata_storage_time" value="<?php echo $WP_VisitorFlow->get_setting('flowdata_storage_time'); ?>" min="3" max="365">
					<label for="storagetime"><?php echo sprintf(__('days (default: %s days)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('flowdata_storage_time') ); ?></label>
					<p class="description"><?php echo __('Detailed flow data is stored only for this amount of days. Older data will be automatically deleted to keep the database lean and the webpage performance high.', 'wp_visitorflow'); ?></p>
					<p class="description"><?php echo __('This affects only the detailed flow data such as information about the user agents, operation systems and click-paths. Aggregated data such as daily page hit counts will not be affected.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
<?php
		global $wp_roles;	
		$capabilities = array();
		
		foreach ($wp_roles->roles as $role ) {
			foreach ($role['capabilities'] as $key => $value ) {
				if( substr($key,0,6) != 'level_' ) {
					$capabilities[$key] = 1;
				}
			}
		}
		ksort( $capabilities );

		$read_access_capability = $WP_VisitorFlow->get_setting('read_access_capability');

		$options = '';
		foreach( $capabilities as $key => $value ) {
			if( $key == $read_access_capability ) { $selected = " SELECTED"; } 
			else { $selected = ""; }
			$options .= '<option value="' .$key .'"' . $selected . '>' . $key . '</option>';
		}
		
?>
			</tbody>
			</table>
		</div>
		
		<br />
		<br />
		
		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>
	
			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Minimum User Access Levels', 'wp_visitorflow'); ?></h3></th>
			</tr>
			
			<tr>
				<th scope="row" colspan="2">
					<p class="description"><strong><?php echo __('Attention!', 'wp_visitorflow'); ?></strong></p>
					<p class="description"><?php echo __('Make sure to chose only access levels for which you have access. Otherwise you would exclude yourself from this admin area.', 'wp_visitorflow'); ?></p>
				</th>
			</tr>
			<tr>
				<th scope="row"><label for="read_access_capability"><?php _e('Access to WP VisitorFlow data and visualization', 'wp_visitorflow')?>:</label></th>
				<td>
					<select id="read_access_capability" name="read_access_capability"><?php echo $options;?></select>
					<label for="read_access_capability"><?php echo __('Default:', 'wp_visitorflow') . ' ' . $WP_VisitorFlow->get_default_settings('read_access_capability'); ?></label>
				</td>
			</tr>
<?php	

		$admin_access_capability = $WP_VisitorFlow->get_setting('admin_access_capability');
			
		$options = '';
		foreach( $capabilities as $key => $value ) {
			
			if( $key == $admin_access_capability ) { $selected = " SELECTED"; } 
			else { $selected = ""; }
			$options .= '<option value="' .$key .'"' . $selected . '>' . $key . '</option>';
		}
?>
			<tr>
				<th scope="row"><label for="admin_access_capability"><?php _e('Access to WP VisitorFlow settings section', 'wp_visitorflow')?>:</label></th>
				<td>
					<select id="admin_access_capability" name="admin_access_capability"><?php echo $options;?></select>
					<label for="admin_access_capability"><?php echo __('Default:', 'wp_visitorflow') . ' ' . $WP_VisitorFlow->get_default_settings('admin_access_capability'); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="2">
					<p class="description"><?php echo sprintf(__('See the %s for details on capability levels.', 'wp_visitorflow'), '<a target=_blank href="http://codex.wordpress.org/Roles_and_Capabilities">' . __('WordPress Roles and Capabilities page', 'wp_visitorflow') . '</a>'); ?></p>
					<p class="description"><?php echo __('Hint: manage_network = Super Admin Network, manage_options = Administrator, edit_others_posts = Editor, publish_posts = Author, edit_posts = Contributor, read = Everyone.', 'wp_visitorflow'); ?></p>
				</th>
			</tr>
			
			</tbody>
			</table>
		</div>
		
		<?php submit_button(); ?>
		
		</form>