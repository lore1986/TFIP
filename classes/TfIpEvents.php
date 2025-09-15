<?php

include_once dirname( __FILE__ ) . '/TFIP_Database.php';

class TfIpEvent
{
    private TFIP_Database $_ipfDatabase;

    function __construct(TFIP_Database $database)
    {
        $this->_ipfDatabase = $database;

        add_action( 'init', [$this ,'tfIpf_register_event_post_type'] );
        add_action( 'save_post', [$this, 'save_tfIpf_meta_event_box_data'], 10, 3 );
        add_action( 'delete_post', [$this, 'delete_tfIpf_event_and_data']);
        add_action( 'wp_trash_post', [$this, 'trash_tfIpf_event_and_data'] );
    }
    


    function tfIpf_register_event_post_type() {

        $supports = array(
            'title',
            'thumbnail',
            'editor', 
            );

        $labels = array(
            'name' => _x('Eventi', 'plural'),
            'singular_name' => _x('Evento', 'singular'),
            'menu_name' => _x('The Florence Eventi', 'admin menu'),
            'name_admin_bar' => _x('The Florence Eventi', 'admin bar'),
            'add_new' => _x('Aggiungi Evento', 'add new'),
            'add_new_item' => __('Aggiungi Evento'),
            'new_item' => __('Nuovo Evento'),
            'edit_item' => __('Modifica Evento'),
            'view_item' => __('Vedi Evento'),
            'all_items' => __('Tutti gli eventi'),
            'search_items' => __('Cerca Evento'),
            'not_found' => __('Nessun Evento trovato.'),
            
            );
        

        $args = array(
            'supports' => $supports,
            'labels' => $labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'tfipfevent'),
            'has_archive' => true,
            'hierarchical' => true,
            'register_meta_box_cb' => [$this,  'tfIpf_notice_meta_box'],
        );


        register_post_type( 'tfipfevent' , $args );
    }

    function tfIpf_notice_meta_box()
    {
        $screens = array( 'tfipfevent' );

        foreach ( $screens as $screen ) {
            add_meta_box(
                'tf_ipf_event_metabox',
                __( 'Parametri Evento', 'sitepoint' ),
                [$this, 'tfip_event__meta_box_callback'],
                $screen
            );
        }
    }


    /*
        Function for event metadata - data that describe the event as:
        Date
        Time
        Type of event
        Event specific fieldssave_form_admin_booking()
    */
    
    function tfip_event__meta_box_callback( $post ) {
        
        wp_nonce_field( 'tf_ipf_nonce_global', 'tfIpf_one_once' );
    
        $timestamp = get_post_meta( $post->ID, '_tfIpf_event_date', true );

        $timeslots = null;
        $refreshtimeslots = 1;

        if(empty($timestamp))
        {
            //get default timeslots it is a new event
            $timestamp = strtotime('today');
            // $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($timestamp);

        }else
        {
            //get specific timeslot selected
            $timestamp_verification = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($timestamp);

            $refreshtimeslots = 0;
            
            if($timestamp_verification == null)
            {
                //here check for error
                return;
            }

            $event_timeslot = get_post_meta( $post->ID, '_tfIpf_event_timeslot', true );
            $timeslots =  $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($timestamp);

            foreach ($timeslots as $ts) {
                # code...
            }

            //if($timeslots)
            


        }


    
        $date_event = date('d-m-Y', $timestamp);
        
        
        //$time_event = date('H:i', $timestamp);
    
        $event_type = esc_attr(get_post_meta($post->ID, '_tfIpf_event_type', true));
        $teamone = esc_attr(get_post_meta($post->ID, '_tfIpf_event_team_one', true));
        $teamtwo = esc_attr(get_post_meta($post->ID, '_tfIpf_event_team_two', true));
        $exact_time_event = esc_attr(get_post_meta($post->ID, '_tfip_exact_time_event', true));

        ?>
    
        <div class="form-group mb-3">
            <label for="event_date"><?php _e('Data Evento:', 'textdomain'); ?></label>
            <!-- review here id-time attribute if necessary-->
            <input type="text" class="form-control" id="event_date" name="event_date" data-idtime="<?php echo esc_attr($exact_time_event); ?>" value="<?php echo esc_attr($date_event); ?>" autocomplete="off">
        </div>
    
        <div class="form-group mb-3">
            <div class="row">
                <label for="event_time"><?php _e('Orario Evento:', 'textdomain'); ?></label>
            </div>
            <div class="row">
                <select id="event_time" name="event_time" class="form-control">
                <?php 
                    if($timeslots != null)
                    {
                        foreach ($timeslots as $slot_item): 
                            $slot = $slot_item['ts'];
                        ?>
                        <option 
                            data-idtimeslot="<?php echo esc_attr($slot->id); ?>" 
                            value="<?php echo esc_attr($slot->timeslotstart); ?>"
                            <?php selected($slot->id, $event_timeslot); ?>>
                            <?php echo esc_html($slot->timeslotstart . ' - ' . $slot->timeslotend); ?>
                        </option>
                        <?php endforeach; 
                    }?>

                </select>
            </div>
            <div class="row">
                <label for="exact_time_event"><?php _e('Orario Esatto:', 'textdomain'); ?></label>
                <input type="text" class="form-control" id="exact_time_event" name="exact_time_event" value="<?php echo esc_attr($exact_time_event); ?>" >
            </div>
        </div>
    
        <div class="form-group mb-3">
            <div class="row">
                <label for="type_event"><?php _e('Tipo Evento:', 'textdomain'); ?></label>
            </div>
            <div class="row">
                <select id="type_event" name="type_event" class="form-control">
                    <option value="sport" <?php selected($event_type, 'sport'); ?>><?php _e('Sport', 'textdomain'); ?></option>
                    <option value="music" <?php selected($event_type, 'music'); ?>><?php _e('Music', 'textdomain'); ?></option>
                    <option value="food" <?php selected($event_type, 'food'); ?>><?php _e('Food', 'textdomain'); ?></option>
                </select>
            </div>
        </div>
        
        <div id="sport_fields" class="form-group mb-3" style="display: <?php echo ($event_type === 'sport') ? 'block' : 'none'; ?>;">
            <div class="row">
                <label for="teamone"><?php _e('Squadra di casa:', 'textdomain'); ?></label>
                <input type="text" class="form-control mb-2" id="teamone" name="teamone" value="<?php echo $teamone; ?>">
            </div>
            <div class="row">
                <label for="teamtwo"><?php _e('Squadra trasferta:', 'textdomain'); ?></label>
                <input type="text" class="form-control" id="teamtwo" name="teamtwo" value="<?php echo $teamtwo; ?>">
            </div>
        </div>
    
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                Activate_Admin_Event_Options(<?php echo $refreshtimeslots ?>);

            });

            
            </script>

        <?php
    }
    
    function tfip_call_check_timeslots($datetimestamp, $timestring)
    {
        $day_id = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($datetimestamp);

        if($day_id == null)
        {
            $day_id = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($datetimestamp);
        }

        $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($datetimestamp);
        
        if(count($timeslots) == 0)
        {
            $timeslots = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($datetimestamp);
        }


        if (count($timeslots) == 1) {

            return $timeslots[0]['ts']; 

        } else {
            
            foreach ($timeslots as $ts) {
                $ts_item = $ts['ts']; 
        
                $ts_start_date = DateTime::createFromFormat('H:i', $ts_item->timeslotstart);
                $compare_time  = DateTime::createFromFormat('H:i', $timestring);
        
                if ($ts_start_date->format('H:i') === $compare_time->format('H:i')) {
                    return $ts_item;
                }
            }
        }    
        
    }


    function delete_tfIpf_event_and_data( $post_id ) {

        $post = get_post( $post_id );
    
       
        if ( $post && $post->post_type === 'tfipfevent' ) {

            delete_post_meta( $post_id, '_tfIpf_event_date');
            delete_post_meta( $post_id, '_tfIpf_event_type');
            delete_post_meta( $post_id, '_tfIpf_event_team_one');
            delete_post_meta( $post_id, '_tfIpf_event_team_two');
            delete_post_meta( $post_id, '_tfIpf_event_time');
            delete_post_meta( $post_id, '_tfIpf_event_timeslot');
            delete_post_meta( $post_id, '_tfip_exact_time_event');

        }
    }

    function trash_tfIpf_event_and_data($post_id)
    {
        $post = get_post( $post_id );
    }

    function save_tfIpf_meta_event_box_data($post_id, $post, $update) {

        global $pagenow;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }

        }
        else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }
        
        if($pagenow !== "post-new.php")
        {
            $timestamp_date = null;
        
            $timestamp_date = strtotime(str_replace('/', '-', $_POST['event_date']));
            $timeslot_time = esc_attr($_POST['event_time']);


            $timeslot_obj = $this->tfip_call_check_timeslots($timestamp_date, $timeslot_time);
            $timeslot_id = $timeslot_obj->id;


            $event_type = esc_attr($_POST['type_event']);
            $teamone = ucfirst(strtolower($_POST['teamone']));
            $teamtwo = ucfirst(strtolower($_POST['teamtwo']));

            $exact_event_time = esc_attr( $_POST['exact_time_event']);
            
            
            update_post_meta( $post_id, '_tfIpf_event_date', $timestamp_date);
            update_post_meta( $post_id, '_tfIpf_event_type', $event_type );
            update_post_meta( $post_id, '_tfIpf_event_team_one', $teamone );
            update_post_meta( $post_id, '_tfIpf_event_team_two', $teamtwo );
            update_post_meta( $post_id, '_tfIpf_event_time', $timeslot_obj->timeslotstart . " - " . $timeslot_obj->timeslotend);
            update_post_meta( $post_id, '_tfIpf_event_timeslot', $timeslot_id);
            update_post_meta( $post_id, '_tfip_exact_time_event', $exact_event_time);
        
        }
        
    }


   

}