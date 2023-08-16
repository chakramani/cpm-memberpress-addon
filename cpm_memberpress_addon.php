<?php

/* 

Plugin Name: Memberpress Addon for special occation
Plugin URI: http://codepixelzmedia.com.np// 
Description: Use to do custom offer. 
Version: 1.0.0
Text Domain: memberpress-addon
Author: Codepixelzmedia
*/


/* Main Plugin File */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


if ( is_plugin_active( 'memberpress/memberpress.php' ) ) {
	$init_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "cpm-memberpress-addon" . DIRECTORY_SEPARATOR  ."cpm_memberpress_addon-loader.php";
	require_once $init_file;

} else {
	if ( ! function_exists( 'memberpress_add_on_notification' ) ) {
		function memberpress_add_on_notification() {
			?>
			<div id="message" class="error">
				<p><?php _e( 'Please install and activate memberpress plugin to use memberpress Addon .', 'memberpress-addon' ); ?></p>
			</div>
			<?php
		}
	}
	add_action( 'admin_notices', 'memberpress_add_on_notification' );
}

// function my_custom_user_registration_listener($user_id) {
//     // Get preferred pricing information from registration form or other source
//     $preferred_pricing = $_POST['preferred_pricing']; // Replace with actual field name
    
//     // Based on the preferred pricing, determine user role
//     if ($preferred_pricing === 'basic') {
//         $new_role = 'basic_role';
//     } elseif ($preferred_pricing === 'premium') {
//         $new_role = 'premium_role';
//     } else {
//         $new_role = 'default_role';
//     }

//     // Assign the new user role
//     wp_update_user(array('ID' => $user_id, 'role' => $new_role));
// }

// add_action('user_register', 'my_custom_user_registration_listener');


// function custom_billing_cycle($start_date, $user_id) {
//     // Check if user is in trial period
//     if (is_user_in_trial($user_id)) {
//         $start_date = calculate_next_billing_date($user_id); // Calculate the start of billing cycle
//     }
    
//     $end_date = calculate_next_february_15(); // Calculate the end of billing cycle
    
//     return array($start_date, $end_date);
// }

// add_filter('memberpress_subscription_dates', 'custom_billing_cycle', 10, 2);



// // Define a function to send notifications
// function send_notification($user_id, $message) {
//     // Use your preferred method to send notifications (email, SMS, etc.)
// }

// // Define a function to schedule notifications
// function schedule_notifications($user_id) {
//     // Calculate the notification date 36 hours before trial end
//     $notification_date = strtotime('+1 week -36 hours');

//     // Schedule the notification
//     wp_schedule_single_event($notification_date, 'send_notification_event', array($user_id));
// }

// // Hook to schedule notifications when a user signs up
// add_action('memberpress_before_checkout', 'schedule_notifications');

// // Hook to send notifications
// add_action('send_notification_event', 'send_notification', 10, 2);
