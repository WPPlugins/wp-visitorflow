<?php
	/**
	 * Plot Diagram Function using jqplot library
	 **/
	function wp_visitorflow_plot($title, $chart_data, $chart_options = array('id' => 'wpvfplot', 'width' => '600px', 'height' => '400px')  ) {
		
		wp_enqueue_style('jqplot_css',  plugin_dir_url( __FILE__ )  . '../../assets/css/jquery.jqplot.css');	
		wp_register_script('jqplot_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.jqplot.min.js' );	
		wp_enqueue_script( 'jqplot_js' );
		wp_register_script('dateAxisRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.dateAxisRenderer.min.js' );	
		wp_enqueue_script( 'dateAxisRenderer_js' );
		wp_register_script('enhancedLegendRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.enhancedLegendRenderer.min.js' );	
		wp_enqueue_script( 'enhancedLegendRenderer_js' );
		wp_register_script('canvasAxisLabelRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.canvasAxisLabelRenderer.min.js' );	
		wp_enqueue_script( 'canvasAxisLabelRenderer_js' );
		wp_register_script('canvasTextRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.canvasTextRenderer.min.js' );	
		wp_enqueue_script( 'canvasTextRenderer_js' );

		// Create data and label strings
		$data_string = '';
		$label_string = '';
		foreach ($chart_data as $series) {
			if ($label_string) { $label_string .= ','; }
			$label_string .= "'" . $series['label'] . "'";

			$data = $series['data'];
			$string = '';
			foreach ($data as $x => $y) {
				if ($string) { $string .= ','; }
				$string .= "['" . $x . "'," . $y . "]";
			}
			
			if ($data_string) { $data_string .= ','; }
			$data_string .= '[' . $string . ']';
		}
		
?>	
		<div id="<?php echo $chart_options['id']; ?>" style="height:<?php echo $chart_options['height']; ?>;width:<?php echo $chart_options['width']; ?>"></div>	
		
		<script>
			jQuery(document).ready(function(){
				var data=[<?php echo $data_string; ?>];
	
				var plot1 = jQuery.jqplot('<?php echo $chart_options['id']; ?>', data, {
					title:'<?php echo $title; ?>',
					seriesDefaults: { 
						shadow: false, 
						showMarker:true
					},
					legend:{
						show:true, 
						renderer: jQuery.jqplot.EnhancedLegendRenderer,
						rendererOptions:{
							seriesToggleReplot: { 
								resetAxes: true
							}
						},
						placement: 'inside', 
						location:'ne',
						labels: [<?php echo $label_string; ?>]
					},
					axes:{
						xaxis:{
							renderer:jQuery.jqplot.DateAxisRenderer,
							tickOptions:{formatString: '<?php echo __('%d-%b %y', 'wp_visitorflow'); ?>'},
						},
						yaxis: {
							label: 'Counts',
							labelRenderer:jQuery.jqplot.CanvasAxisLabelRenderer,
							min: 0				 
						}
					},
					grid: {
						shadow: false, 
						backgroundColor: '#fcfcf8'
					},
					series:[{lineWidth:4, markerOptions:{style:'square'}}]
				});
			});
		</script>
<?php
	}
	
