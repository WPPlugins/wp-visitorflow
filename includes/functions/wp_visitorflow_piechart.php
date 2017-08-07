<?php
	/**
	 * Pie Chart Function using jqplot library
	 **/
	function wp_visitorflow_piechart($title, $chart_data, $chart_options = array('id' => 'piechart', 'width' => '600px', 'height' => '400px') ) {
		
		wp_enqueue_style('jqplot_css',  plugin_dir_url( __FILE__ )  . '../../assets/css/jquery.jqplot.css');	
		wp_register_script('jqplot_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.jqplot.min.js' );	
		wp_enqueue_script( 'jqplot_js' );
		wp_register_script('pieRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.pieRenderer.min.js' );	
		wp_enqueue_script( 'pieRenderer_js' );	
		
		$pie_data = '';
		arsort($chart_data);
		$entries_count = 0;
		$others_count = 0;
		foreach($chart_data as $label => $count) {
			if ($entries_count < 10) {
				if ($pie_data) { $pie_data .= ','; }
				if (! $label) { $label .= __('Unknown', 'wp_visitorflow'); }
				$pie_data .= "['" . $label . "'," . $count . "]";
				$entries_count ++;
			}
			else {
				$others_count++;
			}
		}
		if ($others_count) {
			$pie_data .= ",['" . __('Others', 'wp_visitorflow') . "'," . $others_count . "]";
		}
		
		if (! array_key_exists('legendrows', $chart_options)) {
			$chart_options['legendrows'] = 3;
		}
		
?>
		<div id="<?php echo $chart_options['id']; ?>" style="height:<?php echo $chart_options['height']; ?>;width:<?php echo $chart_options['width']; ?>"></div>	
		<script>
			jQuery(document).ready(function(){
				var data=[<?php echo $pie_data; ?>];
				
				jQuery.jqplot.config.enablePlugins = true;
				var pieplot = jQuery.jqplot('<?php echo $chart_options['id']; ?>', [data], {
					title:'<?php echo $title; ?>',
					grid: {
						 drawBorder: false, 
						 background: '#eee'
					},
					seriesDefaults: {
						shadow: true, 
						renderer: jQuery.jqplot.PieRenderer, 
						rendererOptions: { showDataLabels: true } 
					}, 
					legend: { 
						show:true,
						rendererOptions: {
							numberRows: <?php echo $chart_options['legendrows']; ?>
						},
						location: 's'						
					}
				});
			});
		</script>
<?php		
	}

	
