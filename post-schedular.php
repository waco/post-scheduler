<?php
/*
Plugin Name: Post Schedular
Description: 公式の予約公開が失敗するから作った
Author: waco
Version: 1.0.0
Text Domain: post-schedular
*/

/* Load translation, if it exists */
$plugin_dir = basename(dirname(__FILE__));

// Default Values
$postSchedulerTimeZone = 'Asia/Tokyo';

// Detect WPMU/MultiSite
function postScheduler_is_wpmu() {
	if (function_exists('is_multisite'))
		return is_multisite();
	else
		return file_exists(ABSPATH."/wpmu-settings.php");
}

// Timezone Setup
function postSchedulerTimezoneSetup() {
  global $postSchedulerTimeZone;
  @date_default_timezone_set($postSchedulerTimeZone);
}

// Add cron interval of 60 seconds
function postSchedulerAddCronMinutes($array) {
       $array['postschedulerminute'] = array(
               'interval' => 60,
               'display' => __('Once a Minute','post-scheduler')
       );
	return $array;
}
add_filter('cron_schedules','postSchedulerAddCronMinutes');

/** 
 * Function that does the actualy schedule - called by wp_cron
 */
function scheduledate_publish_scheduled_posts() {
	global $wpdb;
	postSchedulerTimezoneSetup();
	$query = "select ID from $wpdb->posts " .
          " where post_status = 'future' AND post_date >= '" . date('Y-m-d') ."';";
	$results = $wpdb->get_results($query);

	$fp = fopen("wp-post-scheduler.log", "a");
	fwrite( $fp, "" . count($results) . "\n" );
	fwrite( $fp, "" . $query . "\n" );
  	if (!empty($results)) foreach ($results as $a) {
	  fwrite( $fp, "1  " . $a->post_id . "\n" );
	  wp_update_post(array('ID' => $a->ID, 'post_status' => 'publish'));
	}
	fwrite( $fp, "" . time() . "\n" );
	fclose( $fp );
}

if (postScheduler_is_wpmu())
	add_action ('scheduledate_publish_'.$current_blog->blog_id, 'scheduledate_publish_scheduled_posts');
else
	add_action ('scheduledate_publish', 'scheduledate_publish_scheduled_posts');

/** 
 * Called at plugin activation
 */
function scheduledate_activate () {
        postSchedulerTimezoneSetup();

	if (postScheduler_is_wpmu())
		wp_schedule_event(mktime(date('H'),0,0,date('m'),date('d'),date('Y')), 'postschedulerminute', 'scheduledate_publish_'.$current_blog->blog_id);
	else
		wp_schedule_event(mktime(date('H'),0,0,date('m'),date('d'),date('Y')), 'postschedulerminute', 'scheduledate_publish');
}
register_activation_hook (__FILE__, 'scheduledate_activate');

/**
 * Called at plugin deactivation
 */
function scheduledate_deactivate () {
	global $current_blog;
	if (postScheduler_is_wpmu())
		wp_clear_scheduled_hook('scheduledate_publish_'.$current_blog->blog_id);
	else
		wp_clear_scheduled_hook('scheduledate_publish');
}
register_deactivation_hook (__FILE__, 'scheduledate_deactivate');

