<?php
		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
	
		global $wp_roles;	
		$roles = $wp_roles->get_names();		

		// Save settings?
		if (array_key_exists('wpvf_save_settings', $_POST)) {
			
			// Exclude Detected Bots
			$WP_VisitorFlow->set_setting('exclude_bots', FALSE, 0);
			if (array_key_exists('exclude_bots', $_POST)) {
				$WP_VisitorFlow->set_setting('exclude_bots', TRUE, 0);
			}
			
			// Exclude Empty UA Strings
			$WP_VisitorFlow->set_setting('exclude_unknown_useragents', FALSE, 0);
			if (array_key_exists('exclude_unknown_useragents', $_POST)) {
				$WP_VisitorFlow->set_setting('exclude_unknown_useragents', TRUE, 0);
			}
			
			// Exclude crawlers
			$excluded_crawlers = array();
			if (array_key_exists('crawlers_exclude_list', $_POST)) {
				$exclude_list_string = str_replace(array("\r\n", "\r"), "\n",  htmlspecialchars( stripslashes( $_POST['crawlers_exclude_list'] ) ) );
				$list = explode( "\n", $exclude_list_string);
				foreach ($list as $item) {
					$item = preg_replace('/\s+/', ' ', $item);
					$item = str_replace("\t", '', $item);
					$item = trim($item);
					if ($item) {
						array_push($excluded_crawlers, $item);
					}
				}
			}
			$WP_VisitorFlow->set_setting('crawlers_exclude_list', $excluded_crawlers );
							
			// Exclude pages
			$WP_VisitorFlow->set_setting('exclude_404', FALSE, 0);
			if (array_key_exists('exclude_404', $_POST)) {
				$WP_VisitorFlow->set_setting('exclude_404', TRUE, 0);
			}

			$excluded_pagestrings = array();
			if (array_key_exists('pages_exclude_list', $_POST)) {
				$exclude_list_string = str_replace(array("\r\n", "\r"), "\n",  htmlspecialchars( stripslashes( $_POST['pages_exclude_list'] ) ) );
				$list = explode( "\n", $exclude_list_string);
				foreach ($list as $item) {
					$item = preg_replace('/\s+/', ' ', $item);
					$item = str_replace("\t", '', $item);
					$item = trim($item);
					if ($item) {
						array_push($excluded_pagestrings, $item);
					}
				}
			}
			$WP_VisitorFlow->set_setting('pages_exclude_list', $excluded_pagestrings );
			
			// Include WP Roles
			foreach ($roles as $role ) {
				$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
				$option_key = 'wpvf_' . $setting_key;
				if (array_key_exists($option_key, $_POST)) {
					$WP_VisitorFlow->set_setting($setting_key, TRUE, 0);
				}
				else {
					$WP_VisitorFlow->set_setting($setting_key, FALSE, 0);
				}
			}

			// Check settings:

			$WP_VisitorFlow->save_settings(); 
		}

		
?>
		<br />
		<form id="wpvf_settings" method="post">
		<input type="hidden" name="tab" value="storing" />
		<input type="hidden" name="wpvf_save_settings" value="1" />
		
		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>

			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Exclude Remote Clients', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Exclude Detected Bots', 'wp_visitorflow'); ?>:</th>
				<td>
					<input id="exclude_bots" type="checkbox" value="1" name="exclude_bots" <?php echo $WP_VisitorFlow->get_setting('exclude_bots') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="exclude_bots"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('exclude_bots') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php echo __('Exclude bots, crawlers, web spiders etc. from the statistics.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php echo __('Exclude empty UA strings', 'wp_visitorflow'); ?>:</th>
				<td>
					<input id="exclude_unknown_useragents" type="checkbox" value="1" name="exclude_unknown_useragents" <?php echo $WP_VisitorFlow->get_setting('exclude_unknown_useragents') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="exclude_unknown_useragents"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('exclude_unknown_useragents') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php echo __('Exclude remote clients that do not submit any HTTP User Agent string from the statistics.', 'wp_visitorflow'); ?></p>
					<p class="description"><?php echo __('If the HTTP User Agent string is not submitted, this is usually a fair indication that the visitor is not a human being and/or did supresses the submission by purpose.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="timebetween"><?php echo __('UA string exclusion list', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<textarea name="crawlers_exclude_list" id="crawlers_exclude_list"  rows="6" cols="40"><?php
					foreach( $WP_VisitorFlow->get_setting('crawlers_exclude_list') as $string ) {
						echo $string."\n";
					}
					?></textarea>
					<p class="description"><?php echo __('Any remote client with a HTTP User Agent that includes one of the text strings in this list will be excluded from the statistics.', 'wp_visitorflow'); ?></p>
					<p class="description"><?php echo __('You can edit the list and/or include new text strings at the end of the list. One string per line.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			
			</tbody>
			</table>
		</div>
		
		<br />
		<br />

		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>

			<tr>
				<th scope="row" colspan="2"><h3><?php _e('Exclude WordPress Pages', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Exclude 404 error pages', 'wp_visitorflow'); ?>:</th>
				<td>
					<input id="exclude_404" type="checkbox" value="1" name="exclude_404" <?php echo $WP_VisitorFlow->get_setting('exclude_404') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="exclude_404"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('exclude_404') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php echo __('Exclude non-existent pages/files, which lead to a 404 error page.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="timebetween"><?php echo __('Pages URL exclusion list', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<textarea name="pages_exclude_list" id="pages_exclude_list"  rows="6" cols="40"><?php
					foreach( $WP_VisitorFlow->get_setting('pages_exclude_list') as $string ) {
						echo $string."\n";
					}
					?></textarea>
					<p class="description"><?php echo __('Any page URL that includes one of the text strings in this list will be excluded from the statistics.', 'wp_visitorflow'); ?></p>
					<p class="description"><?php echo __('You can edit the list and/or include new text strings at the end of the list. One string per line.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
		
		</tbody>
			</table>
		</div>
		
		<br />
		<br />

		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>

			<tr>
				<th scope="row" colspan="2"><h3><?php echo __('Include WordPress Users', 'wp_visitorflow'); ?></h3></th>
			</tr>
<?php
		
		foreach ($roles as $role ) {
			$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
			$option_key = 'wpvf_' . $setting_key;
			$translated_role_name = translate_user_role($role);
?>
			<tr>
				<th scope="row"><label for="<?php echo $option_key;?>"><?php echo $translated_role_name; ?>:</label></th>
				<td>
					<input id="<?php echo $option_key;?>" type="checkbox" value="1" name="<?php echo $option_key;?>" <?php echo $WP_VisitorFlow->get_setting($setting_key) == TRUE? 'checked="checked"' : ''; ?>>
					<label for="<?php echo $option_key;?>"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings($setting_key) == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php echo sprintf(__('Include role "%s" in the statistics.', 'wp_visitorflow'), $translated_role_name); ?></p>
				</td>
			</tr>
<?php
		}
?>
			<tr>
				<td>
				</td>
				<td>
					<p class="description"><?php echo __('By default, the visitor flow (page views) of logged-in WordPress user is not recorded. You can include logged-in WordPress user with various roles to the statistics.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			
			</tbody>
			</table>	
		</div>
		
		<?php submit_button(); ?>
		</form>