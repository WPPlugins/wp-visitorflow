<?php

if ( is_admin() ) {
	// Add admin menu hook
	add_action('admin_menu', 'wp_visitorflow_admin_menu');
	// Add dashboard widget
	add_action( 'wp_dashboard_setup', 'wp_visitorflow_dashboardWidget' );
	// Add plugin settings link to plugins list
	add_filter('plugin_action_links', 'wp_visitorflow_settings_link', 10, 2);
	// Translations
	add_action('init', 'wp_visitorflow_internationalization');
	
	// Add hook to posts table
	add_filter('manage_posts_columns' , 'posts_add_wp_visitor_column');
	add_action('manage_posts_custom_column' , 'posts_wp_visitor_column', 10, 2 );
	
	// Add hook to pages table
	add_filter( 'manage_pages_columns', 'pages_add_wp_visitor_column' );
	add_action('manage_pages_custom_column' , 'pages_wp_visitor_column', 10, 2 );

}

/**
 * Add column "WP VisitorFlow" to the manage Posts Screen
 */
function posts_add_wp_visitor_column($columns) {
    return array_merge( $columns, 
	  array('wp_visitorflow' => __('WP VisitorFlow')) 
	);
}
function posts_wp_visitor_column( $column, $post_id ) {
	if ($column == 'wp_visitorflow') {
		$url = admin_url( 'admin.php?page=wpvf_mode_singlepage&amp;select_post_id=' .  $post_id);
		echo '<a class="wpvf wpvfflow" href="' . $url . '">' . __('Flow', 'wp_visitorflow') . '</a>';
	}
}

/**
 * Add column "WP VisitorFlow" to the manage Pages Screen
 */
function pages_add_wp_visitor_column($columns) {
    return array_merge( $columns, 
	  array('wp_visitorflow' => __('WP VisitorFlow')) 
	);
}
function pages_wp_visitor_column( $column, $post_id ) {
	if ($column == 'wp_visitorflow') {
		$url = admin_url( 'admin.php?page=wpvf_mode_singlepage&amp;select_post_id=' .  $post_id);
		echo '<a class="wpvf wpvfflow" href="' . $url . '">' . __('Flow', 'wp_visitorflow') . '</a>';
	}
}


/**
 * Add plugin's settings to plugins list
 */
function wp_visitorflow_internationalization()
{
	load_plugin_textdomain('wp_visitorflow', false, dirname(plugin_basename( __FILE__ )) . '/../../languages/' );
}

/**
 * Add plugin's settings to plugins list
 */
function wp_visitorflow_settings_link($links, $file) {
    if ( $file == 'wp-visitorflow/wp-visitorflow.php' ) {
        /* Insert the link at the end*/
        $links['settings'] = sprintf( '<a href="%s"> %s </a>', admin_url( 'admin.php?page=wpvf_admin_settings' ), __( 'Settings', 'plugin_domain' ) );
    }
    return $links;
}


/**
 * Add Dashboard Widget
 **/
function wp_visitorflow_dashboardWidget() 
{
	if (! is_admin() ) { return FALSE; }
	
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();
	
	wp_visitorflow_update_db_info();
	
	$db_info = $WP_VisitorFlow->get_setting('db_info');
	include_once dirname( __FILE__ ) . '/wp_visitorflow_overview.php';
	wp_add_dashboard_widget( 'wpvf', 'WP VisitorFlow', 'wp_visitorflow_overview_summary', 'wp_visitorflow_overview' );
}

 
/**
 * Add primary admin menu
 */
function wp_visitorflow_admin_menu() {
	if (! is_admin() ) { return FALSE; }

	$WP_VisitorFlow = WP_VisitorFlow::getInstance();

	// Minimum reader capability (can be set in "general settings" section):
	$reader_cap = $WP_VisitorFlow->get_setting('read_access_capability');
	// Minimum admin capability (can be set in "general settings" section):
	$admin_cap = $WP_VisitorFlow->get_setting('admin_access_capability');
	
	// Add the top level menu.
	add_menu_page(__('VisitorFlow', 'wp_visitorflow'), __('VisitorFlow', 'wp_visitorflow'), $reader_cap, 'wpvf_menu', '__return_true', 'dashicons-randomize');

	include_once dirname( __FILE__ ) . '/../classes/wp_visitorflow-page-metaboxes.class.php';
	
	$thisPage = new WP_MetaboxPage('wpvf_menu',
									'WP VisitorFlow &ndash; ' . __('Overview','wp_visitorflow'),
									__('Detailed web analytics and visualization of your website\'s visitor flow.', 'wp_visitorflow'),
									__('Overview','wp_visitorflow'), 
									$reader_cap,
									'wpvf_menu',
									'wp_visitorflow_overview_addmetaboxes',
									'wp_visitorflow_overview_body_content'
									);
									
	add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Full Website Analytics', 'wp_visitorflow'), 	__('Full Website', 'wp_visitorflow'), 	$reader_cap,  'wpvf_mode_website',		'wp_visitorflow_mode_website');
	add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Single Page Analytics', 'wp_visitorflow'), 	__('Single Page', 'wp_visitorflow'), 	$reader_cap,  'wpvf_mode_singlepage',	'wp_visitorflow_mode_singlepage');
	add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Data Export', 'wp_visitorflow'), 				__('Data Export', 'wp_visitorflow'),   	$admin_cap,   'wpvf_admin_export',	    'wp_visitorflow_admin_export');
	add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Settings', 'wp_visitorflow'), 				__('Settings', 'wp_visitorflow'), 	   	$admin_cap,   'wpvf_admin_settings',	'wp_visitorflow_admin_settings');

	// Enqueue css file
	wp_enqueue_style('wpvf-css',  plugin_dir_url( __FILE__ )  . '../../assets/css/wp_visitorflow.css');	
	wp_enqueue_style('wpvf-bootstrap',  plugin_dir_url( __FILE__ )  . '../../assets/css/bootstrap_grid12.css');	
}


/**
 * Header for the Overview page
 **/
function wp_visitorflow_overview_body_content() {
	// No data yet? Print some information
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();
	$db_info = $WP_VisitorFlow->get_setting('db_info');
	if ($db_info['visits_count'] == 0) {
?>
	<br /><br />
	<div class="wpvf_warning">
		<?php 
		echo __('No data yet.', 'wp_visitorflow') . '<br />';
		echo ' <span style="font-weight:normal">' . __('This is probably due to a fresh installation.', 'wp_visitorflow') . '</span><br />';
		echo '<br />';
		echo '<span style="font-weight:normal">' . __('Please note that by default no visitor data is stored originating from admin pages or from administrators visiting your website. You can change this settings in the <a class="wpvf" href="?page=wpvf_admin_settings&tab=storing">settings section</a>.', 'wp_visitorflow') . '</span><br />';
		?>
	</div><br />
	<br /><br />
<?php
	}
	else {
?>
	<div style="text-align:right;">			
		<a href="//wordpress.org/support/view/plugin-reviews/wp-visitorflow" target="_blank" class="add-new-h2">
			<?php echo __('Rate us', 'wp_visitorflow'); ?> &#9733;&#9733;&#9733;&#9733;&#9733;
		</a>
		<a href="//wordpress.org/support/plugin/wp-visitorflow" target="_blank" class="add-new-h2">
			<?php echo __('Support', 'wp_visitorflow'); ?>             
		</a>
	</div>
<?php		
	}
}


/**
 * Add Metaboxes to Overview Page
 **/
function wp_visitorflow_overview_addmetaboxes() {
	
	$current_screen = get_current_screen();
	switch ( $current_screen->id) {
		case 'toplevel_page_wpvf_menu':
			include_once dirname( __FILE__ ) . '/wp_visitorflow_overview.php';
			wp_visitorflow_overview_content();
			break;
		default:
			add_meta_box('example1','Example 1','sh_example_metabox', get_current_screen(), 'side','high');
			add_meta_box('example2','Example 2','sh_example_metabox', get_current_screen(), 'advanced','high');
	}
}


/**
 * Page Modus "Website"
 **/
function wp_visitorflow_mode_website() {
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();
	
	// Load user settings
	$WP_VisitorFlow->load_user_settings();
	
	// Get DB object and table names from visitorflow class
	$db = $WP_VisitorFlow->get_DB();
	$visits_table = $WP_VisitorFlow->get_table_name('visits');
	$flow_table = $WP_VisitorFlow->get_table_name('flow');
	$pages_table = $WP_VisitorFlow->get_table_name('pages');
	$meta_table = $WP_VisitorFlow->get_table_name('meta');
	$aggregation_table = $WP_VisitorFlow->get_table_name('aggregation');

	// Minimum reader capability (can be set in "general settings" section):
	$reader_cap = $WP_VisitorFlow->get_setting('read_access_capability');
	$hit_count = 25;                                                                                                						
	
	// Include TimeframePage class and instantiate
	include_once dirname( __FILE__ ) . '/../classes/wp_visitorflow-page-timeframe.class.php';
	$timeframePage = new TimeframePage($reader_cap);
	
	// Start and end of the time interval selection (permanently stored in options)
	$datetimestart = $timeframePage->getTimeframeStart();
	$datetimestop = $timeframePage->getTimeframeStop();
	if ( ! $timeframePage->getTimeframeStart() || ! $timeframePage->getTimeframeStop() ) {
		$datetimestart = $WP_VisitorFlow->get_user_setting('datetimestart');
		$datetimestop  = $WP_VisitorFlow->get_user_setting('datetimestop');
		$timeframePage->setTimeframe($datetimestart, $datetimestop);								   
	}
	
	// Set Tabs
	$timeframePage->setTabs( array( 'flow' 		=> array( 'title' => __('Visitor Flow', 'wp_visitorflow'),		'min_role' => $reader_cap),
									'visitors'  => array( 'title' => __('Visitors', 'wp_visitorflow'),			'min_role' => $reader_cap),
									'referrers' => array( 'title' => __('Referrers', 'wp_visitorflow'),    	'min_role' => $reader_cap),
									'pages'   	=> array( 'title' => __('Visited Pages', 'wp_visitorflow'),   	'min_role' => $reader_cap),
									)
							);

	if ($timeframePage->get_current_tab() == 'visitors') {
		$timeframePage->printHeader( __('Website Visitors', 'wp_visitorflow'),
									 __('Distribution of remote client\'s browsers and operation systems and a detailed list of recent visitors', 'wp_visitorflow') );
		include_once dirname( __FILE__ ) . '/wp_visitorflow_website_visitors.php';
	}
	elseif ($timeframePage->get_current_tab() == 'referrers') {
		$timeframePage->printHeader( __('Website Referrers', 'wp_visitorflow'),
									 __('The top referrers to your website.', 'wp_visitorflow') );
		include_once dirname( __FILE__ ) . '/wp_visitorflow_website_referrers.php';
	}
	elseif ($timeframePage->get_current_tab() == 'pages') {
		$timeframePage->printHeader( __('Website Pages', 'wp_visitorflow'),
									 __('The most visited pages on your website.', 'wp_visitorflow') );
		include_once dirname( __FILE__ ) . '/wp_visitorflow_website_pages.php';
	}
	else {
		$timeframePage->printHeader( __('Website Visitor Flow', 'wp_visitorflow'),
									 __('The total visitor flow: step-by-step diagramm of the visitors interactions with your website', 'wp_visitorflow') );
		include_once dirname( __FILE__ ) . '/wp_visitorflow_flow-per-step.php';
	}
	
	if ($WP_VisitorFlow->get_user_setting('datetimestart') !=  $timeframePage->getTimeframeStart() ) {
		$WP_VisitorFlow->set_user_setting('datetimestart', $timeframePage->getTimeframeStart(), 0);
	}
	if ($WP_VisitorFlow->get_user_setting('datetimestop')  !=  $timeframePage->getTimeframeStop() ) {
		$WP_VisitorFlow->set_user_setting('datetimestop',  $timeframePage->getTimeframeStop(), 0);
	}
	$WP_VisitorFlow->save_user_settings();

	$timeframePage->printFooter();
}


/**
 * Page Modus "Single Page"
 **/
function wp_visitorflow_mode_singlepage() {
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();
	
	// Load user settings
	$WP_VisitorFlow->load_user_settings();
	
	// Get DB object and table names from visitorflow class
	$db = $WP_VisitorFlow->get_DB();
	$visits_table = $WP_VisitorFlow->get_table_name('visits');
	$flow_table = $WP_VisitorFlow->get_table_name('flow');
	$pages_table = $WP_VisitorFlow->get_table_name('pages');
	$meta_table = $WP_VisitorFlow->get_table_name('meta');
	$aggregation_table = $WP_VisitorFlow->get_table_name('aggregation');

	// Minimum reader capability (can be set in "general settings" section):
	$reader_cap = $WP_VisitorFlow->get_setting('read_access_capability');
	$hit_count = 20;                                                                                                						
	
	// Include TimeframePage class and instantiate
	include_once dirname( __FILE__ ) . '/../classes/wp_visitorflow-page-timeframe.class.php';
	$timeframePage = new TimeframePage($reader_cap);
	
	// Start and end of the time interval selection (permanently stored in options)
	$datetimestart = $timeframePage->getTimeframeStart();
	$datetimestop = $timeframePage->getTimeframeStop();
	if ( ! $timeframePage->getTimeframeStart() || ! $timeframePage->getTimeframeStop() ) {
		$datetimestart = $WP_VisitorFlow->get_user_setting('datetimestart');
		$datetimestop  = $WP_VisitorFlow->get_user_setting('datetimestop');
		$timeframePage->setTimeframe($datetimestart, $datetimestop);								   
	}
	
	// Get selected page:
	$selected_page_id = 3;
	$selected_title =  '';
	$selected_post_id =  '';
	$error_message = '';
	if (array_key_exists('select_page_id', $_GET)) {
		$selected_page_id = htmlspecialchars( stripslashes( $_GET['select_page_id'] ) );
		
		// Check if page exists:
		$result = $db->get_row($db->prepare("SELECT title, f_post_id 
											 FROM $pages_table
											 WHERE id='%s' LIMIT 1;",
											 $selected_page_id)
								  );
		if (isset($result->title)) {
			$selected_title =  html_entity_decode( $result->title );
			$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
			$selected_post_id =  $result->f_post_id;
		}
	}
	
	// Or is post id queried?
	elseif (array_key_exists('select_post_id', $_GET)) {
		$selected_post_id = htmlspecialchars( stripslashes( $_GET['select_post_id'] ) );
		$result = $db->get_row($db->prepare(
			"SELECT id, title
			   FROM $pages_table
			  WHERE f_post_id='%s' LIMIT 1;",
			  $selected_post_id
		));
		
		if (isset($result->id)) {
			$selected_title =  html_entity_decode( $result->title );
			$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
			$selected_page_id =  $result->id;
		}
		else {
			$error_message = '<br /><div class="wpvf_warning">';
			$error_message .= '<p>' . __('Selected post/page does not exist or has not been visited yet.', 'wp_visitorflow') . '<p>';
			$error_message .= '</div><br />';
		}
	}
	
	
	//No entry found? Get the primary page (id=3)
	if (! $selected_title) {
		$selected_page_id = 3;
		$result = $db->get_row("SELECT title, f_post_id  
									FROM $pages_table
									WHERE id=3 LIMIT 1;");
		$selected_title =  html_entity_decode( $result->title );
		$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
		$selected_post_id =  $result->f_post_id;
	}
	
	$WP_VisitorFlow->set_user_setting('selected_page_id', $selected_page_id);
	
	// Set Tabs
	$timeframePage->setTabs( array( 'flow' 		=> array( 'title' => __('Visitor Flow', 'wp_visitorflow'),	'min_role' => $reader_cap),
									'timeline'  => array( 'title' => __('Timeline', 'wp_visitorflow'),		'min_role' => $reader_cap),
								   )
							);
	
	$pagelink = $selected_title;
	if ($selected_post_id == -1) {
		$pagelink = '<a class="wpvfpage" href="' . site_url() . $selected_title . '">' . $selected_title . '</a> (404 error)';
	}
	elseif ($selected_post_id > 0) {
		$pagelink = '<a class="wpvfpage" href="' . site_url() . '?p=' . $selected_post_id . '">' . $selected_title . '</a>';
	}
	
	if ($timeframePage->get_current_tab() == 'timeline') {
		$timeframePage->printHeader( __('Single Page Timeline Plot', 'wp_visitorflow'),
									 sprintf(__('Page views for page <strong>%s</strong>', 'wp_visitorflow'), $pagelink) );
		echo $error_message;
		include_once dirname( __FILE__ ) . '/wp_visitorflow_singlepage_timeline.php';
	}
	else {
		$timeframePage->printHeader( __('Single Page Visitor Flow', 'wp_visitorflow'),
									 sprintf(__('Visitor Flow towards and from page <strong>%s</strong>', 'wp_visitorflow'), $pagelink) );
		echo $error_message;
		include_once dirname( __FILE__ ) . '/wp_visitorflow_flow-per-page.php';
	}
	
	if ($WP_VisitorFlow->get_user_setting('datetimestart') !=  $timeframePage->getTimeframeStart() ) {
		$WP_VisitorFlow->set_user_setting('datetimestart', $timeframePage->getTimeframeStart(), 0);
	}
	if ($WP_VisitorFlow->get_user_setting('datetimestop')  !=  $timeframePage->getTimeframeStop() ) {
		$WP_VisitorFlow->set_user_setting('datetimestop',  $timeframePage->getTimeframeStop(), 0);
	}
	$WP_VisitorFlow->save_user_settings();
	
	$timeframePage->printFooter();
}


/**
 * Page Modus "Data Export"
 **/
function wp_visitorflow_admin_export() {
	include_once dirname( __FILE__ ) . '/wp_visitorflow_export.php';
}

/**
 * Page Modus "Settings"
 **/
function wp_visitorflow_admin_settings() {
	include_once dirname( __FILE__ ) . '/wp_visitorflow_settings.php';
}


