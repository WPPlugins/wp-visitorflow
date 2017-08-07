<?php
/**
 *	This is the primary class for WP VisitorFlow.
 *
 *	This class records visits, pages and hits and keeps the options/settings
 **/

// Include Piwik/DeviceDetector library
require_once dirname( __FILE__ ) . '/../../vendor/autoload.php';
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
 
if (! class_exists("WP_VisitorFlow")) :	// Prevent multiple class definitions

class WP_VisitorFlow 
{
	
	// Class variables
	private static $instance = NULL;
	
	protected $db;						// WordPress database object
	protected $table_name;				// Hash array with db table names

	protected $settings;				// Plugin's general options/settings
	protected $user_settings;		    // User's options/settings
	protected $user_id = 0;				// ID of current Wordpress user
	
	protected $ip;						// IP adress of the remote client	
	protected $client;   	    		// Details about the remote vlient
	protected $datetime = 0;	  		// current date and time

	protected $exclusion = FALSE;		// exclusion == true => do not record visit/page
	protected $exclusion_reason;		// reason for exclusion 

	/**
	 * Constructor
	 **/
	protected function __construct() {
		
		global $wpdb;
		
		// Set Wordpress database object
		$this->db = $wpdb;
		
		// Set database table names
		$this->table_name = array('visits'       => $this->db->prefix . "visitorflow_visits",
								  'pages'        => $this->db->prefix . "visitorflow_pages",
								  'flow'         => $this->db->prefix . "visitorflow_flow",
								  'meta'         => $this->db->prefix . "visitorflow_meta",
								  'aggregation'  => $this->db->prefix . "visitorflow_aggregation");

								  $WPVF_installed_version = get_option('wp_visitorflow_plugin_version');
		
		//Set current datetime
		$this->datetime = new DateTime();
		
		// Load plugin's general options
		$this->load_settings();
	}
	
	/**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }
	
	
	/**
	 * Load plugin's general settings 
	 **/
	public function load_settings() {
		$this->settings = get_option( 'wp_visitorflow' );
		
		// If settings do not exist, load and store default values:
		if (! is_array($this->settings) ) { 
			$this->settings = $this->get_default_settings();
			$this->save_settings();
		}
	}
	
	/**
	 * Save plugin's general settings 
	 **/
	public function save_settings() {
		update_option('wp_visitorflow', $this->settings);
	}
	
	/**
	 * Get plugin's default of general settings
	 **/
	public function get_default_settings($key = FALSE) {
		$defaults =  array('record_visitorflow' => TRUE,
						   'record_action_hook' => 'shutdown',
						   'read_access_capability' => 'manage_options',
						   'admin_access_capability' => 'manage_options',
						   'encrypt_ips' => TRUE,
						   'store_useragent' => FALSE,
						   'minimum_time_between' => 60,
						   'flowdata_storage_time' => 365,
						   'exclude_unknown_useragents' => TRUE,
						   'exclude_bots' => TRUE,
						   'exclude_404' => FALSE,
						   'crawlers_exclude_list' => array('Java/1.',
															'libwww-perl', 
															'Netcraft', 
															'crawler', 
															'spider'),
						   'pages_exclude_list' => array('/wp-admin', 
														 '/wp-login', 
														 '/xmlrpc.php',
														 '/wp-trackback.php'),
						   'db-info-timestamp' => time(),
						   'db-info' => array(),
						   'last_dbclean_date' => 0,
						   'last_aggregation_date' => 0,
						   'data_aggregation_running' => FALSE,
						   'enable_app_access' => FALSE,
						   'app_granted_tokens' => array(),
						   'app_token_length' => 32
		                   );

		if ($key) {
			if (array_key_exists($key, $defaults)) {
				return $defaults[$key]; 
			}
			else {
				return FALSE;
			}
		}
		else { return $defaults; }
	}
	
	/**
	 * Get single setting 
	 **/
	public function get_setting($key, $default = NULL) {
		$defaults = $this->get_default_settings();
		
		// Setting value not set => use default value (if any exist) or return FALSE
		if (! array_key_exists($key, $this->settings) ) {
			if (isset($defaults[$key])) { return $defaults[$key];} 
			elseif ($default) { return $default; } 
			else { return FALSE;	}
		}
		
		// Return the setting
		return $this->settings[$key];
	}
		
	/**
	 * Set single setting and (optional) save all settings
	 **/
	public function set_setting($key, $value, $save = FALSE) {
		$this->settings[$key] = $value;
		// Save array in WP options:
		if ($save) { $this->save_settings(); }
	}
	
	/**
	 * Load user settings 
	 **/
	public function load_user_settings() {
		if( $this->user_id == 0 ) {
			$this->user_id = get_current_user_id();
		}
		if (! $this->user_id) { return FALSE; }

		$this->user_settings = get_user_meta( $this->user_id, 'wp_visitorflow', true );
		
		// If user settings do not exist, load and store default values:
		if (! is_array($this->user_settings) ) { 
			$this->user_settings = $this->get_default_user_settings();
			$this->save_User_settings();
		}
	}
	
	/**
	 * Save user settings 
	 **/
	public function save_user_settings() {
		if (! $this->user_id) { return FALSE; }

		update_user_meta( $this->user_id, 'wp_visitorflow', $this->user_settings );
	}
	
	/**
	 * Get default user settings
	 **/
	public function get_default_user_settings($key = FALSE) {
		$defaults =  array('datetimestart' => date( 'Y-m-d' ),
						   'datetimestop' => date( 'Y-m-d' ),
						   'selected_page_id' 	 => 1,
						   'flowchart_dimension' => 2,
						   'flowchart_max_nodes' => 10,
						   'flowchart_start_type' => 'step',
						   'flowchart_start_step' => 0,
						   'flowchart_min_step' => 0,
						   'flowchart_max_step' => 3,
						   'flowchart_filter_data' => 0,
						   'flowchart_filter_data_selected' => array(),
						   'flowchart_distance_between_steps' => 550,
		                   );

		if ($key) {
			if (array_key_exists($key, $defaults)) {
				return $defaults[$key]; 
			}
			else {
				return FALSE;
			}
		}
		else { return $defaults; }
	}
	
	/**
	 * Get single user setting 
	 **/
	public function get_user_setting($key, $default = NULL) {
		$defaults = $this->get_default_user_settings();
		
		// Setting value not set => use default value, if any exist, or return false.
		if (! array_key_exists($key, $this->user_settings) ) {
			if (isset($defaults[$key])) { return $defaults[$key];} 
			elseif (isset($default))    { return $default; } 
			else 						{ return FALSE;	}
		}
		
		// Return the setting.
		return $this->user_settings[$key];
	}
		
	/**
	 * Set single user setting and (optional) save all user settings
	 **/
	public function set_user_setting($key, $value, $save = FALSE) {
		$this->user_settings[$key] = $value;
		// Save array in WP options:
		if ($save) { $this->save_user_settings(); }
	}	
		
	/**
	 * Get DB table name
	 **/
	public function get_table_name($name) {
		if (! array_key_exists($name, $this->table_name)) { return FALSE; }
		return $this->table_name[$name];
	}
	
	/**
	 * Get DB object
	 **/
	public function get_DB() {
		return $this->db;
	}
	
	
	/**
	 * Perfrom data aggregation
	 **/
	public function data_aggregation() {

		// Aggregation already running?
		if ($this->get_setting('data_aggregation_running')) {
			// Running aggregation process younger than one hour? => wait for it.
			if (time() - $this->get_setting('data_aggregation_running') < 60*60) {
				return FALSE;
			}
		}
		$this->set_setting('data_aggregation_running', time(), TRUE);
			
		// Get aggregation date
		if ($this->get_setting('last_aggregation_date')) {
			$aggregation_date = new DateTime( $this->get_setting('last_aggregation_date') );
			$aggregation_date->modify('+1 day');
		}
		else {
			$flow_startdatetime = new DateTime( $this->get_setting('flow-startdatetime') );
			$aggregation_date = new Datetime( $flow_startdatetime->format('Y-m-d') );
		}

		// Aggregation date younger than yesterday (=today or later)? If yes, return without any data aggregation 
		$yesterday = new DateTime( $this->get_datetime('Y-m-d') );
		$yesterday->modify('-1 day');
		if ($aggregation_date > $yesterday) {
			return FALSE;
		}

		// Do data aggregation		
		$this->data_aggregation_perday( $aggregation_date->format('Y-m-d') );
		
		// Save settings
		$this->set_setting('data_aggregation_running', FALSE);
		$this->set_setting('last_aggregation_date', $aggregation_date->format('Y-m-d'));
		$this->save_settings();
		return TRUE;
	}
	
	
	/**
	 * Perform data aggregation for day $date
	 **/
	public function data_aggregation_perday($date = FALSE) {
		if (! $date) { return 0; }
		
		$data = $this->get_data( $date );
		
		foreach ($data as $key => $value) {
			$this->db->replace( $this->get_table_name('aggregation'), 
								array('type' => $key,
									  'date' => $date, 
									  'value' => $value), 
								array('%s', '%s', '%d') 
							  );		
		
		}
		
		$this->store_Meta('log', 'aggregat', 'Data aggregated for date ' . $date);
		
		return 1;
	}
	
	/**
	 * Get data from database for date $date
	 **/
	public function get_data($date = FALSE) {
		if (! $date) { 
			$date = $this->datetime->format('Y-m-d'); 
		}
		
		$visits_table = $this->get_table_name('visits');
		$flow_table = $this->get_table_name('flow');

		$data = array();
		
		// Count Referrers
		$results = $this->db->get_results($this->db->prepare("SELECT f_page_id, COUNT(*) AS count
															FROM $flow_table
															WHERE datetime>='%s'
															  AND datetime<adddate('%s', interval 1 day)
															  AND step='1'
															GROUP BY f_page_id;",
															$date, $date
														   )
										);
		foreach ($results as $res) {
			$data['refer-' . $res->f_page_id] = $res->count;
		}

		// Count Page Views
		$results = $this->db->get_results($this->db->prepare("SELECT f_page_id, COUNT(*) AS count
															FROM $flow_table
															WHERE datetime>='%s'
															  AND datetime<adddate('%s', interval 1 day)
															  AND step>'1'
															GROUP BY f_page_id;",
															$date, $date
														   )
										);
		$total_view_count = 0;
		foreach ($results as $res) {
			$data['views-' . $res->f_page_id] = $res->count;
			$total_view_count +=  $res->count;
		}
		$data['views-all'] = $total_view_count;
		
		// Count Visits
		$visits = $this->db->get_var($this->db->prepare("SELECT COUNT(id)
														FROM $visits_table
														WHERE last_visit>='%s'
														  AND last_visit<adddate('%s', interval 1 day);",
														$date, $date
													   )
										);
		$data['visits'] = $visits;
		
		return $data;
	}
	

	/**
	 * Post update (called after plugin's install or update)
	 **/
	public function post_update($old_version = FALSE, $new_version = FALSE) {
	
		if ($old_version && $new_version) {
			$this->store_Meta('log', 'newversion', 'Update from version ' . $old_version . ' to version ' . $new_version);
		}

		$pages_table = $this->get_table_name('pages');
		
		$message = '';
		
		// Initialize "Pages" table with three standard pages ('unknown', 'self' and startpage of WP site)
		$result = $this->db->get_row("SELECT id FROM $pages_table WHERE f_post_id='0' AND title='unknown';");
		if (! isset($result->id)) {
			$res = $this->db->insert($pages_table,
									array( 'id'   		=> 1,
										   'internal'   => 0,
										   'f_post_id'  => 0,
										   'title'		=> 'unknown'
										  ),
									array('%d', '%d', '%d', '%s')
							);
			$message .= __('Initial page "unknown" added to pages table', 'wp_visitorflow') . '<br />';
		}
		
		$result = $this->db->get_row("SELECT id FROM $pages_table WHERE f_post_id='0' AND title='self';");
		if (! isset($result->id)) {
			$res = $this->db->insert($pages_table,
									array( 'id'   		=> 2,
										   'internal'   => 1,
										   'f_post_id'  => 0,
										   'title'		=> 'self'
										  ),
									array('%d', '%d', '%d', '%s')
							);
			$message .= __('Initial page "self" added to pages table', 'wp_visitorflow') . '<br />';
		}
		
		$frontpage_id = get_option('page_on_front');
		$result = $this->db->get_row("SELECT id FROM $pages_table WHERE f_post_id='" . $frontpage_id . "';");
		if (! isset($result->id)) {
			$res = $this->db->replace($pages_table,
									array( 'id'   		=> 3,
										   'internal'   => 1,
										   'f_post_id'  => $frontpage_id,
										   'title'		=> get_the_title( $frontpage_id )
										  ),
									array('%d', '%d', '%d', '%s')
							);
			$message .= __('Front page added to pages table', 'wp_visitorflow') . '<br />';
		}
		
		if ($message) {
			$this->store_Meta('log', 'initpages', $message);
		}
		
		$message .= $this->set_startdatetimes();

		return $message;
	}
		
	/**
	 * Find and set start datetimes for overall databe and for flow data table
	 **/
	public function set_startdatetimes() {
		$flow_table = $this->get_table_name('flow');
		$aggregation_table = $this->get_table_name('aggregation');
		
		$message = '';
			
		// Set flow data start date/time (= mininum datetime in flow table)
		$new_startdatetime = $this->db->get_var( "SELECT MIN(datetime) FROM $flow_table;" );
		if (! $new_startdatetime) { 
			$new_startdatetime = $this->get_datetime();
		}
		if ($new_startdatetime != $this->get_setting('flow-startdatetime')) {
			$this->set_setting('flow-startdatetime', $new_startdatetime, 1);
			$message .= sprintf( __('New flow data start date = %s.', 'wp_visitorflow'), 
								 date_i18n( get_option( 'date_format' ), strtotime($new_startdatetime) ) ) . '<br />';
		}
	
		// Set overall db data start date/time (= mininum date in aggregation table)
		$new_db_startdatetime = $this->db->get_var( "SELECT MIN(date) FROM $aggregation_table;" );
		if (! $new_db_startdatetime) { 
			$new_db_startdatetime = $this->get_setting('flow-startdatetime');
		}
		if ($new_db_startdatetime != $this->get_setting('db-startdatetime')) {
			$this->set_setting('db-startdatetime', $new_db_startdatetime, 1);
			$message .= sprintf( __('New db data start date = %s.', 'wp_visitorflow'), 
								 date_i18n( get_option( 'date_format' ), strtotime($new_db_startdatetime) ) ) . '<br />';
		}
		
		// counters-startdatetime set?
		if (! $this->get_setting('counters-startdatetime')) {
			$this->set_setting('counters-startdatetime', $new_db_startdatetime, 1);
		}
		
		if ($message) {
			$this->store_Meta('log', 'setstart', $message);
		}
		
		return $message;
	}
	
	
	/**
	 * Set IP address of the remote client.
	 **/
	private function set_IP() {
		// Standard value
		$ip = $_SERVER['REMOTE_ADDR'];
	
		// If CLIENT_IP or FORWARDED is set, use this value
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$p = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		} 

		// Remove port (if any exists)
		if( strstr( $ip, ':' ) !== FALSE ) {
			$parts = explode(':', $ip);
			$ip = $parts[0];
		}
		
		// Valid IP address? 
		$long = ip2long($ip);
		if ($long == -1 || $long === FALSE) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		// Encrypt IP address?
		if ($this->get_setting('encrypt_ips') == TRUE) {
			$ip = sha1( $ip ); 
		}
		
		// Set class variable
		$this->ip = $ip;
	}

	
	/**
	 * Set details of remote client from the submitted HTTP User Agent String
	 **/
	private function set_Client($ua_string = FALSE) {
		if (! $ua_string) { return; }
		
		$client = array();
		
		// Parse UA String using Device Detector
		$dd = new DeviceDetector($ua_string);
		
		// Use a cache to increase performance
		$dd->setCache( new Doctrine\Common\Cache\PhpFileCache( dirname( __FILE__ ) . '/../../cache/' ) );
		
		// Parse the UA String
		$dd->parse();

		// Bot/Crawler detected
		if ($dd->isBot()) {		
			$client['bot'] = TRUE;
			$botInfo = $dd->getBot(); // ['name', 'category', 'url', 'producer' => ['name', 'url'] ]
			$client['bot_name'] = $botInfo['name'] ? $botInfo['name'] : NULL;
		}
		// Browser detected
		else {
			$client['bot'] = FALSE;
			// Get Agent and OS info
			$clientInfo = $dd->getClient(); // ['type', 'name', 'short_name', 'version', 'engine']
			$osInfo = $dd->getOs();         // ['name', 'short_name', 'version', 'platform'] 
			$client['agent_name'] 	 = isset($clientInfo['name']) && $clientInfo['name'] ? $clientInfo['name'] : NULL;
			$client['agent_version'] = isset($clientInfo['version']) && $clientInfo['version'] ? $clientInfo['version'] : NULL;
			$client['agent_engine']  = isset($clientInfo['engine']) && $clientInfo['engine'] ? $clientInfo['engine'] : NULL;
			$client['os_name'] 	 	 = isset($osInfo['name']) && $osInfo['name'] ? $osInfo['name'] : NULL;
			$client['os_version'] 	 = isset($osInfo['version']) && $osInfo['version'] ? $osInfo['version'] : NULL;
			$client['os_platform'] 	 = isset($osInfo['platform']) && $osInfo['platform'] ? $osInfo['platform'] : NULL;
		}
		
		$this->client = $client;
	}

	
	/**
	 * Get remote client's details
	 **/
	public function get_Client($key = false) {
		if ($key) {
			return $this->client[$key];
		}
		return $this->client;
	}
	
		
	/**
	 * Get current date/time
	 **/
	public function get_datetime($format = 'Y-m-d H:i:s') {
		return $this->datetime->format($format) ;
	}
	
	
	/**
	 * Get IP adress from remote client
	 **/
	public function get_IP() {
		return $this->ip;
	}
		
	
	/**
     * Get page details from WordPress API or from page URI
	 *
	 * Case 1: page is an external website:
	 * returns array['internal' => '0',
     *				 'post_id'  => '0',
	 *				 'title'    => page URI ]
	 *
	 * Case 2: page is an internal website:
	 * returns array['internal' => '1',
     *				 'post_id'  => '-1' - if resulted in a 404 error
								   '0' - if not a WP post or page 
	 *							   'post_id' - if a WP post or page
	 *				 'title'    => post/page title
	 *                             or the page URI ]
	 */
	private function get_page_info( $current_page = FALSE ) {
		$page_info = array('internal' => 0, 'post_id' => 0, 'title' => '');
		
		$page_uri = 0;
		$internal_post = 0;
		
		// Get page_uri from current page 
		if ($current_page) {
			$page_uri = 'http';
			if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') { $page_uri .= 's'; }
			$page_uri .= '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$internal_post = (is_single() or is_page()) ? 1 : 0;
		}
		// Get page_uri from referrer
		else {
			if ( isset($_SERVER['HTTP_REFERER']) ) { 
				$page_uri = esc_sql( strip_tags( $_SERVER['HTTP_REFERER'] ) ); 
			}
		}
		
		// No page_uri? return error -1
		if (! $page_uri || ! is_string($page_uri) ) { 
			$page_info['internal'] = -1;
			return $page_info;
		}
		
		// We are on an internal WP post or page
		if ($internal_post) {
			$page_info['internal'] = 1;
			$page_info['post_id'] = get_queried_object_id();
			$page_info['title'] = get_the_title();
			return $page_info;
		}
		// Are we on a 404 error page?
		if ( is_404() ) {
			$page_info['post_id'] = -1;
		}

		$site_url = site_url();
		// URL of an internal WP page? Strip the site's path from URL
		if (strlen($page_uri) >= strlen($site_url) && is_string($site_url) && strlen($site_url) > 0 ) {
			if (substr($page_uri, 0, strlen($site_url)) === $site_url) {
				$page_info['internal'] = 1;
				// Strip the site's path from the URI.
				$page_uri = str_ireplace( $site_url, '', $page_uri );
				// double "//" to "/"
				$page_uri = str_replace("//", "/", $page_uri);
			}
		}
		
		// Strip queries from URL
		$parts = explode( '?', $page_uri );
		if (count($parts) > 1) { $page_uri = $parts[0]; }
		
		// Remove last '/' or '\' at the end (if any)
		$page_uri = rtrim($page_uri , '/\\');
		 
		// No uri left => use '/' as uri
		if (! $page_uri) {
			$page_uri = '/';
		}
			
		$page_info['title'] = $page_uri;
		
		return $page_info;
	}
	
	
	/**
	 * Save visit information
	 **/
	public function record_Visit() {

		// Get HTTP User Agent String
		$ua_string = 0;
		if( array_key_exists('HTTP_USER_AGENT', $_SERVER) ) {
			$ua_string = $_SERVER['HTTP_USER_AGENT'];
		}
		elseif ( $this->get_setting('exclude_unknown_useragents') ) {
			$this->increase_Meta('count uastring', 'unknown');
			$this->exclusion = TRUE;
			$this->exclusion_reason = "unknown user agent";
			return FALSE;
		}
		
		// Self-referrer?
		if ($ua_string) {
			global $wp_version;
			if (    $ua_string == "WordPress/" . $wp_version . "; " . get_home_url("/") 
				||  $ua_string == "WordPress/" . $wp_version . "; " . get_home_url() 
		       ) { 
				$this->increase_Meta('count exclusion', 'self-referrer');
				$this->exclusion = TRUE; 
				$this->exclusion_reason = "self-referrer"; 
				return FALSE;
			}
		}
		
		// 404 error page?
		if( is_404() ) { 
			if ( $this->get_setting('exclude_404') ) {
				$this->increase_Meta('count exclusion', '404');
				$this->exclusion = TRUE; 
				$this->exclusion_reason = "404";
				return FALSE;
			}
		}
		
		// Parse HTTP User Agent String by Device Detector
		$this->set_Client($ua_string);
		
		// Bot detected?
		if ($this->client['bot'] == TRUE && $this->get_setting('exclude_bots')) {
			$this->increase_Meta('count bot', $this->client['bot_name']);
			$this->exclusion = TRUE;
			$this->exclusion_reason = "bot";
			return FALSE;
		}
		
		// Track also unknown/hidden user agents?
		if ( (! is_array($this->client) || ! array_key_exists('agent_name',  $this->client) || ! $this->client['agent_name']) 
			&& $this->get_setting('exclude_unknown_useragents') ) {
			$this->increase_Meta('count uastring', 'unknown');
			$this->exclusion = TRUE;
			$this->exclusion_reason = "unknown user agent";
			return FALSE;
		}
			
		
		// Check if user role is included to the statistic recording
		if (is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			
			if (count($current_user->roles) > 0) { // Check - but only if logged-in user has at least one role
				$included = FALSE;
				foreach( $current_user->roles as $role ) {
					$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
					if( $this->get_setting($setting_key) == TRUE ) {
						$included = TRUE;
					}
				}
				if (! $included) {
					$this->exclusion = TRUE;
					$this->exclusion_reason = "user role";
					return FALSE;
				}
			}
		}
		
		// Check if the HTTP User Agent String contains a string from the crawlers exclusion list
		if ($ua_string) {
			foreach ($this->get_setting('crawlers_exclude_list') as $crawler_string) {
				if ( preg_match('/' . preg_quote(strtolower($crawler_string), '/') . '/', strtolower($ua_string)) ) {
					$this->increase_Meta('count uastring', $crawler_string);
					$this->exclusion = TRUE;
					$this->exclusion_reason = "uastring";
					return FALSE;
				}
			}
		}
		
		// Check if the page contains a string from the pages exclusion list
		if (array_key_exists('REQUEST_URI', $_SERVER) && $_SERVER['REQUEST_URI']) {
			$page = $_SERVER['REQUEST_URI'];
			foreach ($this->get_setting('pages_exclude_list') as $page_string) {
				if ( preg_match('/' . preg_quote(strtolower($page_string), '/') . '/', strtolower($page)) ) {
					$this->increase_Meta('count pagestring', $page_string);
					$this->exclusion = TRUE;
					$this->exclusion_reason = "pagestring";
					return FALSE;
				}
			}
		}	
		
		// Set the IP adress of the remote client
		$this->set_IP();
		
		// Check if visitor was already registered before:
		$sql_ands = '';
		$sql_params = array();
		array_push($sql_params, $this->get_datetime());
		array_push($sql_params, $this->get_setting('minimum_time_between'));
		$fields = array('agent_name', 'agent_version', 'agent_engine', 'os_name', 'os_version', 'os_platform');
		foreach ($fields as $field) {
			if ($this->get_Client($field)) {
				$sql_ands .= " AND $field='%s'";
				array_push($sql_params, $this->get_Client($field));
			}
			else {
				$sql_ands .= " AND $field IS NULL";
			}
		}
		$sql_ands .= " AND ip='%s'";
		array_push($sql_params, $this->get_IP());
		
		$sql = $this->db->prepare('SELECT id FROM ' . $this->table_name['visits'] . "
								   WHERE last_visit>=date_sub('%s', interval '%d' minute)
								   $sql_ands
								   LIMIT 1;", 
								   $sql_params);

		$visit = $this->db->get_row( $sql );
									
		// New or old visitor?
		$new_visitor = TRUE;
		$visit_id  = 0;
		if (isset($visit->id)) {
			$new_visitor = FALSE;
			$visit_id = $visit->id;
		}

		/**************************************************************************************
		 * All checks done: Save visitor, page and flow to database
		 **************************************************************************************/
		
		// New Visitor => store visit, referrer and visited page 
		if ($new_visitor) {

			// Store Visitor
			$res = $this->db->insert($this->table_name['visits'],
									 array( 'last_visit' 	=> $this->get_datetime(),
											'agent_name' 	=> $this->get_Client('agent_name'),
											'agent_version' => $this->get_Client('agent_version'),
											'agent_engine' 	=> $this->get_Client('agent_engine'),
											'os_name' 		=> $this->get_Client('os_name'),
											'os_version'	=> $this->get_Client('os_version'),
											'os_platform' 	=> $this->get_Client('os_platform'),
											'ip' 		 	=> $this->get_IP()
										  ),
									 array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
									);
			$visit_id = $this->db->insert_id;
			
			// Get referrer page info
			$referer_info = $this->get_page_info(0);	
			if ($referer_info['internal'] == -1) {		// no referrer => unknown source
				$referer_info['internal'] = 0;
				$referer_info['title'] = 'unknown'; 
			}
			elseif ($referer_info['internal'] == 0) { 	// no referrer  => unknown source
				if ($referer_info['title'] == '/') {
					$referer_info['title'] = 'unknown'; 
				}
			}
			elseif ($referer_info['internal'] == 1) {	// referrer, but from own website => self referrer
				$referer_info['title'] = 'self'; 		// (this is the case when the time between two visits is longer than 'minimum_time_between')
			} 
			
			
			// Get visited page info
			$page_info = $this->get_page_info(1);	
			if ($page_info['internal'] == 1) {
				//Store referer
				$referer_page_id = $this->store_Page($referer_info['internal'], $referer_info['title'], $referer_info['post_id']);
				$referer_flow_id = $this->store_Flow($visit_id, $referer_page_id);
				
				//Store page
				$page_id = $this->store_Page($page_info['internal'], $page_info['title'], $page_info['post_id']);
				$flow_id = $this->store_Flow($visit_id, $page_id);

				// Store search enginge keywords (if any exists)
				if (array_key_exists('HTTP_REFERER', $_SERVER) ) {
					$this->store_SEKeywords($_SERVER['HTTP_REFERER'], $referer_page_id, $page_id);
				}
			}
			
			// Store HTTP User Agent String (optional):
			if ($this->get_setting('store_useragent')) {
				$this->store_Meta('useragent', $visit_id, $ua_string );
			}
			
		}
		// Old Visitor => update visit and store visited page
		else {
			// Update Visitor
			$res = $this->db->update($this->table_name['visits'],
									 array( 'last_visit' =>  $this->get_datetime() ), 	
									 array( 'id' => $visit_id),
									 array('%s'),
									 array('%d')
									);
			//Store page
			$page_info = $this->get_page_info(1);
			if ($page_info['internal'] == 1) {
				$page_id = $this->store_Page($page_info['internal'], $page_info['title'], $page_info['post_id']);
				$flow_id = $this->store_Flow($visit_id, $page_id);
			}		
		}

		return $visit_id;
	}

	/**
	 * Store page in "pages" DB table
	 **/
	private function store_Page($internal = FALSE, $title = FALSE, $post_id = 0) {
		if (! $title) { return FALSE; }
		
		$page_id = 0;

		// Page already in DB?
		if ($post_id > 0) {
			$page = $this->db->get_row( $this->db->prepare('SELECT id, title
															FROM ' . $this->table_name['pages'] . "
															WHERE  f_post_id='%d';", 
															$post_id
														   ) 
										 );
			if (isset($page->id)) {
				$page_id = $page->id;
			
				//Page got new title? => update page table
				if ($title != $page->title) {
					$res = $this->db->update($this->table_name['pages'],
											 array( 'title' =>  $title ), 	
											 array( 'id' => $page_id),
											 array('%s'),
											 array('%d')
											);
				}
			}
		}
		else {
			$page_id = $this->db->get_var( $this->db->prepare('SELECT id 
															   FROM ' . $this->table_name['pages'] . "
															   WHERE  internal='%d' 
															   AND    title='%s';", 
															   $internal, $title
															  ) 
										 );
		}
		
		// Yes: return id
		if ($page_id) {		
			return $page_id;
		}
		// No: insert new page and return id
		else {
			$res = $this->db->insert($this->table_name['pages'],
									 array( 'internal'   => $internal,
											'title'		 => $title,
											'f_post_id'	 => $post_id
										  ),
									 array('%d', '%s', '%d')
									);
			return $this->db->insert_id;
		}
	}
	
	/**
	 * Store entry in "flow" DB table
	 **/
	private function store_Flow($visit_id = FALSE, $page_id = FALSE) {
		if (! $visit_id || ! $page_id)  { return FALSE; }
		
		// Check, if visitor is already in the flow
		$flow = $this->db->get_row( $this->db->prepare('SELECT f_page_id, step FROM ' . $this->table_name['flow'] . "
														WHERE f_visit_id='%d' 
														ORDER BY step DESC
														LIMIT 1;", 
														$visit_id
												       ) 
									);
		$step = 0;
		// New flow? Start with step = 0		
		if (! isset($flow->f_page_id)) {	
			$step = 1;
		}
		else {
			// Are we on a new page? Or is it the same, e.g. by reloading page?
			if ($page_id != $flow->f_page_id) {
				$step =  $flow->step + 1;
			}
		}

		// Everything ok, store the page flow
		if ($step >= 1) {
			$res = $this->db->insert($this->table_name['flow'],
									 array( 'f_visit_id' => $visit_id,
											'step'	 	 => $step,
											'datetime'	 => $this->get_datetime(),
											'f_page_id'	 => $page_id
										  ),
									 array('%d', '%d', '%s', '%d')
									);
			return $this->db->insert_id;
		}
		return FALSE;
	}

	
	/**
	 * Store entry in "meta" DB table
	 **/
	public function store_Meta($type, $label, $value) {
		$meta_table = $this->db->prefix . 'visitorflow_meta';
		$res = $this->db->insert($meta_table,
								 array( 'type' 	=> $type,
										'label' => substr($label, 0, 254),
										'value' => $value,
										'datetime' => $this->get_datetime()
									  ),
								 array('%s', '%s', '%s')
								);
		return $res;
	}
	
	/**
	 * Increase a value in meta table
	 **/
	private function increase_Meta($type, $label) {
		$meta_table = $this->db->prefix . 'visitorflow_meta';
		
		$result = $this->db->get_row( $this->db->prepare("SELECT id, value 
														  FROM $meta_table
														  WHERE type='%s' AND label='%s' 
														  LIMIT 1;", 
														  $type, $label
												        ) 
									);
		
		if (! isset($result->id)) {	
			$res = $this->db->insert($meta_table,
									 array( 'type'     => $type,
											'label'	   => $label,
											'value'	   => 1,
											'datetime' => $this->get_datetime(),
										  ),
									 array('%s', '%s', '%d', '%s')
									);
			return $res;
		}
		else {
			$sql = $this->db->prepare( "UPDATE $meta_table 
										SET value='%s', datetime='%s' 
										WHERE id='%d';",
										($result->value + 1), $this->get_datetime(),
										$result->id );
			return 	$this->db->query( $sql );	
		}
	}
	
	/**
	 * Save Search Engine Keywords
	 **/
	function store_SEKeywords( $url = FALSE, $referer_page_id = FALSE, $page_id = FALSE ) {
		if ( ! $url || ! is_string($url) || ! $referer_page_id || ! $page_id) { return FALSE; }
		
		// Parse the url into its components
		$url_parts = parse_url($url);
			
		// Check if there is any query string
		if (! array_key_exists('query', $url_parts) ) { return FALSE; }
		
		// Convert query string to array
		parse_str($url_parts['query'], $queries); 
		
		// Get the array with search enegine information
		$searchengines = $this->get_SearchEngines();
		
		foreach ( $searchengines as $se_label => $engineinfo ) {
			
			// Check if host contains the SE pattern
			foreach ( $engineinfo['searchpattern'] as $pattern ) {
				
				$pattern = str_replace("\.", "\\.", $pattern);
				if ( preg_match('/' . $pattern . '/',  strtolower($url_parts['host']) ) ) {
					
					// If queries contains the SE querykey store the value
					if ( array_key_exists($engineinfo['querykey'], $queries) ) {
						$keywords = strip_tags ($queries[ $engineinfo['querykey'] ] );
						if ($keywords) {
							$this->store_Meta('se keywords', $se_label . '#' . $keywords, $page_id);
						}
					}
				}
			}
			
		}
		
		return TRUE;
	}
	
	/**
	 * Get SearchEngine Array
	 **/
	public function get_SearchEngines( $searchengine = FALSE ) {
		$searchengines = array ('baidu' 	 => array( 'label' => 'Baidu', 		'searchpattern' => array('baidu.com'), 				'querykey' => 'wd'),
								'bing' 		 => array( 'label' => 'Bing', 		'searchpattern' => array('bing.com'), 				'querykey' => 'q'), 
								'duckduckgo' => array( 'label' => 'DuckDuckGo', 'searchpattern' => array('duckduckgo.com','ddg.gg'),'querykey' => 'q'),
								'google' 	 => array( 'label' => 'Google', 	'searchpattern' => array('google.'), 				'querykey' => 'q'),
								'naver' 	 => array( 'label' => 'Naver', 		'searchpattern' => array('naver.com'), 				'querykey' => 'q'),
								'yahoo' 	 => array( 'label' => 'Yahoo!', 	'searchpattern' => array('yahoo.com'), 				'querykey' => 'p'),
								'yandex' 	 => array( 'label' => 'Yandex', 	'searchpattern' => array('yandex.ru'), 				'querykey' => 'text'),
								);
		if ($searchengine) {
			return $searchengines[$searchengine];
		}
		return $searchengines;
	}
}

endif;	// Prevent multiple class definitions
	