<?php

// include_once dirname( __FILE__ ) . '/TFIP_Database.php';
// include_once dirname( __FILE__ ) . '/TfIpfManager.php';


class TFIP_Admin {
    
    public function __construct() {
        
        add_action( 'admin_menu', array( $this, 'tfipf_settings_page' ) );
        add_action( 'admin_init', array( $this, 'tfipf_register_settings' ) );
        add_action( 'admin_init', array( $this, 'tfipf_settings_fields' ) );
        add_filter( 'plugin_action_links_tfipfpub', array( $this, 'tfipf_add_settings_link' ) );

        add_action( 'admin_notices', array( $this, 'tfipf_show_submitted_inputs' ) );

    }

    public function tfipf_add_settings_link( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=TFIP_settings' ) . '">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    public function tfipf_settings_page() {
        add_options_page( 'TFIP', 'TFIP', 'manage_options', 'TFIP_settings', array( $this, 'TFIP_Admin_Settings_Page_Content' ) );
    }


    /*
        some kind of logging
    */
    public function tfipf_show_submitted_inputs() {


        if (isset($_GET['page']) && $_GET['page'] === 'TFIP_settings' && isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {

            $token = esc_html(get_option('tfip_whatsapp_token'));
            $capienza = esc_html(get_option('tfip_default_capienza'));
            $timeslots = get_option('tfip_timeslots');
    
            echo '<div class="notice is-dismissible">';
            echo '<p><strong>Form Data: </strong></p>';
            echo '<p><strong>Token:</strong> ' . $token . '</p>';
            echo '<p><strong>Default Capienza:</strong> ' . $capienza . '</p>';
    
            if (!empty($timeslots) && is_array($timeslots)) {
                echo '<p><strong>Timeslots:</strong></p>';
                echo '<ul>';
                foreach ($timeslots as $slot) {
                    echo '<li>Start: ' . esc_html($slot['start']) . ', End: ' . esc_html($slot['end']) . ', Capacity: ' . esc_html($slot['capacity']) . '</li>';
                }
                echo '</ul>';
            }
    
            echo '</div>';
        }
    }


    /*
        settings page content 
    */

    public function TFIP_Admin_Settings_Page_Content() {
        ?>
        <div class="wrap">
            <h2>Impostazioni</h2>
            <form method="post" action="options.php">
                <div>
                    <?php settings_fields( 'tfipf_settings_group' ); ?>
                </div>
                
                
                <?php do_settings_sections( 'TFIP_settings' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function tfipf_register_settings() {
        register_setting( 'tfipf_settings_group', 'tfip_whatsapp_token' );

        register_setting( 'tfipf_settings_group', 'tfip_default_capienza', [$this, 'tfipf_sanitize_capienza_value'] );
        
        register_setting('tfipf_settings_group', 'tfip_timeslots', [
            'sanitize_callback' => [$this, 'sanitize_timeslots']
        ]);
        

    }
    

    public function sanitize_timeslots($input) {

        $sanitized = [];
        $total_capacity = 0;

        foreach ($input as $index => $slot) {
            $start = sanitize_text_field($slot['start']);
            $end = sanitize_text_field($slot['end']);
            $capacity = intval($slot['capacity']);

            if ($start && $end) {
                $total_capacity += $capacity;

                $sanitized[$index] = [
                    'start' => $start,
                    'end' => $end,
                    'capacity' => $capacity
                ];
            }
        }

        $default_capienza = intval(get_option('tfip_default_capienza'));

        if ($total_capacity !== $default_capienza) {

            add_settings_error(
                'tfip_timeslots',
                'capacity_mismatch',
                'The sum of timeslot capacities (' . $total_capacity . ') must equal the default capienza (' . $default_capienza . ').',
                'error'
            );

            return get_option('tfip_timeslots');
        }

        return $sanitized;
    }

    

    public function tfipf_settings_fields() {
        add_settings_section( 'tfipf_settings_section', 'Plugin Settings', '', 'TFIP_settings' );
        add_settings_field( 'tfip_whatsapp_token', 'Token Whatsapp', array( $this, 'tfipf_whatsapp_token_callback' ), 'TFIP_settings', 'tfipf_settings_section' );
        add_settings_field( 'tfip_default_capienza', 'Default Capienza', array( $this, 'tfipf_default_capienza_callback' ), 'TFIP_settings', 'tfipf_settings_section' );
        
        add_settings_field( 'add_timeslot_instance_button', 'Add Timeslots', array( $this, 'TFIP_ADMIN_Timeslots_render' ), 'TFIP_settings', 'tfipf_settings_section' );

    }

    public function TFIP_ADMIN_Timeslots_render()
    {
        $saved_timeslots = get_option('tfip_timeslots', []);
        $html = '<div class="container TFIP-style">
                    <div id="ts-container">';
        
        if (!empty($saved_timeslots)) {
            foreach ($saved_timeslots as $i => $slot) {
                $start = esc_attr($slot['start']);
                $end = esc_attr($slot['end']);
                $capacity = esc_attr($slot['capacity']);

                $add_btn = ($i === array_key_last($saved_timeslots)) ? '<button type="button" class="button button-primary" id="' . $i . '_add_timeslot_instance_button" onclick="Create_New_Timeslot_HTML_Instance(' . $i . ')">Add Timeslot</button>' : '';
                $del_btn = ($i !== 0) ? '<button type="button" class="button button-primary" id="' . $i . '_delete_timeslot_instance_button" onclick="Delete_Timeslot_HTML_Instance(' . $i . ')">Remove Timeslot</button>' : '';

                $html .= '
                    <div class="ts_instance" id="' . $i . '_id_ts">
                        <div><p>Timeslot ' . $i . '</p></div>
                        <div><input type="text" class="time-input" id="' . $i . '_timeslot_start" name="tfip_timeslots[' . $i . '][start]" value="' . $start . '" /></div>
                        <div><input type="text" class="time-input" id="' . $i . '_timeslot_end" name="tfip_timeslots[' . $i . '][end]" value="' . $end . '" /></div>
                        <div><input type="number" id="' . $i . '_timeslot_capacity" name="tfip_timeslots[' . $i . '][capacity]" value="'. $capacity .'"/></div>
                        <div>' . $add_btn . ' ' . $del_btn . '</div>
                    </div>';
            }
        } else {
            $html .= '
                <div class="ts_instance" id="0_id_ts">
                    <div><p>Timeslot 0</p></div>
                    <div><input type="text" class="time-input" id="0_timeslot_start" name="tfip_timeslots[0][start]" /></div>
                    <div><input type="text" class="time-input" id="0_timeslot_end" name="tfip_timeslots[0][end]" /></div>
                    <div><input type="number" id="0_timeslot_capacity" name="tfip_timeslots[0][capacity]" /></div>
                    <div>
                        <button type="button" class="button button-primary" id="0_add_timeslot_instance_button" onclick="Create_New_Timeslot_HTML_Instance(0)">Add Timeslot</button>
                    </div>
                </div>';
        }

        $html .= '</div></div>';
        echo $html;
    }


    public function tfipf_whatsapp_token_callback() {
        $string_option_value = get_option( 'tfip_whatsapp_token' );
        echo '<input type="text" name="tfip_whatsapp_token" value="' . esc_attr( $string_option_value ) . '" />';
    }

    public function tfipf_default_capienza_callback() {
        $number_option_value = get_option( 'tfip_default_capienza' );
        echo '<input type="number" name="tfip_default_capienza" value="' . esc_attr( $number_option_value ) . '" />';
    }

    

    function tfipf_sanitize_capienza_value( $input ) {
        $sanitized_capienza = intval( $input );
        return $sanitized_capienza;
    }
}
