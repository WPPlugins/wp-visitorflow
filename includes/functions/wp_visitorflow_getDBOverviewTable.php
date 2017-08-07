<?php
	$WP_VisitorFlow = WP_VisitorFlow::getInstance();;		

	if (! is_admin() || ! current_user_can( $WP_VisitorFlow->get_setting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$db_info = $WP_VisitorFlow->get_setting('db_info');
?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('Property', 'wp_visitorflow'); ?></th>
		<th><?php echo __('Value', 'wp_visitorflow'); ?></th>
	</tr>
	<tr class="darker">
		<td><?php echo __('Database start date', 'wp_visitorflow'); ?></td>
		<td class="right">
			<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime($WP_VisitorFlow->get_setting('db-startdatetime'))); ?></strong><br />
			(<?php echo sprintf(__('%s ago', 'wp_visitorflow'), 
							   wp_visitorflow_getNiceTimeDifferenz($WP_VisitorFlow->get_setting('db-startdatetime'), $WP_VisitorFlow->get_datetime() )
							   ); ?>)
		</td>
	</tr>
	<tr>
		<td><?php echo __('Flow data start date', 'wp_visitorflow'); ?></td>
		<td class="right">
			<strong><?php echo date_i18n( get_option( 'date_format' ), strtotime($WP_VisitorFlow->get_setting('flow-startdatetime'))); ?></strong><br />
			(<?php echo sprintf(__('%s ago', 'wp_visitorflow'), 
							   wp_visitorflow_getNiceTimeDifferenz($WP_VisitorFlow->get_setting('flow-startdatetime'), $WP_VisitorFlow->get_datetime() )
							   ); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td>
			<?php echo __('Number of visits', 'wp_visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['visits_count']); ?></strong><br />
			(<?php echo number_format_i18n(round($db_info['visits_count'] * $db_info['db_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp_visitorflow'); ?>)
		</td>
	</tr>
	<tr>
		<td>
			<?php echo __('Number of page views', 'wp_visitorflow'); ?>			
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['hits_count']); ?></strong><br />
			(<?php echo number_format_i18n(round($db_info['hits_count'] * $db_info['db_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp_visitorflow'); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td>
			<?php echo __('Recorded bots visits', 'wp_visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['bots_count']); ?></strong><br />
			(<?php echo number_format_i18n(round($db_info['bots_count'] * $db_info['counters_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp_visitorflow'); ?>)
		</td>
	</tr>	
	<tr>
		<td>
			<?php echo __('Excluded page views', 'wp_visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['exclusions_count']); ?></strong><br />
			(<?php echo number_format_i18n(round($db_info['exclusions_count'] * $db_info['counters_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp_visitorflow'); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td><?php echo __('Number of internal pages', 'wp_visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['pages_internal_count']); ?></strong></td>
	</tr>
	<tr>
		<td><?php echo __('Number of external pages (referrers)', 'wp_visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['pages_count'] - $db_info['pages_internal_count']); ?></strong></td>
	</tr>
	<tr class="darker">
		<td><?php echo __('Recorded HTTP User Agent strings', 'wp_visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['meta_useragents_count']); ?></strong></td>
	</tr>
	</table>
