<?php

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class TFIP_Database {


    function __construct()
    {
        //add_action( 'wp_ajax_tf_ipf_filter_events', array( $this, 'tfIpf_filter_events'));
    }

    //monitor this function
    public function TFIP_Database_Create_TimeSlots($timeslots_in)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';  

        $created_timeslots = [];
        //$new_day_availability = 0;
        $overlap_verification = 0;

        foreach ($timeslots_in as $timeslot) {
            if (isset($timeslot['id_date'], $timeslot['start'], $timeslot['end'], $timeslot['capacity'], $timeslot['active_bookings'], $timeslot['active'])) {
        
                // Check for overlapping timeslot
                $overlap = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name 
                         WHERE id_date = %d 
                           AND timeslotstart < %s 
                           AND timeslotend > %s",
                        $timeslot['id_date'],
                        $timeslot['end'],      
                        $timeslot['start']   
                    ),
                    ARRAY_A
                );
        
                if ($overlap) {

                    $overlap_verification += 1;         

                }else
                {
                    $existing = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM $table_name 
                            WHERE id_date = %d 
                            AND timeslotstart = %s 
                            AND timeslotend = %s",
                            $timeslot['id_date'],
                            $timeslot['start'],
                            $timeslot['end']
                        ),
                        ARRAY_A
                    );
                    
                    wp_reset_query();
            
                    if (!$existing) {
                        $data = [
                            'id_date' => $timeslot['id_date'],
                            'timeslotstart' => $timeslot['start'],
                            'timeslotend' => $timeslot['end'],
                            'max_bookings' => $timeslot['capacity'],
                            'active_bookings' => $timeslot['active_bookings'],
                            'active' => $timeslot['active']
                        ];
            
                        $format = ['%d', '%s', '%s', '%d', '%d', '%d'];
                        $wpdb->insert($table_name, $data, $format);
                        wp_reset_query();

                        if ($wpdb->insert_id) {

                            $created_timeslots[] = [
                                'ts' => [
                                    'id_slot' => $wpdb->insert_id,
                                    'data_slot' => $data
                                ]
                            ];

                        } else {
                            return [
                                'resolution' => 0,
                                'id_slot' => null,
                                'message' => "Error inserting timeslot with ID: " . $timeslot['id_date']
                            ];
                        }
                    }
                }
        
                
            } else {
                return [
                    'resolution' => 0,
                    'id_slot' => null,
                    'message' => "Missing required data for timeslot with ID: " . $timeslot['id_date']
                ];
            }
        }

        if($overlap_verification == count($timeslots_in)) 
        {  
            return [
                'resolution' => 0, 
                'id_slot' => null,
                'message' => "Overlapping timeslot found for all timeslots given" 
            ];

        }else
        {
            $success = $this->TFIP_Database_Update_Day_Max_Availability($timeslot['id_date']);

            if((bool)((int)$success['resolution']))
            {
                return [
                    'resolution' => 1,
                    'id_slot' => $created_timeslots,
                    'message' => "Timeslots inserted in DB"
                ];
                
            }else
            {
                return [
                    'resolution' => 0,
                    'id_slot' => null,
                    'message' => "Problems in updating max day availability as sum of all slots availability"
                ];
            }
        }
        

    }




    public function TFIP_Database_Create_Active_Day($id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_active_days';

        $default_capienza = get_option('tfip_default_capienza', false);
        
        if($default_capienza != false)
        {
            $data = [
                'id_date' => $id,        
                'day_max' => $default_capienza,      
                'active' => 1         
            ];
            
            $format = ['%d', '%d', '%d'];
            
            $wpdb->insert($table_name, $data, $format);
            
            if ($wpdb->last_error == "") {
                $return_res = [
                    'resolution' => 1,
                    'id_date' => $id
                ];
            } else {
                $return_res = [
                    'resolution' => 0,
                    'id_date' => $id
                ];
            }
            
            

            return $return_res;

        }else
        {
            $return_res = [
                'resolution' => 0
            ];

            return $return_res;
        }
        
        
    }

    public function TFIP_Database_Update_Day_Max_Availability($id_date) {
        
        global $wpdb;

        $newmax = get_option( 'tfip_default_capienza');

        $table_name = $wpdb->prefix . 'tfip_active_days';

        $timeslots = $this->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($id_date);

        $ret = null;
        
        if(count($timeslots) > 0)
        {
            $newmax = array_sum(array_column($timeslots, 'max_bookings'));
            

            $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $id_date));
            wp_reset_query();

            if($result->day_max == $newmax)
            {
                $ret = [
                    'resolution' => 1,
                    'message' => "No need to update",
                ];
                
            }else
            {
                $data = array(
                    'day_max' => $newmax,
                );
            
                $where = array(
                    'id_date' => $id_date,
                );
            
                $wpdb->update($table_name, $data, $where);
            
                $rows_affected = $wpdb->rows_affected;
                wp_reset_query();
                
                if ($rows_affected == 1) {
                    
                    $ret = [
                        'resolution' => 1,
                        'message' => "Updated",
                    ];
                    
                } else {
                    $ret = [
                        'resolution' => 0,
                        'message' => "Failed in updating max availability for day",
                    ];
                }
            }
        }else
        {
            $deleted =  $this->TFIP_Delete_Day($id_date);
            $ret = [
                'resolution' => 0,
                'message' => "Deleted day because no timeslots found",
            ];
        }

        return $ret;
    }

    public function TFIP_Delete_Day($id_date)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_active_days'; // Replace with your actual table name

        $deleted = $wpdb->delete(
            $table_name,
            [ 'id_date' => $id_date ],
            [ '%d' ]
        );

        return $deleted;
    }

    public function TFIP_Database_Block_Unblock_Day($id_date, $status) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_active_days';
        
        $data = array(
            'active' => $status,
        );
    
        $where = array(
            'id_date' => $id_date,
        );
    
        $wpdb->update($table_name, $data, $where);
    
        $rows_affected = $wpdb->rows_affected;
        wp_reset_query();
        
        if ($rows_affected == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function TFIP_Database_Block_Unblock_Timeslot($id_slot, $status) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        
        $data = array(
            'active' => $status,
        );
    
        $where = array(
            'id' => $id_slot,
        );
    
        $result = $wpdb->update($table_name, $data, $where);
        wp_reset_query();
        
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }


    public function TFIP_Database_Get_Single_Booking($booking_id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_bookings';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE idbooking = %d", $booking_id));
        wp_reset_query();

        return $result;
    }


    public function TFIP_Database_Get_Active_Day($id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_active_days';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $id));
        wp_reset_query();

        return $result;
    }

    public function TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($id_date) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslots = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $id_date));
        wp_reset_query();

        return $timeslots;
    }

    public function TFIP_Database_Get_Specific_Timeslot($id_slot) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot));
        wp_reset_query();

        return $timeslot_instance;
    }

    public function TFIP_Database_Get_Date_From_Timeslot($id_slot) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $day_instance = $wpdb->get_row($wpdb->prepare("SELECT id_date FROM $table_name WHERE id = %d", $id_slot));
        wp_reset_query();

        return $day_instance->id_date;
    }
    


    
    public function PrintErrorMessage($message)
    {
        return '<div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">We are sorry: '. $message .'</h4>
        </div>';
    }

    public function tfIpf_get_date($id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipf_days_date';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        $result = $wpdb->get_row($query);

        if ($result !== null) {
            return $result;
        } else {
            return false;
        }
    }

    public function TFIP_Database_Update_Booking($booking_data) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_bookings';
    
        $result = $wpdb->update(
            $table_name,
            [
                'id_timeslot'    => $booking_data['id_new_timeslot'],
                'idpostevent'    => null,//$booking_data['post_event_id'],
                'identification' => $booking_data['identification'],
                'participants'   => $booking_data['participants'],
                'phone'          => $booking_data['phone'],
                'extra_message'  => $booking_data['extra_message'],
                'status'         => $booking_data['status'],
                'booking_time'   => $booking_data['time_booking'],
            ],
            [ 'idbooking' => $booking_data['id_booking'] ],
            [ '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s' ],
            [ '%d' ] 
        );

        if ($result !== false) {
            // Success, even if 0 rows were updated //wordpress database "strange" behaviour
            return true;
        } else {
            // Query failed (e.g., SQL syntax error)
            return false;
        }
    }


    public function tfIpf_update_days_date_bookings($id, $newBookings) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipf_days_date';
    
        $currentBookings = $wpdb->get_var($wpdb->prepare("SELECT bookings FROM $table_name WHERE id = %d", $id));
        $maxParticipants = $wpdb->get_var($wpdb->prepare("SELECT max_participants FROM $table_name WHERE id = %d", $id));

        $updatedBookings = $currentBookings + $newBookings;
        
        if ($updatedBookings > $maxParticipants) {
            return false;
        }
        
        $data = array(
            'bookings' => $updatedBookings,
        );
    
        $where = array(
            'id' => $id,
        );
    
        $result = $wpdb->update($table_name, $data, $where);
    
        //$rows_affected = $wpdb->rows_affected;
        
        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }


    public function TFIP_Database_Get_Single_Timeslot_Max_Bookings($id_slot)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot), ARRAY_A);

        $ret = null;

        if($timeslot_instance)
        {
            $ret = [
                'timeslot_id' => $id_slot,
                'resolution' => 1,
                'message' => 'Got timeslot instance, Availabilty is unchanged',
                'day_id' => $timeslot_instance['id_date'],
                'updated_availability' => $timeslot_instance['max_bookings']
            ];

        }else{

            $ret = [
                'timeslot_id' => $id_slot,
                'resolution' => 0,
                'message' => 'Could not get timeslot instance',
                'day_id' => $timeslot_instance['id_date'],
                'updated_availability' => 0
            ];

        }
        

        return $ret;
    }


    //to be called always after timeslot creation or booking change
    public function TFIP_Database_Update_Max_Capacity($id_slot, $new_max)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot), ARRAY_A);
        wp_reset_query();
        $ret = null;

        if($timeslot_instance)
        {
            $active_bookings = (int)$timeslot_instance['active_bookings'];
            
            if($active_bookings <= $new_max)
            {
                $table_name = $wpdb->prefix . 'tfip_timeslot_instances';

                $data = array(
                    'max_bookings' => $new_max,
                );

                $where = array(
                    'id' => $id_slot,
                );
            
                $updated = $wpdb->update($table_name, $data, $where);
                


                if ($updated === false) {
                                        
                    $ret = [
                        'resolution' => 0,
                        'message' => 'Failed to update active_bookings: ' . $wpdb->last_error,
                        'updated_availability' => 0
                    ];
                    wp_reset_query();
                    return $ret;

                }else
                {
                    wp_reset_query();

                    $success = $this->TFIP_Database_Update_Day_Max_Availability($timeslot_instance['id_date']);

                    if((bool)((int)$success['resolution']))
                    {
                        $ret = [
                            'resolution' => 1,
                            'message' => 'OK',
                            'updated_availability' => $timeslot_instance['max_bookings'] - $active_bookings
                        ];
                        
                    }else
                    {

                        $ret = [
                            'resolution' => 0,
                            'message' => 'Cannot update day total availability',
                            'updated_availability' => 0
                        ];

                    }

                    wp_send_json($ret);

                }


            }else
            {
                $ret = [
                    'resolution' => 0,
                    'message' => 'Cannot set max availability less than already present booking participants',
                    'updated_availability' => 0
                ];

                wp_send_json($ret);
            }
        }
    }


    //if this function is called after an update feed it with the already calculated difference
    // between original participants and new participants
    // int is not unsigned and it will work on negative number as well
    public function TFIP_Database_Update_Timeslots_Availability($id_slot, $participants)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot), ARRAY_A);
        wp_reset_query();


        $ret = null;

        if($timeslot_instance)
        {
            $new_active_bookings = 0;
            
            $new_active_bookings = (int)$timeslot_instance['active_bookings'] + (int)$participants;
            
            if($new_active_bookings <= $timeslot_instance['max_bookings'])
            {
                $table_name = $wpdb->prefix . 'tfip_active_days';

                $same_slot_bookings = $timeslot_instance['active_bookings'] + (int) $participants;

                if($same_slot_bookings < 0)
                {
                    return [
                        'resolution' => 0,
                        'message' => "Removing too many participants. not allowed",
                        'updated_availability' => 0
                    ];
                    
                }else
                {
                
                    if($timeslot_instance['max_bookings'] >= $same_slot_bookings )
                    {
                        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';

                        $data = array(
                            'active_bookings' => $same_slot_bookings,
                        );

                        $where = array(
                            'id' => $id_slot,
                        );
                    
                        $updated = $wpdb->update($table_name, $data, $where);

                        if ($updated === false) {
                            
                            return  [
                                'resolution' => 0,
                                'message' => 'Failed to update active_bookings: ' . $wpdb->last_error,
                                'updated_availability' => 0
                            ];                        

                        }else
                        {
                        
                            return [
                                'resolution' => 1,
                                'message' => 'OK',
                                'updated_availability' => $timeslot_instance['max_bookings'] - $same_slot_bookings
                            ];

                        }
                        
                    }else
                    {
                        return [
                            'resolution' => 0,
                            'message' => "Trying to add too many participants",
                            'updated_availability' => 0
                        ];
                    }
                }

            }else
            {
                $ret = [
                    'resolution' => 0,
                    'message' => 'Max limit reached cannot crete booking',
                    'updated_availability' => 0
                ];
                
            }

        }else
        {
            $ret = [
                'resolution' => 0,
                'message' => 'error in TFIP_Database_Update_Timeslots_Availability cannot find timeslots',
                'updated_availability' => 0
            ];
            
        }
        
        
        return $ret;

    }

   

    public function tfIpf_filter_events()
    {
        if(isset($_POST['usersearch'])) {

            $searchText = sanitize_text_field($_POST['usersearch']);
            $searchText = strtolower($searchText);
    
            $query_tfIpf = new WP_Query(
                array(
                    's' => $searchText,
                    'post_type' => 'tfipfevent',
                    'post_status' => 'publish',
                )
            );
            
    
            // Output the filtered events as options for select
            if ($query_tfIpf->have_posts()) {
                while ($query_tfIpf->have_posts()) {

                    $query_tfIpf->the_post();
                    $pid = get_the_ID();
                    $date_event_timestamp = get_post_meta( $pid, '_tfIpf_event_date_time', true );

                    if(!empty($date_event_timestamp))
                    {
                        $date_event = date('d/m/Y', $date_event_timestamp);
                    }

                    echo'<option value="' . $pid . '">' . get_the_title() . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data Evento: ' . $date_event . '</span></option>';

                }
                wp_reset_postdata();
            } else {
                echo '<option>No events found</option>';
            }

            echo '<option value="-1"> Tavolo </span></option>';
        }
    
        wp_die();
    }

    public function tfIpf_event_query_list($maxnum = -1)
    {
    
        $timestamp_now = strtotime(date('Y-m-d'));

        $events = array();

        $query_tfIpf = new WP_Query(
            array(
                'post_type'      => 'tfipfevent',
                'post_status'    => 'publish',
                'posts_per_page' => $maxnum,
                'meta_query'     => array(
                    array(
                        'key'     => '_tfIpf_event_date_time',
                        'value'   => $timestamp_now,
                        'type'    => 'NUMERIC',
                        'compare' => '>=',
                    )
                ),
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_tfIpf_event_date_time',
                'order'          => 'ASC'
            )
        );

        while($query_tfIpf->have_posts())
        {
            $query_tfIpf->the_post();

            $the_single_ipf = new stdClass();
            $the_single_ipf->id = get_the_ID();
            $the_single_ipf->title = get_the_title($the_single_ipf->id);
            
            $the_single_ipf->event_description = get_post_meta( $the_single_ipf->id, '_tfIpf_event_description', true );
            
            $the_single_ipf->time_event = date('H:i', get_post_meta( $the_single_ipf->id, '_tfIpf_event_date_time', true ));
            $the_single_ipf->date_event = date('Y-m-d',  get_post_meta( $the_single_ipf->id, '_tfIpf_event_date_time', true ));

            $the_single_ipf->event_type = get_post_meta($the_single_ipf->id, '_tfIpf_event_type', true);
            $the_single_ipf->image_p = get_post_meta( $the_single_ipf->id, '_tfIpf_event_image', true );
            $the_single_ipf->teamone = get_post_meta($the_single_ipf->id, '_tfIpf_event_team_one', true);
            $the_single_ipf->teamtwo = get_post_meta($the_single_ipf->id, '_tfIpf_event_team_two', true);
            $the_single_ipf->max_participants = get_post_meta($the_single_ipf->id, 'squadre', true);

            $the_single_ipf->available = "to be calculated";

            array_push($events, $the_single_ipf);
        }

        wp_reset_query();

        return $events;
    }



    

    public function tfIpf_verify_code($id, $usercode)
    {
        $return_arr = array();

        global $wpdb;
        $table_name = $wpdb->prefix . 'ipf_bookings'; 

        $booking_code = $wpdb->get_var(
            $wpdb->prepare("SELECT code FROM $table_name WHERE id = %d", $id)
        );

        
        if ($booking_code !== null && strtoupper($usercode) == strtoupper($booking_code)) {
            
            
            $new_status = 'confirmed';

            $result = $wpdb->update(
                $table_name,
                array('status' => $new_status),
                array('id' => $id),
                array('%s'), 
                array('%d') 
            );

            if ($result !== false) {
                
                $single_booking = $wpdb->get_row(
                    $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)
                );
                $return_arr = array('error' => 0, 'booking' => $single_booking);

            } else {

                $return_arr = array('error' => 1, 'error_message' => 'error updating booking');
            }

        } else {

            $return_arr = array('error' => 2, 'error_message' => 'booking does not exist');
        }

        return $return_arr;
    }



    public function TFIP_Database_generate_code()
    {
        global $wpdb;

        $arr_charN = 'qwertyuipasdfghjklzxcvbnm123456789';
        $code = '';
     
        for ($x = 0; $x < 6; $x++) {
            $rand_ind = rand(0, strlen($arr_charN) - 1); // Subtract 1 to get a valid index
            $code .= $arr_charN[$rand_ind];
        }
        
        $return_arr = array();

        
        $table_name = $wpdb->prefix . 'ipf_bookings'; 

        $return_arr = $wpdb->get_col(
            $wpdb->prepare("SELECT code FROM $table_name")
        );


        $code = strtoupper($code);
        
        if (in_array($code, $return_arr)) {

            return $this->TFIP_Database_generate_code();

        } else {
            
            return $code;
        }
        

        
        return strtoupper($code);
    }



    // function ipf_save_edit_event($event)
    // {
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'ipf_events';

    //     $event_id = $event->event_id;

    //     $result = $wpdb->update(
    //         $table_name,
    //         array(
    //             'event_title' => $event->event_title,
    //             'date_event' =>  $event->date_event,
    //             'type_event' => $event->event_type,
    //             'maxnum' => $event->eventPlaces,
    //             'description_event' => $event->event_description,
    //             'image_path' =>  $event->image_url,
    //         ),
    //         array('event_id' => $event_id), // Update based on event ID
    //         array('%s', '%s', '%s', '%d', '%s', '%s'),
    //         array('%d')
    //     );

    //     if ($result === false) {
    //         $error_message = $wpdb->last_error;
    //         echo "Error updating data: $error_message";
    //         return false;
    //     } else {
    //         return true;
    //     }

    // }

    public function TFIP_Database_Get_Timeslot_Bookings($slot_id)
    {
        global $wpdb;

        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tfip_bookings WHERE id_timeslot = %d",
                (int)$slot_id
            )
        );

        wp_reset_query();

        return $bookings;
    }


    public function TFIP_Database_Delete_Single_Timeslot($timeslot_id)
    {

        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances'; 

        $deleted = $wpdb->delete(
            $table_name,
            [ 'id' => $timeslot_id ],
            [ '%d' ]
        );

        return $deleted;
    }


    public function TFIP_Database_Get_Timeslot_Confirmed_Bookings($slot_id)
    {
        global $wpdb;

        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tfip_bookings WHERE id_timeslot = %d AND status = 1",
                (int)$slot_id
            )
        );

        wp_reset_query();

        return $bookings;
    }


    public function TFIP_Database_query_date($id_day)
    {
       
        global $wpdb;

        $active_day = $this->TFIP_Database_Get_Active_Day($id_day);
        $active_bookings = 0;
        $day_availability = 0;
        $total_customers_in = 0;

        $capienza = esc_html(get_option('tfip_default_capienza'));
        $formatted_timeslots = [];

        if($active_day)
        {
            $timeslots = $this->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($active_day->id_date);

            if (count($timeslots) != 0) {

               
                foreach ($timeslots as $ts) {


                    $bookings = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}tfip_bookings WHERE id_timeslot = %d",
                            (int)$ts->id
                        )
                    );

                    wp_reset_query();

                    $booking_confirmed =[];
                    $booking_n_confirmed = [];

                    
                    $local_customers_count = 0;

                    foreach($bookings as $bk)
                    {
                        if((bool)((int)$bk->status))
                        {
                            $active_bookings += 1;
                            $day_availability += $ts->max_bookings - $bk->participants;
                            $local_customers_count += $bk->participants;

                            $booking_confirmed[] = $bk;
                        }else
                        {
                            $booking_n_confirmed[] = $bk;
                        }                        
                    }

                    $total_customers_in += $local_customers_count;

                    $formatted_timeslots[] = [
                        'timeslot_id' => $ts->id,
                        'timeslot_active_bookings' => isset($ts->active_bookings) ? $ts->active_bookings : 0,
                        'timeslot_availability' => isset($ts->max_bookings) ? $ts->max_bookings : null,
                        'start' => $ts->timeslotstart,
                        'end' => $ts->timeslotend,
                        'max_bookings' => $ts->max_bookings,
                        'active_bookings' => $ts->active_bookings,
                        'status' => $ts->active,
                        'confirmed_bookings' => $booking_confirmed,
                        'confirmed_n_bookings' => $booking_n_confirmed
                    ];
                    
                }
            }
            else
            {
               
                $timeslots = get_option('tfip_timeslots', []);
            
                foreach ($timeslots as $ts) {
                    $formatted_timeslots[] = [
                        'timeslot_id' => -1,
                        'timeslot_active_bookings' => 0,
                        'timeslot_availability' => isset($ts['capacity']) ? $ts['capacity'] : 0,
                        'start' => $ts['start'],
                        'end' => $ts['end'],
                        'max_bookings' => $ts['capacity'],
                        'active_bookings' => 0,
                        'status' => -1,
                        'confirmed_bookings' => 0,
                        'confirmed_n_bookings' => 0
                    ];
                }

                $day_availability += $capienza;
                
            }

        }else
        {
            $timeslots = get_option('tfip_timeslots', []);
            
            foreach ($timeslots as $ts) {
                $formatted_timeslots[] = [
                    'timeslot_id' => -1,
                    'timeslot_active_bookings' => 0,
                    'timeslot_availability' => isset($ts['capacity']) ? $ts['capacity'] : 0,
                    'start' => $ts['start'],
                    'end' => $ts['end'],
                    'max_bookings' => $ts['capacity'],
                    'active_bookings' => 0,
                    'status' => -1,
                    'confirmed_bookings' => 0,
                    'confirmed_n_bookings' => 0
                ];
            }

           
            $day_availability += $capienza;

        }
        
        
        // Build final object
        $obj = [
            'id_day' => $id_day,
            'day_status' => (int)$active_day->active,
            'timeslots' => $formatted_timeslots,
            'total_bookings' => $active_bookings,
            'max_day_availability' => $active_day == null ? $capienza : $active_day->day_max,
            'day_availability' => $active_day == null ? $capienza : $active_day->day_max - $total_customers_in,
            'customers_in' => $total_customers_in
        ];

        
        wp_reset_query();

        return $obj;
        
        
       
    }

    public function EditMaxCapacity($timestamp_day, $capacity)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipf_days_date';
        $ddate = intval($timestamp_day);

        $query = $wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ipf_days_date
            WHERE id = %d
        ", $ddate); 

        $res = $wpdb->get_results($query);

        $query = $wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ipf_bookings
            WHERE date_id = %d
        ", $res[0]->id); 

        $objects_bookings = $wpdb->get_results($query);

        $totalBookings = 0;

        foreach ($objects_bookings as $b) {

            if($b->status == "confirmed")
            {
                $totalBookings += $b->participants;
            }
        }


        if($capacity - $totalBookings < 0)
        {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'ipf_days_date';

        $result = $wpdb->update(
            $table_name,
            array('max_participants' => intval($capacity)),
            array('id' => $ddate),
            array('%d'), 
            array('%d') 
        );

        if ($result !== false) {
            return true;
        } else {
           return false;
        }

    }

    public function TFIP_Database_drop_tables()
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'tfip_bookings',
            $wpdb->prefix . 'tfip_events',
            $wpdb->prefix . 'tfip_timeslot_instances',
            $wpdb->prefix . 'tfip_active_days',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
    }

    public function TFIP_Booking_Create_And_Return_Default_Timeslots($id_day_date)
    {
        $timeslots  = get_option('tfip_timeslots', []);

        foreach ($timeslots as &$ts) {
            $ts['id_date'] = $id_day_date;
            $ts['active_bookings'] = 0;  
            $ts['active'] = 1;          
        }

        $res_timeslots = $this->TFIP_Database_Create_TimeSlots($timeslots);
        $ret = null;

        switch ($res_timeslots['resolution']) {
            case 0:
                {
                    $ret = [
                        'resolution' => 0,
                        'timeslots' => null,
                        'message' => 'NOPE'
                    ];
                }
                break;
            case 1:
            {
                $timeslots_objects = (array) $res_timeslots['id_slot'];
        
                $ret = [
                    'resolution' => 1,
                    'timeslots' => $timeslots_objects,
                    'message' => 'OK'
                ];
            }
            break;
            case 2:
            {
                //some timeslot are overlapping. timeslots is not being created
                //apply procedure to save bookings and reset timeslots for the day.

                $ret = [
                    'resolution' => 0,
                    'timeslots' => null,
                    'message' => 'NOPE'
                ];

            }
        }
        

        return $ret;

    }

    public function TFIP_Database_create_tables() {
  
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_active_days = $wpdb->prefix . 'tfip_active_days';
        $table_timeslot_instances = $wpdb->prefix . 'tfip_timeslot_instances';
        $table_tfip_events = $wpdb->prefix . 'tfip_events';
        $table_bookings = $wpdb->prefix . 'tfip_bookings';

        $sql = [];

        // Table 1: active_days
        $sql[] = "CREATE TABLE $table_active_days (
            id_date BIGINT UNSIGNED NOT NULL,
            day_max INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id_date)
        ) $charset_collate;";

        // Table 2: timeslot_instances
        $sql[] = "CREATE TABLE $table_timeslot_instances (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_date BIGINT UNSIGNED NOT NULL,
            timeslotstart CHAR(5) NOT NULL,
            timeslotend CHAR(5) DEFAULT NULL,
            max_bookings INT NOT NULL DEFAULT 0,
            active_bookings INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            FOREIGN KEY (id_date) REFERENCES $table_active_days(id_date) ON DELETE CASCADE
        ) $charset_collate;";

        // Table 3: tfip_events   ---> remove this table
        $sql[] = "CREATE TABLE $table_tfip_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_timeslot BIGINT UNSIGNED NOT NULL,
            idpostevent BIGINT UNSIGNED NOT NULL,
            bypass_timeslot INT NOT NULL DEFAULT 0,
            max_bookings_event INT DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            FOREIGN KEY (id_timeslot) REFERENCES $table_timeslot_instances(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Table 4: bookings
        $sql[] = "CREATE TABLE $table_bookings (
            idbooking BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_timeslot BIGINT UNSIGNED NOT NULL,
            idpostevent BIGINT UNSIGNED DEFAULT NULL,
            identification VARCHAR(255) NOT NULL,
            participants INT NOT NULL DEFAULT 1,
            phone VARCHAR(20) NOT NULL,
            extra_message TEXT,
            code VARCHAR(6),
            status TINYINT(1) NOT NULL DEFAULT 0,
            booking_time CHAR(5) NOT NULL,
            PRIMARY KEY (idbooking),
            FOREIGN KEY (id_timeslot) REFERENCES $table_timeslot_instances(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }
          
    }

    public function TFIP_Query_Events_All_Or_For_Date($id_date = null, $timestamp_start_timeslot = null, $maxnum = -1)
    {
    
        $timestamp_date = strtotime(date('Y-m-d'));

        if($id_date)
        {
            $timestamp_date = $id_date;
        }

        if($timestamp_start_timeslot)
        {
            $timestamp_date == $timestamp_start_timeslot;
        }


        $events = array();

        $query_tfIpf = new WP_Query(
            array(
                'post_type'      => 'tfipfevent',
                'post_status'    => 'publish',
                'posts_per_page' => $maxnum,
                'meta_query'     => array(
                    array(
                        'key'     => '_tfIpf_event_date_time',
                        'value'   => $timestamp_date,
                        'type'    => 'NUMERIC',
                        'compare' => '>=',
                    )
                ),
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_tfIpf_event_date_time',
                'order'          => 'ASC'
            )
        );

        while($query_tfIpf->have_posts())
        {
            $query_tfIpf->the_post();

            $the_single_ipf = new stdClass();
            $the_single_ipf->id = get_the_ID();
            $the_single_ipf->title = get_the_title($the_single_ipf->id);
            
            $the_single_ipf->event_description = get_post_meta( $the_single_ipf->id, '_tfIpf_event_description', true );
            
            $the_single_ipf->time_event = date('H:i', get_post_meta( $the_single_ipf->id, '_tfIpf_event_date_time', true ));
            $the_single_ipf->date_event = date('Y-m-d',  get_post_meta( $the_single_ipf->id, '_tfIpf_event_date_time', true ));

            $the_single_ipf->event_type = get_post_meta($the_single_ipf->id, '_tfIpf_event_type', true);
            $the_single_ipf->image_p = get_post_meta( $the_single_ipf->id, '_tfIpf_event_image', true );
            $the_single_ipf->teamone = get_post_meta($the_single_ipf->id, '_tfIpf_event_team_one', true);
            $the_single_ipf->teamtwo = get_post_meta($the_single_ipf->id, '_tfIpf_event_team_two', true);
            $the_single_ipf->max_participants = get_post_meta($the_single_ipf->id, 'squadre', true);

            $the_single_ipf->available = "to be calculated";

            array_push($events, $the_single_ipf);
        }

        wp_reset_query();

        return $events;
    }


    function TFIP_Database_Query_Day_Data($timestamp)
    {
        $obj = null;
        $calendar_data = $this->TFIP_Database_query_date($timestamp);
        $events = $this->TFIP_Query_Events_All_Or_For_Date($timestamp);
        
        $fmt = new IntlDateFormatter(
            'it_IT',                 
            IntlDateFormatter::FULL, 
            IntlDateFormatter::NONE, 
            'Europe/Rome',           
            IntlDateFormatter::GREGORIAN
        );
        
        $formattedDate = $fmt->format($calendar_data['id_day']);

        $calendar_data['formatted_date'] =  $formattedDate;

        $obj=[
            'calendars' => $calendar_data,
            'events' => $events
        ];
        
        return $obj;
    }

    
    

}