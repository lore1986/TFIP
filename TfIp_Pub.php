<?php
/**
 * Plugin Name: The Florence Irish Pub
 * Description: The Florence Irish Pub Firenze Booking
 * Version: 1.0
 * Author: Tripleg
 */

defined('ABSPATH') || exit; // Prevent direct access

// Include necessary classes
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TfIpCalendar.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TFIP_Database.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TfIpEvents.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TfIpBookings.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TfIpManager.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TFIP_Admin.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TFIP_Pages.php');
include_once(plugin_dir_path(__DIR__) . 'tfIp_Pub/classes/TFIP_Templater.php');

// Shortcode and script actions
add_action('wp_enqueue_scripts', 'TFIP_user_enqueue_scripts');
add_action('admin_enqueue_scripts', 'TFIP_admin_enqueue_scripts');
//add_action('admin_enqueue_scripts', 'TFIP_enqueue_admin_scripts_event');


add_shortcode('tfIpfCalendarShort', 'tfIpf_calendar_all_event_shortcode');
add_shortcode('tfIpfNoEventBooking', 'TFIP_Pub_No_Event_Booking_Shortcode_Action');

// Admin plugin link
$plugin = plugin_basename(__FILE__);
$admin = new TFIP_Admin();
add_filter("plugin_action_links_$plugin", [$admin, 'tfipf_add_settings_link']);

// Global objects
$database = new TFIP_Database();
$calendar = new TfIpCalendar($database);
$H = new TFIP_Templater();
$event = new TfIpEvent($database);
$manager = new TfIpManager();
$bookings = new TfIpBooking($database, $manager);

// Activation hook
register_activation_hook(__FILE__, 'tfip_registration_handler');
// Uninstall hook
register_uninstall_hook(__FILE__, 'tfip_uninstall_handler');
// Deactivation hook
register_deactivation_hook(__FILE__, 'tfip_deregistration_handler');


function tfip_registration_handler() {
    
    $pages = new TFIP_Pages();
    
    $created_page_id = $pages->TFIP_Pages_Create_Page_Booking();

    if ($created_page_id) {
        update_option('tfipf_booking_page_id', $created_page_id);
    }

    $createDatabase = new TFIP_Database();
    $createDatabase->TFIP_Database_create_tables();
}

function tfip_deregistration_handler() {
    $page_id = get_option('tfipf_booking_page_id');
    if ($page_id) {
        wp_delete_post($page_id, true);
        delete_option('tfipf_booking_page_id');
    }
}


function tfip_uninstall_handler() {

    // Delete custom post types
    $allposts = get_posts(['post_type' => 'tfipfevent', 'numberposts' => -1, 'fields' => 'ids']);
    foreach ($allposts as $post_id) {
        wp_delete_post($post_id, true);
    }

    // Drop custom tables
    $deleteDatabase = new TFIP_Database();
    $deleteDatabase->TFIP_Database_drop_tables();

    // Delete custom page
    $page_id = get_option('tfipf_booking_page_id');
    if ($page_id) {
        wp_delete_post($page_id, true);
        delete_option('tfipf_booking_page_id');
    }
}

function TFIP_admin_enqueue_scripts($hook) {
    global $post_type;
   
    wp_enqueue_style('tfip_admin_css', plugin_dir_url(__FILE__) . 'assets/css/TFIP-admin.css');
    
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);

    wp_enqueue_script('tfipf-admin-script', plugin_dir_url(__FILE__) . 'src/js/tfipf-admin.js', [], null, true);
    wp_add_inline_script('tfipf-admin-script', 'ajaxurl', admin_url('admin-ajax.php'));


    if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === 'tfipfevent') {

        // JS
        wp_enqueue_script(
            'TfIpAdmin_js',
            plugin_dir_url(__FILE__) . 'src/js/tfip_admin_js.js',
            [],//array('jquery', 'underscore', 'intlTelInput'),
            null,
            true // put in footer
        );

        // Localize variables
        wp_localize_script('TfIpAdmin_js', 'TFIP_Ajax_Obj', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'templatesUrl'  => plugin_dir_url(__FILE__)  . 'assets/html-templates/',
            'nonce'         => wp_create_nonce('tfip'),
            'tifpBootstrap' => 'tfipf-bootstrap'
        ));
    }
}


function TFIP_user_enqueue_scripts() {

    wp_enqueue_style('tfipf-bootstrap', plugin_dir_url(__FILE__) . 'assets/css/TFIP-boostrap.css');

    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);


    wp_enqueue_script('undescore', includes_url('js') . '/underscore.min.js' );

    wp_enqueue_style('intlTelInput', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/25.3.1/build/css/intlTelInput.min.css');
    wp_enqueue_script('intlTelInput', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/25.3.1/build/js/intlTelInput.min.js', array('jquery'), null);

    wp_enqueue_script('TfIpBooking_event_js',  plugin_dir_url(__FILE__) . 'src/js/event_booking.js',array('jquery', 'intlTelInput'), null, true );
    wp_localize_script('TfIpBooking_event_js', 'TFIP_Ajax_Obj', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'templatesUrl' => plugin_dir_url(__FILE__)  . 'assets/html-templates/',
        'nonce' => wp_create_nonce('tfip'),
        'tifpBootstrap' => 'tfipf-bootstrap'
    ));

    wp_enqueue_script('TfIpCalendar_js',  plugin_dir_url(__FILE__) . 'src/js/calendar_js.js', array('jquery', 'undescore', 'intlTelInput'), null, false );
    
    wp_localize_script('TfIpCalendar_js', 'TFIP_Ajax_Obj', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'templatesUrl' => plugin_dir_url(__FILE__)  . 'assets/html-templates/',
        'nonce' => wp_create_nonce('tfip'),
        'tifpBootstrap' => 'tfipf-bootstrap'
    ));


}


function tfIpf_calendar_all_event_shortcode($atts)
{
    $max_num = isset($atts['maxnum']) ? intval($atts['maxnum']) : -1;

    ob_start();
    ?>
    <!DOCTYPE html>
    <div class="TFIP-style">
        <div class="row ">
            <div id="container container-booking" class="col-12" style="display:none;"></div>
            <div id="container-list-events" class="col-12"></div>
        </div>
    </div>

    <script>
        window.onload = function() {
            ajax_call_calendar(<?php echo $max_num; ?>);
        };
    </script>
    <?php
    return ob_get_clean();
}

//this tooo
function TFIP_Pub_No_Event_Booking_Shortcode_Action()
{
    global $database;
    ob_start();
    $obj_d = new stdClass();
    
    $obj_d->date_str = date('d-m-Y');
    $obj_d->date_stamp = strtotime($obj_d->date_str);

    $obj_d->timeslots = $database->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($obj_d->date_stamp);

    if(count($obj_d->timeslots) == 0)
    {
        $obj_d->timeslots = get_option('tfip_timeslots', []);
    }

    extract([ 'objdata' => $obj_d ]);
    
    $template = plugin_dir_path(__DIR__) . 'tfIp_Pub/assets/html-templates/partial/booking_no_event.php';
    
    if ($template) {
        include $template;
    } else {
        echo '<p>Template not found.</p>';
    }

    return ob_get_clean();
}





