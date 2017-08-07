<?php
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();	

	if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
		
	$admin_tabs = array( 'table'  	 => array( 'title' => __('Data Export', 'wp_visitorflow'),		'min_role' => 'moderate_comments'),
						 'app'  	 => array( 'title' => __('App', 'wp_visitorflow'), 				'min_role' => 'moderate_comments'),
						);  

	include_once dirname( __FILE__ ) . '/../classes/wp_visitorflow-page.class.php';
	
	$exportPage = new WP_VisitorFlow_Page($WP_VisitorFlow->get_setting('admin_access_capability'), FALSE, $admin_tabs);

	// Print Page Header
?>
	<div class="wrap">
		<div style="float:left;">
			<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/images/Logo_250.png'; ?>" align="left" width="80" height="80" alt="Logo" />
		</div>
		<h1>WP VisitorFlow &ndash; <?php echo __('Data Export', 'wp_visitorflow') ?></h1>
		<p><?php echo __('Export recorded data to csv tables or to the WP VisitorFlow app.', 'wp_visitorflow'); ?></p>
		<div style="clear:both;"></div>
<?php	
	
	// Print Tab Menu
?>
		<div style="clear:both;"></div>
		<h2 class="wpvf-nav-tab-wrapper">
<?php
		foreach ($admin_tabs as $tab => $props) {
			if (current_user_can($props['min_role']) ) {
				if ($exportPage->get_current_tab() == $tab){
					$class = ' wpvf-nav-tab-active';
				} 
				else {
					$class = '';    
				}
				echo '<a class="wpvf-nav-tab'.$class.'" href="?page=wpvf_admin_export&amp;tab=' . $tab . '">'.$props['title'].'</a>';
			}
		}
?>
		</h2>
		<div style="clear:both;"></div>
<?php
		

	if ($exportPage->get_current_tab() == 'table') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_export_table.php';
	}	
	elseif ($exportPage->get_current_tab() == 'app') {
		include_once dirname( __FILE__ ) . '/wp_visitorflow_export_app.php';
	}
	
	