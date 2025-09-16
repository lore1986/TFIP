<?php

include_once dirname( __FILE__ ) . '/TFIP_Database.php';

class TfIpEvent
{
    private TFIP_Database $_ipfDatabase;

    function __construct(TFIP_Database $database)
    {
        $this->_ipfDatabase = $database;

        add_action( 'init', [$this ,'tfIpf_register_event_post_type'] );

        // Validation before saving (blocks drafts, publishes, and autosaves if invalid)
        add_filter( 'wp_insert_post_data', [$this, 'validate_tfIpf_event_fields'], 10, 2 );

        // Save meta (only runs if validation passed)
        add_action( 'save_post', [$this, 'save_tfIpf_meta_event_box_data'], 10, 3 );

        add_action( 'delete_post', [$this, 'delete_tfIpf_event_and_data']);
        add_action( 'wp_trash_post', [$this, 'trash_tfIpf_event_and_data'] );

        add_action( 'admin_notices', [$this, 'TFIP_print_form_error']);
    }
    
    function tfIpf_register_event_post_type() {
        $supports = ['title','thumbnail','editor'];

        $labels = [
            'name' => _x('Eventi', 'plural'),
            'singular_name' => _x('Evento', 'singular'),
            'menu_name' => _x('TFIP Events', 'admin menu'),
            'name_admin_bar' => _x('TFIP Events', 'admin bar'),
            'add_new' => _x('Aggiungi Evento', 'add new'),
            'add_new_item' => __('Aggiungi Evento'),
            'new_item' => __('Nuovo Evento'),
            'edit_item' => __('Modifica Evento'),
            'view_item' => __('Vedi Evento'),
            'all_items' => __('Tutti gli eventi'),
            'search_items' => __('Cerca Evento'),
            'not_found' => __('Nessun Evento trovato.'),
        ];
        
        $args = [
            'supports' => $supports,
            'labels' => $labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'tfipfevent'],
            'has_archive' => true,
            'hierarchical' => true,
            'register_meta_box_cb' => [$this,  'tfIpf_notice_meta_box'],
        ];

        register_post_type( 'tfipfevent' , $args );
    }

    function tfIpf_notice_meta_box() {
        add_meta_box(
            'tf_ipf_event_metabox',
            __( 'Parametri Evento', 'sitepoint' ),
            [$this, 'TFIP_event__meta_box_callback'],
            'tfipfevent'
        );
    }

    function delete_tfIpf_event_and_data( $post_id ) {
        $post = get_post( $post_id );
        if ( $post && $post->post_type === 'tfipfevent' ) {
            delete_post_meta( $post_id, '_TFIP_event_date');
            delete_post_meta( $post_id, '_TFIP_event_type');
            delete_post_meta( $post_id, '_TFIP_event_TeamOne');
            delete_post_meta( $post_id, '_TFIP_event_TeamTwo');
            delete_post_meta( $post_id, '_TFIP_event_time');
            delete_post_meta( $post_id, '_TFIP_event_timeslot');
            delete_post_meta( $post_id, '_TFIP_exact_event_time');
        }
    }

    function trash_tfIpf_event_and_data($post_id) {
        $post = get_post( $post_id );
        //  cleanup
    }

    function TFIP_print_form_error(){
        if ( isset($_GET['event_fields_missing']) ) {
            echo '<div class="error"><p><strong>Please fill in Event Date, Event Time, and Exact Event Time before saving the event.</strong></p></div>';
        }
    }

    /**
     *  Block saving (draft/publish/autosave) if required fields missing
     */
    function validate_tfIpf_event_fields($data, $postarr) {
        if ($data['post_type'] !== 'tfipfevent') {
            return $data;
        }
    
        if ($data['post_status'] === 'trash') {
            return $data;
        }
    
        // Let autosave always run
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $data;
        }
    
        $event_date       = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '';
        $event_time       = isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '';
        $exact_event_time = isset($_POST['exact_event_time']) ? sanitize_text_field($_POST['exact_event_time']) : '';
    
        if ( empty($event_date) || empty($event_time) || empty($exact_event_time) ) {
            // Add error flag
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('event_fields_missing', 1, $location);
            });
    
            // Prevent saving by forcing "error" status
            $data['post_status'] = 'auto-draft';
            $data['post_content'] = $postarr['post_content'];
            $data['post_title']   = $postarr['post_title'];
        }
    
        return $data;
    }
    

    /**
     *  Save meta only when all fields are filled
     */

    function save_tfIpf_meta_event_box_data($post_id, $post, $update) {
        
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) return;

        if ( $post->post_type !== 'tfipfevent' ) return;

        $event_date       = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '';
        $event_time       = isset($_POST['event_time']) ? sanitize_text_field($_POST['event_time']) : '';
        $exact_event_time = isset($_POST['exact_event_time']) ? sanitize_text_field($_POST['exact_event_time']) : '';
        $type_event = isset($_POST['type_event']) ? sanitize_text_field($_POST['type_event']) : '';

    
        if($type_event != '')
        {
            update_post_meta($post_id, '_TFIP_event_type', $type_event);
            if($type_event == 'sport')
            {
                $teamone          = isset($_POST['teamone']) ? sanitize_text_field($_POST['teamone']) : '';
                $teamtwo          = isset($_POST['teamtwo']) ? sanitize_text_field($_POST['teamtwo']) : '';

                update_post_meta($post_id, '_TFIP_event_TeamOne', $teamone);
                update_post_meta($post_id, '_TFIP_event_TeamTwo', $teamtwo);

            }
        }
        

        if ( !empty($event_date) && !empty($event_time) && !empty($exact_event_time) ) {

            $date_format = DateTime::createFromFormat('d-m-Y', $event_date)->setTime(0,0,0);
            $timestamp = $date_format->getTimestamp();


            $activeDay = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($timestamp);

            if(!$activeDay)
            {
                $activeDay = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($timestamp);
            }

            $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($activeDay->id_date);

            if(count($timeslots) == 0)
            {
                $timeslots = $this->_ipfDatabase->TFIP_Database_Get_Create_Format_Timeslots($activeDay->id_date);
            }

            $timebooking_normalized = TFIP_Utils::TFIP_Utils_Format_DateTime($activeDay->id_date,  $event_time);
            $timeslotid = TFIP_Utils::TFIP_Utils_Return_Timeslot_For_Selected_Time($timeslots, $activeDay->id_date, $timebooking_normalized)->id;

            update_post_meta($post_id, '_TFIP_event_timeslot', $timeslotid);
            update_post_meta($post_id, '_TFIP_event_timestamp', $timestamp);
            update_post_meta($post_id, '_TFIP_event_date', $event_date);
            update_post_meta($post_id, '_TFIP_event_time', $event_time);
            update_post_meta($post_id, '_TFIP_exact_event_time', $exact_event_time);
        }
    }

    function TFIP_event__meta_box_callback( $post ) {
        wp_nonce_field( 'tf_ipf_nonce_global', 'tfIpf_one_once' );
        
        $date_event       = esc_attr(get_post_meta($post->ID, '_TFIP_event_date', true));
        $event_type       = esc_attr(get_post_meta($post->ID, '_TFIP_event_type', true));
        $teamone          = esc_attr(get_post_meta($post->ID, '_TFIP_event_TeamOne', true));
        $teamtwo          = esc_attr(get_post_meta($post->ID, '_TFIP_event_TeamTwo', true));
        $exact_event_time = esc_attr(get_post_meta($post->ID, '_TFIP_event_time', true));
        $event_time       = esc_attr(get_post_meta($post->ID, '_TFIP_exact_event_time', true));
        ?>

        <div class="TFIP-style">
            <div class="form-group" >
                <input type="text" hidden id="get_event_time" value="<?php echo $exact_event_time; ?>" >
                <input type="text" hidden id="get_exact_event_time" value="<?php echo $event_time; ?>">
            </div>

            <div class="form-group" style="margin-bottom: 8px;">
                <label for="event_date"><?php _e('Data Evento:', 'textdomain'); ?></label>
                <input type="text" class="form-control" id="event_date" name="event_date"  
                    value="<?php echo $date_event; ?>" autocomplete="off">
            </div>
        
            <div class="form-group"  style="margin-bottom: 8px;">
                <label for="event_time"><?php _e('Orario Evento:', 'textdomain'); ?></label>
                <select id="event_time" name="event_time" class="form-control"></select>
            </div>

            <div class="form-group" style="margin-bottom: 8px;">
                <label for="exact_event_time"><?php _e('Orario Esatto:', 'textdomain'); ?></label>
                <select id="exact_event_time" name="exact_event_time"></select>
            </div>
        
            <div class="form-group" style="margin-bottom: 8px;">
                <label for="type_event"><?php _e('Tipo Evento:', 'textdomain'); ?></label>
                <select id="type_event" name="type_event" class="form-control">
                    <option value="sport" <?php selected($event_type, 'sport'); ?>><?php _e('Sport', 'textdomain'); ?></option>
                    <option value="music" <?php selected($event_type, 'music'); ?>><?php _e('Music', 'textdomain'); ?></option>
                    <option value="food"  <?php selected($event_type, 'food'); ?>><?php _e('Food', 'textdomain'); ?></option>
                </select>
            </div>
            <div id="sport_fields" class="form-group" style="margin-bottom: 8px; display: <?php echo ($event_type === 'sport') ? 'block' : 'none'; ?>;">
                <div class="row">
                    <label for="teamone"><?php _e('Squadra di casa:', 'textdomain'); ?></label>
                    <input type="text" class="form-control mb-2" id="teamone" name="teamone" value="<?php echo $teamone; ?>">
                </div>
                <div class="row">
                    <label for="teamtwo"><?php _e('Squadra trasferta:', 'textdomain'); ?></label>
                    <input type="text" class="form-control" id="teamtwo" name="teamtwo" value="<?php echo $teamtwo; ?>">
                </div>
            </div>
        </div>
        <?php
    }
}
