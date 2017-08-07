<?php
		if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
	
		// Save settings?
		if (array_key_exists('wpvf_save_settings', $_POST)) {
			if (array_key_exists('encrypt_ips', $_POST)) {
				$WP_VisitorFlow->set_setting('encrypt_ips', TRUE, 0);
			}
			else {
				$WP_VisitorFlow->set_setting('encrypt_ips', FALSE, 0);
			}
			if (array_key_exists('store_useragent', $_POST)) {
				$WP_VisitorFlow->set_setting('store_useragent', TRUE, 0);
			}
			else {
				$WP_VisitorFlow->set_setting('store_useragent', FALSE, 0);
			}
			
			$WP_VisitorFlow->save_settings(); 
		}
		// Print setting menu:	
?>
		<br />
		<form id="wpvf_settings" method="post">
		<input type="hidden" name="tab" value="general" />
		<input type="hidden" name="wpvf_save_settings" value="1" />

		<div class="wpvf-background">	
			<table class="form-table">
			<tbody>		
	
			<tr>
				<th scope="row" colspan="2"><h3><?php _e('IP Addresses', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row">
					<label for="haship"><?php echo __('Anonymize IP addresses', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<input id="haship" type="checkbox" value="1" name="encrypt_ips" <?php echo $WP_VisitorFlow->get_setting('encrypt_ips') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="haship"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('encrypt_ips') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php _e('Anonymize IP addresses of remote clients by encryption.', 'wp_visitorflow'); ?>
										   <?php _e('This option is recommended to fulfill data privacy rules in several countries.', 'wp_visitorflow'); ?><br />
										   <?php _e('Be aware: Once encrypted, the stored IP addresses cannot be restored.', 'wp_visitorflow'); ?></p>
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
				<th scope="row" colspan="2"><h3><?php _e('HTTP User Agent String', 'wp_visitorflow'); ?></h3></th>
			</tr>
			<tr>
				<th scope="row">
					<label for="store_useragent"><?php echo __('Store HTTP User Agent String', 'wp_visitorflow'); ?>:</label>
				</th>
				<td>
					<input id="store_useragent" type="checkbox" value="1" name="store_useragent" <?php echo $WP_VisitorFlow->get_setting('store_useragent') == TRUE? 'checked="checked"' : ''; ?>>
					<label for="store_useragent"><?php echo sprintf(__('Active (default: %s)', 'wp_visitorflow'), $WP_VisitorFlow->get_default_settings('store_useragent') == TRUE ? __('active', 'wp_visitorflow') : __('inactive', 'wp_visitorflow') ); ?></label>
					<p class="description"><?php _e('Store the full HTTP User Agent string submitted by remote clients. This information can be helpful in the idenditification of crawlers and search engines.', 'wp_visitorflow'); ?><br />
					<p class="description"><?php _e('This option leads to a lot of additional data.', 'wp_visitorflow'); ?>
										   <?php _e('On the other hand, this data can easily be deleted again later.', 'wp_visitorflow'); ?></p>
				</td>
			</tr>
			
			</tbody>
			</table>	
		</div>
		
		<?php submit_button(); ?>
		</form>