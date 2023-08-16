<?php



if (!defined('ABSPATH')) {

    exit;
}



function cpm_memberpress_form_addon_enqueue_script()

{

    global $ver_num;

    $ver_num = mt_rand();

    wp_enqueue_script('cpm_custom_for_memberpress_script', plugin_dir_url(__FILE__) . '/assets/css/cpm_custom.js', array(), $ver_num, 'all');

    wp_enqueue_style('cpm_custom_for_memberpress_css', plugin_dir_url(__FILE__) . '/assets/js/cpm_custom.css', array(), $ver_num, 'all');
}

add_action('wp_enqueue_scripts', 'cpm_memberpress_form_addon_enqueue_script');





add_action("admin_menu", "cpm_imdb_options_submenu", 9999);

if (!function_exists('cpm_imdb_options_submenu')) {

    function cpm_imdb_options_submenu()

    {

        add_submenu_page(

            'memberpress',

            'Memberpress Addon',

            'Memberpress Addon',

            'manage_options',

            'memberpress-addon',

            'cpm_memberpress_addon_page'

        );
    }
}



function cpm_memberpress_addon_page()

{
    echo 'comming soon';
}

function my_custom_user_registration_listener($user_id)
{

    $currentDate = date('Y-m-d'); // Get the current date in YYYY-MM-DD format

    $septemberDate = date('Y-09-01'); // September 1st of the current year


    // Assign the new user role

    // wp_update_user(array('ID' => $user_id, 'role' => $new_role));
}

add_action('user_register', 'my_custom_user_registration_listener');



add_action('wp_footer', 'notification_before_36_hours');
function notification_before_36_hours()
{
    global $wpdb;
    $members_ids = array();
    $members_id_with_expires = array();
    $subscriontions_expire_date = array();
    $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mepr_members");

    //get users who has subscribed in between september 1 to feb 15
    $offer_subscribed_members = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}mepr_transactions WHERE created_at BETWEEN '2023-09-01 00:00:00' AND '2023-02-15 00:00:00' AND created_at ='2023-09-15 23:59:59'");
    var_dump($offer_subscribed_members);

    foreach ($members as $key  => $member) {
        // Process each member's data
        array_push($members_ids, $member->user_id);
    }
    $implode_members = '(' . implode(',', $members_ids) . ')';
    $subscriptions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mepr_subscriptions WHERE user_id IN $implode_members");

    foreach ($subscriptions as $key => $subscription) {

        if ($subscription->status == 'active' && $subscription->trial) {
            // Get the expiration date of the subscription
            $expiration_date = $wpdb->get_results("SELECT expires_at FROM {$wpdb->prefix}mepr_transactions WHERE subscription_id = $subscription->id", ARRAY_A);
            $user_details = get_userdata($subscription->user_id)->data;
            $members_id_with_expires[$user_details->user_email] = $expiration_date[0]['expires_at']; //save key as user_id and value as expire date
        }
    }

    foreach ($members_id_with_expires as $key => $members_id_with_expire) { //this is for notification before 36 hoirs
        $notification_time = '';
        $givenDate = $members_id_with_expire; // Replace this with your desired date and time
        $hoursToSubtract = 36;

        // Convert the given date string to a DateTime object
        $dateTime = new DateTime($givenDate);

        // Subtract the specified hours
        $dateTime->sub(new DateInterval("PT{$hoursToSubtract}H"));

        // Format and display the result
        $newDate = $dateTime->format("Y-m-d H:i:s"); // this is the date/time to notify
        // echo "Original Date: $givenDate<br>";
        // echo "Subtracted Date: $newDate";
    }

    // echo '<pre>';
    // var_dump($members_id_with_expires);
    // echo '</pre>';
}

function catch_first_payment_after_trial($event) {
    
    $transaction = $event->get_data();
    $subscription = $transaction->subscription();
    $is_first_real_payment = false;
  
    if($subscription !== false) {
      if($subscription->trial && $subscription->trial_amount <= 0.00 && $subscription->txn_count == 1) {
        $is_first_real_payment = true;
      }
      elseif($subscription->trial && $subscription->trial_amount > 0.00 && $subscription->txn_count == 2) {
        $is_first_real_payment = true;
      }
    }
  
    if($is_first_real_payment) {
      // This is the first real payment after a paid or free trial period
      // So do what you will in here
    }
  }
//   add_action('mepr-event-transaction-completed', 'catch_first_payment_after_trial');

  function mepr_capture_new_member_signup_completed($event) {
      global $wpdb;
      $transaction = $wpdb->prefix . 'mepr_transactions';
      $user = $event->get_data();
      $txn_data = json_decode($event->args);
      $resut = $wpdb->update( $transaction, array( 'expires_at' => '2024-02-15 23:59:59'),array('user_id'=>$txn_data->user_id));
      die(var_dump($resut));
  
    //Do what you need
  }
  add_action('mepr-event-member-signup-completed', 'mepr_capture_new_member_signup_completed');