<?php



if (!defined('ABSPATH')) {

    exit;
}


function cpm_memberpress_form_addon_enqueue_script()

{

    global $ver_num;

    $ver_num = mt_rand();

    wp_enqueue_script('cpm_custom_for_memberpress_script', plugin_dir_url(__FILE__) . 'assets/js/cpm_custom.js', array(), $ver_num, 'all');

    wp_enqueue_style('cpm_custom_for_memberpress_css', plugin_dir_url(__FILE__) . 'assets/css/cpm_custom.css', array(), $ver_num, 'all');
}

add_action('admin_enqueue_scripts', 'cpm_memberpress_form_addon_enqueue_script');





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
    if (isset($_POST['save'])) {
        $starting_date = $_POST['superbown_starting_date'];
        $ending_date = $_POST['superbown_ending_date'];
        $pause_date = $_POST['subscription_pause_date'];
        $resume_date = $_POST['subscription_resume_date'];

        $startingDateTime = new DateTime($starting_date);
        $endingDateTime = new DateTime($ending_date);
        $pauseDateTime = new DateTime($pause_date);
        $resumeDateTime = new DateTime($resume_date);

        $formattedStartingDateTime = $startingDateTime->format("Y-m-d H:i:s");
        $formattedEndingDateTime = $endingDateTime->format("Y-m-d H:i:s");
        $formattedPauseDateTime = $pauseDateTime->format("Y-m-d H:i:s");
        $formattedResumeDateTime = $resumeDateTime->format("Y-m-d H:i:s");
        $notification_hours = sanitize_text_field($_POST['notification_time']);

        update_option('superbowl_starting_date', $formattedStartingDateTime);
        update_option('superbowl_ending_date', $formattedEndingDateTime);
        update_option('superbowl_renew_notification', $notification_hours);
        update_option('subscription_pause_date', $formattedPauseDateTime);
        update_option('subscription_resume_date', $formattedResumeDateTime);
    }
    $superbowl_starting_date = get_option('superbowl_starting_date');
    $superbowl_ending_date = get_option('superbowl_ending_date');
    $superbowl_renew_notification = get_option('superbowl_renew_notification');
    $subscription_pause_date = get_option('subscription_pause_date');
    $subscription_resume_date = get_option('subscription_resume_date');
?>

    <form method="post" id="cpm-form">
        <h1>Superbowl Setting</h1>
        <fieldset>
            <legend><span class="number">1</span> Superbowl Details</legend>

            <label for="starting date">Superbowl starting at : </label>
            <input type="datetime-local" id="superbown_starting_date" name="superbown_starting_date" value="<?php echo isset($superbowl_starting_date) ? $superbowl_starting_date : ''; ?>" />

            <label for="ending date">Superbowl ends at : </label>
            <input type="datetime-local" id="superbown_ending_date" name="superbown_ending_date" value="<?php echo isset($superbowl_ending_date) ? $superbowl_ending_date : ''; ?>" />

            <label for="pause date">Subscription pause at : </label>
            <input type="datetime-local" id="subscription_pause_date" name="subscription_pause_date" value="<?php echo isset($subscription_pause_date) ? $subscription_pause_date : ''; ?>" />

            <label for="pause date">Subscription resume at : </label>
            <input type="datetime-local" id="subscription_resume_date" name="subscription_resume_date" value="<?php echo isset($subscription_resume_date) ? $subscription_resume_date : ''; ?>" />

            <label for="stripe_live_key">Trial ending notification hours : </label>
            <input type="text" id="notification_time" name="notification_time" value="<?php echo isset($superbowl_renew_notification) ? $superbowl_renew_notification : ''; ?>" placeholder="Notification time before ---- hours?" />
        </fieldset>
        <input type="submit" value="save" name="save" />
    </form>
<?php }


add_action('notification_before_36_hours', 'notification_before_36_hours');
// add_action('wp_footer', 'notification_before_36_hours');
function notification_before_36_hours()
{
    global $wpdb;
    // $get_last_registerd_users = getUsersFromFeb11to15();
    $superbowl_renew_notification = get_option('superbowl_renew_notification');
    $notification_hour = (7 * 24) - $superbowl_renew_notification;
    $current_date = date("Y-m-d h:i:s");
    $notify_subscriptions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mepr_subscriptions AS S, {$wpdb->prefix}usermeta AS U WHERE S.trial = 1 AND TIMEDIFF('$current_date', S.created_at) >= '$notification_hour:00:00' AND U.meta_key = 'notificaiton_sent' AND U.meta_value = 1 AND S.user_id = U.user_id");
    foreach ($notify_subscriptions as $notify_subscription) {
        $user = get_user_by('ID', $notify_subscription->user_id);
        $user_email = $user->user_email;

        $is_mailed_for_notification = get_user_meta($notify_subscription->user_id, 'notificaiton_sent')[0];
        $subject = 'Regarding subscription date';
        $message = 'Your subscription date will be expired after 36 hours.';

        if (!$is_mailed_for_notification) {
            $mail = wp_mail($user_email, $subject, $message);
        }

        if ($mail) {
            update_user_meta($notify_subscription->user_id, 'notificaiton_sent', 1);
        }
    }




    // var_dump($get_last_registerd_users);
    //for notification to regtisterd from 11-15 of feb
}


function mepr_capture_new_member_signup_completed($event)
{
    global $wpdb;
    $superbowl_ending_date = get_option('superbowl_ending_date');
    $subscription_pause_date = get_option('subscription_pause_date');
    $transaction = $wpdb->prefix . 'mepr_transactions';
    $subscriptions_table_name = $wpdb->prefix . 'mepr_subscriptions';
    $user = $event->get_data();
    $txn_data = json_decode($event->args);
    $created_date = $txn_data->created_at;
    if ($created_date <= $superbowl_ending_date && $created_date >= $subscription_pause_date) {
        $update_subscription_status = $wpdb->update($subscriptions_table_name, array('status' => 'suspended'), array('user_id' => $txn_data->user_id));
    }
    $update_transaction_expire_data = $wpdb->update($transaction, array('expires_at' => $superbowl_ending_date), array('user_id' => $txn_data->user_id));
}
add_action('mepr-event-member-signup-completed', 'mepr_capture_new_member_signup_completed');


function getUsersFromSepToFeb()
{
    global $wpdb;
    $superbowl_starting_date = get_option('superbowl_starting_date');
    $superbowl_ending_date = get_option('superbowl_ending_date');
    $user_ids = $wpdb->get_results("SELECT DISTINCT user_id FROM {$wpdb->prefix}mepr_transactions WHERE created_at BETWEEN '$superbowl_starting_date' AND '$superbowl_ending_date' ");
    return $user_ids;
}

// add_action('wp_footer', 'getUsersFromFeb11to15');
function getUsersFromFeb11to15()
{
    global $wpdb;
    $superbowl_ending_date = get_option('superbowl_ending_date');
    $daysToSubtract = 4;
    $current_date = date("Y-m-d h:i:s");
    $superbowl_renew_notification = get_option('superbowl_renew_notification');
    $dateTime = new DateTime($superbowl_ending_date);
    $dateTime->sub(new DateInterval("P{$daysToSubtract}D"));
    $days_berfore_superbowl_ending_date = $dateTime->format('Y-m-d H:i:s');

    $superbowl_starting_date = get_option('superbowl_ending_date');
    $yearsToAdd = 1;

    $addeddateTime = new DateTime($superbowl_starting_date);
    $addeddateTime->add(new DateInterval("P{$yearsToAdd}Y"));

    $newaddeddateTime = $addeddateTime->format('Y-m-d H:i:s');

    $notify_subscriptions_between_11_15 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mepr_subscriptions AS S, {$wpdb->prefix}mepr_transactions AS T WHERE S.trial = 1 && S.status = 'suspended' AND TIMEDIFF('$newaddeddateTime', '$current_date') >= '$superbowl_renew_notification:00:00' AND T.created_at BETWEEN ('$days_berfore_superbowl_ending_date','$superbowl_ending_date) AND S.user_id = T.user_id");


    // echo '<pre>';
    // var_dump("SELECT * FROM {$wpdb->prefix}mepr_subscriptions AS S, {$wpdb->prefix}mepr_transactions AS T WHERE S.trial = 1 && S.status = 'suspended' AND TIMEDIFF('2023-08-19 12:00:00', '$current_date') >= '$superbowl_renew_notification:00:00' AND T.created_at BETWEEN ('$days_berfore_superbowl_ending_date','$superbowl_ending_date) AND S.user_id = T.user_id");
    // var_dump($notify_subscriptions_between_11_15);
    // echo '</pre>';
    return $notify_subscriptions_between_11_15;
}

//function to resume subscription
add_action('resumePausedSubscription', 'resumePausedSubscription');
function resumePausedSubscription()
{
    global $wpdb;
    $superbowl_ending_date = get_option('superbowl_ending_date');
    $yearsToAdd = 1;

    $addeddateTime = new DateTime($superbowl_ending_date);
    $addeddateTime->add(new DateInterval("P{$yearsToAdd}Y"));

    $newaddeddateTime = $addeddateTime->format('Y-m-d H:i:s');

    $transaction = $wpdb->prefix . 'mepr_transactions';
    $offer_subscribed_members = getUsersFromSepToFeb(); //gives user details who has registered from (sep - feb)
    $subscriptions_table_name = $wpdb->prefix . 'mepr_subscriptions';
    foreach ($offer_subscribed_members as $key => $offer_subscribed_member) {
        $user_subscription_staus = $wpdb->get_results("SELECT user_id,status FROM {$wpdb->prefix}mepr_subscriptions WHERE user_id = $offer_subscribed_member->user_id ORDER BY created_at DESC
        LIMIT 1", ARRAY_A);
        $update_transaction_expire_data = $wpdb->update($transaction, array('expires_at' => $newaddeddateTime), array('user_id' => $offer_subscribed_member->user_id));
        if (!empty($user_subscription_staus)) {
            if ($user_subscription_staus[0]['status'] == 'suspended') {
                $update_subscription_status = $wpdb->update($subscriptions_table_name, array('status' => 'active'), array('user_id' => $user_subscription_staus[0]['user_id']));
            }
        }
    }
}

//function to pause subscription
add_action('pauseResumeSubscription', 'pauseResumeSubscription');
function pauseResumeSubscription()
{
    global $wpdb;
    $offer_subscribed_members = getUsersFromSepToFeb(); //gives user details who has registered from (sep - feb)
    $subscriptions_table_name = $wpdb->prefix . 'mepr_subscriptions';
    foreach ($offer_subscribed_members as $key => $offer_subscribed_member) {
        $user_subscription_staus = $wpdb->get_results("SELECT user_id,status FROM {$wpdb->prefix}mepr_subscriptions WHERE user_id = $offer_subscribed_member->user_id ORDER BY created_at DESC
        LIMIT 1", ARRAY_A);
        if (!empty($user_subscription_staus)) {
            if ($user_subscription_staus[0]['status'] == 'active') {
                $update_subscription_status = $wpdb->update($subscriptions_table_name, array('status' => 'suspended'), array('user_id' => $user_subscription_staus[0]['user_id']));
            }
        }
    }
}


function notification_time_intervals($schedules)
{
    $schedules['yearly'] = array(
        'interval' => 31540000,
        'display' => __('Once Year')
    );
    $schedules['weekly'] = array(
        'interval' => 604800,
        'display' => __('Once Weekly')
    );
    $schedules['monthly'] = array(
        'interval' => 2635200,
        'display' => __('Once a month')
    );
    $schedules['hourly'] = array(
        'interval' => 60,
        'display' => __('Once a hour')
    );
    // echo '<pre>';
    // var_dump($schedules);
    // echo '</pre>';
    return $schedules;
}
add_filter('cron_schedules', 'notification_time_intervals');

//run to resume subscription
if (!wp_next_scheduled('resumePausedSubscription')) {
    $subscription_resume_date = get_option('subscription_resume_date');
    $newresumetimestamp = strtotime($subscription_resume_date); //timestamp for resume subscription
    wp_schedule_event($newresumetimestamp, 'yearly', 'resumePausedSubscription');
}

//run to pause subscription
if (!wp_next_scheduled('pauseResumeSubscription')) {
    $subscription_pause_date = get_option('subscription_pause_date');
    $newpausetimestamp = strtotime($subscription_pause_date); //timestamp for resume subscription
    wp_schedule_event($newpausetimestamp, 'yearly', 'pauseResumeSubscription');
}

//run to trial subscription subscription
if (!wp_next_scheduled('notification_before_36_hours')) {

    wp_schedule_event(time(), 'hourly', 'notification_before_36_hours');
}
