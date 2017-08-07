<?php
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();	

	if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	// Short variables for database object and table names from visitorflow class
	$db = $WP_VisitorFlow->get_DB();
	$visits_table = $WP_VisitorFlow->get_table_name('visits');
	$flow_table   = $WP_VisitorFlow->get_table_name('flow');
	$pages_table  = $WP_VisitorFlow->get_table_name('pages');
	$meta_table   = $WP_VisitorFlow->get_table_name('meta');
	$aggregation_table = $WP_VisitorFlow->get_table_name('aggregation');
	
	$admin_tabs = array( 'general'  	 => array( 'title' => __('General', 'wp_visitorflow'),			 	'min_role' => 'moderate_comments'),
						 'storing'  	 => array( 'title' => __('Recording Settings', 'wp_visitorflow'), 	'min_role' => 'moderate_comments'),
						 'privacy'  	 => array( 'title' => __('Privacy', 'wp_visitorflow'),	    	 	'min_role' => 'moderate_comments'),
						 'maintenance' 	 => array( 'title' => __('Maintenance', 'wp_visitorflow'),		 	'min_role' => 'moderate_comments'),
						 'logfile'		 => array( 'title' => __('Logfile', 'wp_visitorflow'),			 	'min_role' => 'moderate_comments'),
						);  

	include_once dirname( __FILE__ ) . '/../classes/wp_visitorflow-page.class.php';
	
	$settingsPage = new WP_VisitorFlow_Page($WP_VisitorFlow->get_setting('admin_access_capability'), FALSE, $admin_tabs);

	// Print Page Header
?>
	<div class="wrap">
		<div style="float:left;">
			<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/images/Logo_250.png'; ?>" align="left" width="80" height="80" alt="Logo" />
		</div>
		<h1>WP VisitorFlow &ndash; <?php echo __('Settings', 'wp_visitorflow') ?></h1>
		<div style="clear:both;"></div>
<?php	
	
	// Print Tab Menu
?>
		<div style="clear:both;"></div>
		<h2 class="wpvf-nav-tab-wrapper">
<?php
		foreach ($admin_tabs as $tab => $props) {
			if (current_user_can($props['min_role']) ) {
				if ($settingsPage->get_current_tab() == $tab){
					$class = ' wpvf-nav-tab-active';
				} 
				else {
					$class = '';    
				}
				echo '<a class="wpvf-nav-tab'.$class.'" href="?page=wpvf_admin_settings&amp;tab=' . $tab . '">'.$props['title'].'</a>';
			}
		}
?>
		</h2>
		<div style="clear:both;"></div>
<?php
		

	if ($settingsPage->get_current_tab() == 'general') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_settings_general.php';
	}	
	elseif ($settingsPage->get_current_tab() == 'storing') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_settings_storing.php';
	}
	elseif ($settingsPage->get_current_tab() == 'privacy') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_settings_privacy.php';
	}
	elseif ($settingsPage->get_current_tab() == 'maintenance') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_settings_maintenance.php';
	}
	elseif ($settingsPage->get_current_tab() == 'logfile') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_settings_logfile.php';
	}

	$settingsPage->printFooter();
	
	