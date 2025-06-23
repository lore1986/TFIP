<?php

class TfIpEvent
{
    function __construct()
    {
        add_action( 'init', [$this ,'tfIpf_register_event_post_type'] );
        add_action( 'save_post', [$this, 'save_tfIpf_meta_event_box_data'] );
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
        Event specific fields
    */
    
    function tfip_event__meta_box_callback( $post ) {
        wp_nonce_field( 'tf_ipf_nonce_global', 'tfIpf_one_once' );
    
        $timestamp = get_post_meta( $post->ID, '_tfIpf_event_date_time', true );
        $timestamp = !empty($timestamp) ? (int) $timestamp : time();
    
        $date_event = date('d/m/Y', $timestamp);
        $time_event = date('H:i', $timestamp);
    
        $event_type = esc_attr(get_post_meta($post->ID, '_tfIpf_event_type', true));
        $teamone = esc_attr(get_post_meta($post->ID, '_tfIpf_event_team_one', true));
        $teamtwo = esc_attr(get_post_meta($post->ID, '_tfIpf_event_team_two', true));
        ?>
    
        <div class="form-group mb-3">
            <label for="event_date"><?php _e('Data Evento:', 'textdomain'); ?></label>
            <input type="text" class="form-control" id="event_date" name="event_date" value="<?php echo esc_attr($date_event); ?>" autocomplete="off">
        </div>
    
        <div class="form-group mb-3">
            <div class="row">
                <label for="event_time"><?php _e('Orario Evento:', 'textdomain'); ?></label>
            </div>
            <div class="row">
                <select id="event_time" name="event_time" class="form-control">
                    <?php

                    //EDIT FOR BETTER TIMING WITH SEPARATE FASCE D'ORARIO
                    $options = [
                        "15:00","15:15","15:30","15:45","16:00","16:15","16:30","16:45",
                        "17:00","17:15","17:30","17:45","18:00","18:15","18:30","18:45",
                        "19:00","19:15","19:30","19:45","20:00","20:15","20:30","20:45",
                        "21:00","21:15","21:30","21:45","22:00","22:15","22:30","22:45",
                        "23:00","23:15","23:30"
                    ];
                    foreach ($options as $option) {
                        printf('<option value="%s" %s>%s</option>', esc_attr($option), selected($option, $time_event, false), esc_html($option));
                    }
                    ?>
                </select>
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

                const typeSelect = document.getElementById('type_event');
                const sportFields = document.getElementById('sport_fields');

                sportFields.style.display = typeSelect.value === 'sport' ? 'block' : 'none';

                typeSelect.addEventListener('change', function () {
                    sportFields.style.display = this.value === 'sport' ? 'block' : 'none';
                });
    
                new Pikaday({
                    field: document.getElementById('event_date'),
                    format: 'DD/MM/YYYY',
                    toString(date) {
                        return moment(date).format('DD/MM/YYYY');
                    },
                    parse(dateString) {
                        return moment(dateString, 'DD/MM/YYYY').toDate();
                    },
                    minDate: new Date(),
                    showToday: true
                });
            });
        </script>
        <?php
    }
    

    function save_tfIpf_meta_event_box_data( $post_id ) {

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
        

        $timestamp = strtotime(str_replace('/', '-', $_POST['event_date']));
        $formattedDate = date('Y-m-d', $timestamp);

        $date_event =  strtotime($formattedDate . " ". $_POST['event_time']);
        $event_type = $_POST['type_event'];
        $teamone = ucfirst(strtolower($_POST['teamone']));;
        $teamtwo = ucfirst(strtolower($_POST['teamtwo']));
        
        
        update_post_meta( $post_id, '_tfIpf_event_date_time', $date_event);
        update_post_meta( $post_id, '_tfIpf_event_type', $event_type );
        update_post_meta( $post_id, '_tfIpf_event_team_one', $teamone );
        update_post_meta( $post_id, '_tfIpf_event_team_two', $teamtwo );

        
    }

   

}