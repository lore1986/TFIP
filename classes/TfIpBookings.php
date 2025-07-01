<?php

use SimplePie\Parse\Date;

include_once dirname( __FILE__ ) . '/TFIP_Database.php';
// include_once dirname( __FILE__ ) . 'TfIpfManager.php';


class TfIpBooking {

    private TFIP_Database $_ipfDatabase;
    private TfIpManager $_manager;


    function __construct(TFIP_Database $database, TfIpManager $manager)
    {
        $this->_ipfDatabase = $database;

        $this->_manager = $manager;

        add_action( 'wp_ajax_tfip_getBookingData', array( $this, 'tfip_getBookingData'));
        add_action( 'wp_ajax_nopriv_tfip_getBookingData', array( $this, 'tfip_getBookingData'));
        add_action( 'wp_ajax_tfip_confirmBooking', array( $this, 'tfip_confirmBooking'));
        add_action( 'wp_ajax_nopriv_tfip_confirmBooking', array( $this, 'tfip_confirmBooking'));
        add_action( 'wp_ajax_tf_ipf_confirm_booking', array( $this, 'tfIpf_final_booking_confirm'));
        add_action( 'wp_ajax_nopriv_tf_ipf_confirm_booking', array( $this, 'tfIpf_final_booking_confirm'));


        add_action( 'wp_ajax_tfip_get_single_booking', array( $this, 'TFIP_Booking_Get_Single_Booking'));

        
        add_action( 'wp_ajax_tfip_admin_create_booking', array( $this, 'tfip_admin_create_booking'));
        add_action( 'wp_ajax_tfip_admin_update_booking', array( $this, 'TFIP_Booking_Update_Booking'));

        //add_action( 'rest_api_init', array($this,'show_booking_endpoint') );

        add_action('init', [$this,'booking_rewrite_rule']);
        add_filter('query_vars', [$this, 'add_custom_query_var']);

        add_filter('template_include', [$this, 'load_custom_plugin_template']);


    }

    //display booking to customer
    function booking_rewrite_rule() {
        add_rewrite_rule(
            '^booking/([^/]*)/?',
            'index.php?booking_code=$matches[1]',
            'top'
        );
    }

    function add_custom_query_var($vars) {
        $vars[] = 'booking_code';
        return $vars;
    }
    
    
    function load_custom_plugin_template($template) {
        $booking_code = get_query_var('booking_code');
    
        if ($booking_code) {
            $file = plugin_dir_path(__FILE__) . '../template/php-templates/TFIP_Single_Booking_Public.php';
            if (file_exists($file)) {
                return $file;
            }
        }
    
        return $template;
    }
    

    function TFIP_Booking_Get_Single_Booking()
    {
        $booking_id = isset($_POST['bookingId']) ? intval($_POST['bookingId']) : 0;
        
        $obj = null;

        if(!$booking_id || $booking_id == 0)
        {
            $obj = [
                'resolution' => 0,
                'message' => 'You really should not be here. you are cheating with the url parameters',
                'booking' => null,
                'timeslot' => null,
                'alltimeslots' => null,
                'postevent' => null
            ];


        }else
        {

            
            if($booking_id && $booking_id != 0)
            {
                $single_booking = $this->_ipfDatabase->TFIP_Database_Get_Single_Booking($booking_id);

                if($single_booking)
                {
                    $timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($single_booking->id_timeslot);
                    $day_timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($timeslot->id_date);
                    $targetTime = (new DateTime($single_booking->booking_time))->format('H:i');
                    
                    if($timeslot)
                    {

                        $slots = [];

                        if (count($day_timeslots) == 1) {

                            $start = new DateTime($day_timeslots[0]->timeslotstart);
                            $end = new DateTime($day_timeslots[0]->timeslotend);
        
                            $interval = new DateInterval('PT15M');
                            $period = new DatePeriod($start, $interval, $end);
        
                            foreach ($period as $time) {

                                $slot_time = $time->format('H:i');

                                $slots[] = [
                                    'ids' => $day_timeslots[0]->id,
                                    'objt' => $time->format('H:i'),
                                    'valt' => $time->format('H:i'),
                                    'sel' => ($slot_time === $targetTime) ? 1 : 0
                                ];
                            }
        
                            // $slots[] = [
                            //     'ids' => $day_timeslots[0]->id,
                            //     'objt' => $end->format('H:i'),
                            //     'valt' => $end->format('H:i'),
                            //     'sel' => ($slot_time === $targetTime) ? 1 : 0
                            // ];


                        } else {

                            foreach ($day_timeslots as $ts) {
                                $start = new DateTime($ts->timeslotstart);
                                $end = new DateTime($ts->timeslotend);
        
                                $slots[] = [
                                    'ids' => $ts->id,
                                    'objt' => $start->format('H:i') . ' - ' . $end->format('H:i'),
                                    'valt' => $start->format('H:i'),
                                    'sel' => ($start->format('H:i') === $targetTime) ? 1 : 0
                                ];
                            }
                        }
                        
                        $event_instance = null;

                        if($single_booking->idpostevent)
                        {
                            $event_instance = get_post($single_booking->idpostevent);
                        }


                        $obj = [
                            'resolution' => 1,
                            'message' => 'OK',
                            'booking' => $single_booking,
                            'timeslot' => $timeslot,
                            'alltimeslots' => $slots,
                            'postevent' => $event_instance
                        ];

                        

                        // if ($event_post && $event_post->post_type === 'tfipfevent') {
                        //     // Access data
                        //     $title = $event_post->post_title;
                        //     $content = $event_post->post_content;

                        //     // Example: get custom field
                        //     $event_datetime = get_post_meta($event_id, '_tfIpf_event_date_time', true);

                        //     // Output or return data
                        //     echo "Title: $title, Date: $event_datetime";
                        // }


                    }else
                    {
                        $obj = [
                            'resolution' => 0,
                            'message' => 'You really should not be here. Timeslot instance cannot be found',
                            'booking' => null,
                            'timeslot' => null,
                            'alltimeslots' => null,
                            'postevent' => null
                        ];
                    }

                }else
                {
                    $obj = [
                        'resolution' => 0,
                        'message' => 'Cannot find Booking with the specified Id',
                        'booking' => null,
                        'timeslot' => null,
                        'alltimeslots' => null,
                        'postevent' => null
                    ];
                }

            }else
            {
                $obj = [
                    'resolution' => 0,
                    'message' => 'Booking id is not valid, do not hack us please',
                    'booking' => null,
                    'timeslot' => null,
                    'alltimeslots' => null,
                    'postevent' => null
                ];
            }
        }

        wp_send_json($obj);
    }


    /* 
        Function to create booking in the prenotazioni page

        TABLES : wp_tfip_bookings : wp_tfip_timeslot_instances : wp_tfip_active_days

        BOOKING:  	idbooking 	id_timeslot 	idpostevent 	identification 	participants 	phone 	extra_message 	code 	status 	booking_time 	
        TIMESLOT:  	id 	id_date 	timeslotstart 	timeslotend 	max_bookings   active_bookings	 active 	
        ACTIVE DAY:  	id_date 	day_max  active 	


    */

    
    public function TFIP_Booking_Create_Client_Booking(
        $id_timeslot, $identification, $participants, $phone, $extra_message, $code, $status, $timebooking, $post_event_id = 0
    ) {

        global $wpdb;

     
        $table_name = $wpdb->prefix . 'tfip_bookings';
        $timebooking = $timebooking->format('H:i');
        //$status_int = $status == "confirmed" ? 1 : 0;
        $posteventnullable = $post_event_id == 0 ? null : $post_event_id;
    
        // Prepare data array
        $data = array(
            'id_timeslot' => $id_timeslot,
            'idpostevent' => $posteventnullable,
            'identification' => $identification,
            'participants' => $participants,
            'phone' => $phone,
            'extra_message' => $extra_message,
            'code' => $code,
            'status' => $status,
            'booking_time' => $timebooking,  
        );
    

        $success = $wpdb->insert($table_name, $data);
        
        if ($success !== false) {

            $ret = [
                'id_booking' => $wpdb->insert_id,
                'resolution' => 1,
                'participants' => $participants,
                'id_timeslot' => $id_timeslot
            ];

            return $ret;

        } else {
            
            $ret = [
                'id_booking' => -1,
                'resolution' => 0,
                'participants' => 0,
                'id_timeslot' => $id_timeslot
            ];

            return $ret;
            
        }
    }

    public function TFIP_Booking_Update_Booking()
    {
        // date_booking d-m-Y
        // time_booking (wrong) 17.00
        // postevent_id 143..
        // identification 
        // participants
        // phone
        // extra_message
        // status = confirmed / forwarded
        // id = (id slot) 
        // action


        $booking_data = [
            'id_original_slot' => intval($_POST['original_timeslot']),
            'id_new_timeslot'  => isset($_POST['id_new_timeslot']) &&  $_POST['id_new_timeslot'] != "" ? intval($_POST['id_new_timeslot']) : null,
            'id_booking'       => intval($_POST['id_booking']),
            'date_booking'     => (new DateTime(sanitize_text_field($_POST['date_booking'])))->getTimestamp(),
            'post_event_id'    => intval($_POST['postevent_id']),
            'time_booking'     => (new DateTime(sanitize_text_field($_POST['time_booking'])))->format('H:i'),
            'identification'   => sanitize_text_field($_POST['identification']),
            'phone'            => sanitize_text_field($_POST['phone']),
            'participants'     => intval($_POST['participants']),
            'extra_message'    => sanitize_textarea_field($_POST['extra_message']),
            'status'           => sanitize_text_field($_POST['status']) === 'confirmed' ? 1 : 0
        ];

        
        $validation = $this->TFIP_Booking_Check_Update_Booking($booking_data);
        $res = null;


        if($validation['resolution'] == 1)
        {
            // 'new_timeslot' => $update_booking_timeslot,
            // 'original_timeslot' => $original_timeslot,
            // 'original_booking' => $original_booking,
            // 'new_booking' => $booking_data,
            // 'resolution' => 1,
            // 'active_day' => $new_active_day,
            // 'message' => 'OK' 

            $booking_data = $validation['new_booking'];
            $booking_data['post_event_id'] = null; //here fix event for now if change reset only connected event //check on date and time
            
            $succ = $this->_ipfDatabase->TFIP_Database_Update_Booking($booking_data);

            $original_timeslot = $validation['original_timeslot'];
            $original_booking = $validation['original_booking'];

            $removed_participants = 0;
            $new_participants = 0;
         
            if($booking_data['status'] > (int)$original_booking->status) {
                $removed_participants = 0;
                $new_participants = $booking_data['participants'];
            }
            else if($booking_data['status'] == (int)$original_booking->status)
            {
                if($booking_data['status'] != 0)
                {
                    $removed_participants = $original_booking->participants; 
                    $new_participants = $booking_data['participants'];
                }
            }
            else {
                $removed_participants = $original_booking->participants; 
                $new_participants = 0;
            }
    

            $res = $this->_ipfDatabase->TFIP_Database_Update_Timeslots_Availability($original_timeslot->id, - (int)$removed_participants);
            
            if($res['resolution'] == 1)
            {
                $res = $this->_ipfDatabase->TFIP_Database_Update_Day_Max_Availability($original_timeslot->id_date);

                if((bool)((int)$res['resolution']))
                {
                    $res = $this->_ipfDatabase->TFIP_Database_Update_Timeslots_Availability($booking_data['id_new_timeslot'], (int)$new_participants);
                    
                    if((bool)((int)$res['resolution']))
                    {
                        $res = $this->_ipfDatabase->TFIP_Database_Update_Day_Max_Availability($booking_data['date_booking']);

                        if((bool)((int)$res['resolution']))
                        {
                            $res_e = [
                                'resolution'  => $res['resolution'],
                                'message' => "Booking Updated",
                                'date_booking' => $booking_data['date_booking']
                            ];
        
                            $res = $res_e;
                        }

                    }
                }
                else
                {
                    $res_e = [
                        'resolution'  => $res['resolution'],
                        'message' => $res['message'],
                        'date_booking' => $booking_data['date_booking']
                    ];

                    $res = $res_e;
                    
                }

            }else
            {
                $res_e = [
                    'resolution'  => $res['resolution'],
                    'message' => $res['message'],
                    'date_booking' => $booking_data['date_booking']
                ];

                $res = $res_e;
                
            }
            
            wp_send_json($res);

        }else
        {
            $res = [
                'resolution'  => $validation['resolution'],
                'message' => $validation['message'],
                'date_booking' => $booking_data['date_booking']
            ];
        }

        wp_send_json($res);

    }

    public function TFIP_Booking_Get_Create_Format_Timeslots($day_timestamp)
    {
        $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($day_timestamp);
        
        if(count($timeslots) == 0)
        {
            $res_slots = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($day_timestamp);

            if($res_slots['resolution'] == 1)
            {
                $default_timeslots_obj = $res_slots['timeslots'];

                foreach ($default_timeslots_obj as $entry) {
                    $slot = new stdClass();
                    $slot->id = (string) $entry['ts']['id_slot'];
                
                    foreach ($entry['ts']['data_slot'] as $key => $value) {
                        $slot->$key = (string) $value; 
                    }
                
                    $timeslots[] = $slot;
                }

                return [
                    'timeslots' => $timeslots,
                    'resolution' => 1,
                    'message' => "Timeslots Created"
                ];
            
            }else
            {
                return [
                    'timeslots' => null,
                    'resolution' => 0,
                    'message' => "Error creating default timeslots"
                ];
            }
        }else
        {
            return [
                'timeslots' => $timeslots,
                'resolution' => 1,
                'message' => "Timeslots Founded"
            ];
        }

    }

    public function TFIP_Booking_Get_Create_Active_Day($day_timestamp)
    {
        $new_active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($day_timestamp);

        if($new_active_day == null)
        {
            $day_res = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($day_timestamp);
            $new_active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($day_timestamp); 
        }

        return $new_active_day;


        // if($day_res['resolution'] != 1)
        // {
        //     return [
        //         'timeslot_id' => null,
        //         'resolution' => 0,
        //         'day_id' => null,
        //         'message' => "Error in creating the day instance. you should not be here"
        //     ];
        // }else
        // {
        //     $new_active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($new_active_day_id);
        // }
        // if(!$new_active_day)
        // {
        //     return [
        //         'timeslot_id' => null,
        //         'resolution' => 0,
        //         'day_id' => null,
        //         'message' => "No matching day found for the selected time. you should not be here"
        //     ];

        // }
    }

    public function TFIP_Booking_Check_Update_Booking($booking_data)
    {
        // $booking_data = [
        //     'id_new_timeslot' 
        //     'id_booking'    
        //     'date_booking'  
        //     'post_event_id' 
        //     'time_booking'  
        //     'identification'
        //     'phone'         
        //     'participants'  
        //     'extra_message' 
        //     'status'        
        // ];

        //update new date
        //update old date -->if different

        //else
        //update only date

        
        
        $new_active_day_id = $booking_data['date_booking'];
        $new_active_day = $this->TFIP_Booking_Get_Create_Active_Day($new_active_day_id);
        
        $timeslots = $this->TFIP_Booking_Get_Create_Format_Timeslots($new_active_day_id)['timeslots'];
        $original_booking = $this->_ipfDatabase->TFIP_Database_Get_Single_Booking($booking_data['id_booking']);
        $original_timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($original_booking->id_timeslot); 
        $new_booking_timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($booking_data['id_new_timeslot']);


        if(!$new_booking_timeslot)
        {
            return [
                'timeslot_id' => null,
                'resolution' => 0,
                'day_id' => $new_active_day_id,
                'message' => "Could not identify correct timeslot from time booking"
            ];
        }

        $booking_data['id_new_timeslot'] = $new_booking_timeslot->id;
        $booking_participants_updated = $booking_data['participants'];

        $temp_day_total_max  = 0;
        $day_local_active = 0;
        $same_slot = false;
        $status_changed = 0;

        $availables = 0;
        //$removed_participants + 

        


        foreach ($timeslots as $local_slot) {
            $temp_day_total_max += $local_slot->max_bookings;
            $day_local_active += $local_slot->active_bookings;     
        }

        if($new_booking_timeslot->id == $original_timeslot->id)
        {
            $same_slot = true;
        }

        // if($booking_data['status'] != (int)$original_timeslot->active)
        // {
        //     if($booking_data['status'] > (int)$original_timeslot->active) {$status_changed = 1;}
        //     else {$status_changed = -1;}
        // }


        $availables = $new_booking_timeslot->max_bookings - $booking_data['participants'] - $new_booking_timeslot->active_bookings;

        if($booking_data['status'] == (int)$original_booking->status)
        {
            if($same_slot)
            {
                if($booking_data['status'] > 0)
                {
                    $availables = $new_booking_timeslot->max_bookings - ($booking_data['participants'] 
                        + $original_booking->participants - $new_booking_timeslot->active_bookings);
                }
            }
        }else if($booking_data['status'] < (int)$original_booking->status)
        {
            if($same_slot)
            {
                $availables = $new_booking_timeslot->max_bookings - $new_booking_timeslot->active_bookings + $original_booking->participants;
            }else
            {
                $availables = $new_booking_timeslot->max_bookings - $new_booking_timeslot->active_bookings;
            }
        }
    

        if(!$new_active_day->active)
        {
            return [
                'timeslot_id' => $new_booking_timeslot->id,
                'resolution' => 0,
                'day_id' => $new_active_day_id,
                'message' => "Day is locked."
            ];
        }
            
            
        if(!$new_booking_timeslot->active)
        {
            return [
                'timeslot_id' => $new_booking_timeslot->id,
                'resolution' => 0,
                'day_id' => $new_active_day_id,
                'message' => "Timeslot is locked."
            ];
        }

            

        if ($temp_day_total_max != $new_active_day->day_max) {
            return [
                'timeslot_id' => $new_booking_timeslot->id,
                'resolution' => 4,
                'day_id' => $new_active_day_id,
                'message' => "Error: max capacity for the day does not match the sum of timeslot capacities. you really should not be here."
            ];
        }


        if ($availables >= 0) {

            return [
                'new_timeslot' => $new_booking_timeslot,
                'original_timeslot' => $original_timeslot,
                'original_booking' => $original_booking,
                'new_booking' => $booking_data,
                'resolution' => 1,
                'active_day' => $new_active_day,
                'message' => 'OK' 
            ];
        } else {

            return [
                'timeslot_id' => $new_booking_timeslot->id,
                'resolution' => 0,
                'day_id' => $new_active_day_id,
                'message' => "No space available for so many customers at the selected time slot."
            ];
        }
   
    }


    // TFIP ONE OF PROGRAM MAIN FUNCTION
    public function TFIP_Booking_Verify_Availability_Prepare_Booking($active_day_id, $number_participants, $timebooking, $status)
    {
        $active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($active_day_id);

        if ($active_day) {

            if($active_day->active)
            {
                $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($active_day_id);
                $temp_day_total_max = 0;
                $temp_total_active = 0;


                $booking_timeslot = null;


                foreach ($timeslots as $ts) {
                    $ts_start_date = DateTime::createFromFormat('H:i', $ts->timeslotstart);
                    
                    if ($ts_start_date == $timebooking) {
                        $booking_timeslot = $ts;
                    }
                    $temp_day_total_max += $ts->max_bookings;
                    $temp_total_active += $ts->active_bookings;
                }


                if(count($timeslots) == 1 && !$booking_timeslot)
                {
                    $booking_timeslot = $timeslots[0];
                }
                


                if (!$booking_timeslot) {
                    
                    return [
                        'timeslot_id' => null,
                        'resolution' => 0,
                        'day_id' => $active_day_id,
                        'message' => "No matching time slot found for the selected time."
                    ];
                }

                if(!$booking_timeslot->active)
                {
                    return [
                        'timeslot_id' => $booking_timeslot->id,
                        'resolution' => 5,
                        'day_id' => $active_day_id,
                        'message' => "Timeslot is locked."
                    ];
                }

                if ($temp_day_total_max != $active_day->day_max) {
                    return [
                        'timeslot_id' => $booking_timeslot->id,
                        'resolution' => 4,
                        'day_id' => $active_day_id,
                        'message' => "Error: max capacity for the day does not match the sum of timeslot capacities. you should not be here."
                    ];
                }


                $availables = $booking_timeslot->max_bookings - $booking_timeslot->active_bookings;

                if($status)
                {
                    $availables = $booking_timeslot->max_bookings - $booking_timeslot->active_bookings - $number_participants;
                }
                

                if ($availables >= 0) {

                    return [
                        'timeslot_id' => $booking_timeslot->id,
                        'resolution' => 1,
                        'day_id' => $active_day_id,
                        'message' => 'Can create booking in timeslot' 
                    ];
                    
                } else {

                    return [
                        'timeslot_id' => $booking_timeslot->id,
                        'resolution' => 0,
                        'day_id' => $active_day_id,
                        'message' => "No space available for so many customers at the selected time slot."
                    ];
                }
            }else
            {
                return [
                    'timeslot_id' => null,
                    'resolution' => 3,
                    'day_id' => $active_day_id,
                    'message' => "Day is not active."
                ];
            }
        } 
        else {
            
            return [
                'timeslot_id' => null,
                'resolution' => 2,
                'day_id' => null,
                'message' => "No matching day slot found for the selected time."
            ];

        }
    }

    public function TFIP_Booking_Get_Timeslot_For_Booking($timeslots, $booking_time)
    {
        $selected_slot = null;
        $res_booking = 0;

        if($timeslots)
        {
            //validate this next part with a flag on the if (if nothing is found in between the two times)
            if(count($timeslots) > 1)
            {
                foreach($timeslots as $ts_obj)
                {
                    $timeslotstart = DateTime::createFromFormat('H:i', $ts_obj['ts']['data_slot']['timeslotstart']);
                    $timeslotend = DateTime::createFromFormat('H:i', $ts_obj['ts']['data_slot']['timeslotend']);

                    if ($booking_time >= $timeslotstart && $booking_time <= $timeslotend) {
                        $selected_slot = $ts_obj;
                    }
                }
                
            }else
            {
                $selected_slot = $timeslots[0];
            
            }
        }

        if($selected_slot)
        {
            $res_booking = 1;
        }
        

        $res_booking = [
            'resolution' => $res_booking,
            'selected_timeslot_id' => $selected_slot['ts']['id_slot'],
            'timeslot_data' => $selected_slot['ts']['data_slot']
        ];

        return $res_booking;
        
    }
    
    


    public function TFIP_Booking_Create_And_Return($slot_id, $booking, $post_id = 0)
    {

        $res = $this->TFIP_Booking_Create_Client_Booking(
            $slot_id, $booking->identification, $booking->participants,
            $booking->phone, $booking->extra_message, $booking->code, $booking->status, $booking->time_booking, $post_id);
        
        // $res = [
        //     'id_booking' => $wpdb->insert_id,
        //     'resolution' => 1,
        //     'participants' => $participants,
        //     'id_timeslot' => $id_timeslot
        // ];
        
        //update booking for the day and for the timeslots
        if($res['resolution'] == 1)
        {   

            $ret = null;

            if($booking->status)
            {
                $ret = $this->_ipfDatabase->TFIP_Database_Update_Timeslots_Availability(
                    $res['id_timeslot'], $res['participants']);
            }else
            {
                $ret = $this->_ipfDatabase->TFIP_Database_Get_Single_Timeslot_Max_Bookings($res['id_timeslot']);
            }

            $obj = [
                'timeslot_id' => $slot_id,
                'resolution' => $ret['resolution'],
                'message' => $ret['message'],
                'day_id' => null,
                'updated_availability' => $ret['updated_availability']
            ];
            
            // $ret = [
            //     'resolution' => 1,
            //     'message' => 'Max limit reached cannot crete booking',
            //     'updted_availability' => $timeslot_instance['max_bookings'] - $active_bookings
            // ];

            return $obj;

        }else
        {
            $ret = [
                'resolution' => 0,
                'message' => 'Cannot create client booking',
                'updted_availability' => null
            ];
        }
    }

    public function TFIP_Booking_Process_Validation_Result($id_date, $booking, $validation)
    {
        switch ($validation['resolution']) {
            case 0:
                $this->send_json_response(0, 'Selected timeslot is already full or number of customers is over the slot limit.');
                break;
            case 1:
                $timeslot_id = $validation['timeslot_id'];

                if (!$timeslot_id) {
                    $this->handle_missing_timeslot_case($id_date, $booking);
                    
                } else {
                    $this->finalize_booking($timeslot_id, $booking);
                }
                break;

            case 2:

                $date_obj = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($id_date);
                $this->handle_missing_timeslot_case($date_obj['id_date'], $booking);

                break;
            case 3:
                //day is blocked
                $this->send_json_response(3, $validation['message'], null, $validation['day_id'],  null);
                break;
            case 4:
                //mismatch max day vs sum of all max timeslots
                $this->send_json_response(4, $validation['message'], null, $validation['day_id'], null);
                break;
            case 5:
                //timeslot is locked
                $this->send_json_response(5, $validation['message'], null, $validation['day_id'],  $validation['timeslot_id']);
            default:
                $this->send_json_response(0, 'Unexpected booking resolution.');
        }
    }

    /* 
        Function to create booking in the prenotazioni page

        TABLES : wp_tfip_bookings : wp_tfip_timeslot_instances : wp_tfip_active_days

        BOOKING: idbooking 	id_timeslot 	idpostevent 	identification 	participants 	phone 	extra_message 	code 	status 	booking_time 	
        TIMESLOT:  	id 	id_date 	timeslotstart 	timeslotend 	max_bookings   active_bookings	 active 	
        ACTIVE DAY:  	id_date 	day_max  active 	


    */

    public function tfip_admin_create_booking()
    {
        if (!isset($_POST['formdata'])) {
            $this->send_json_response(0, 'Missing booking data.');
        }

        $formData = json_decode(stripslashes($_POST['formdata']), true);

        // Prepare booking object
        $booking = new stdClass();
        $booking_date_raw = sanitize_text_field(esc_attr($formData['date_id']));
        $booking_date = new DateTime($booking_date_raw . ' 00:00:00');
        $id_date = $booking_date->getTimestamp();

        $booking->time_booking = DateTime::createFromFormat('H:i', sanitize_text_field($formData['time_booking']));
        $booking->participants = intval($formData['participants']);
        $booking->identification = sanitize_text_field(esc_attr($formData['identification']));
        $booking->phone = sanitize_text_field($formData['phone']);
        $booking->extra_message = sanitize_text_field(esc_attr($formData['extra_message']));
        $booking->status = sanitize_text_field($formData['status'] == 'confirmed') ? 1 : 0;
        $booking->code = $this->_ipfDatabase->TFIP_Database_generate_code($booking->identification);

        // Validate booking availability
        $validation = $this->TFIP_Booking_Verify_Availability_Prepare_Booking($id_date, $booking->participants, $booking->time_booking, $booking->status);

        $this-> TFIP_Booking_Process_Validation_Result($id_date, $booking, $validation);
        
    }

    private function handle_missing_timeslot_case($id_date, $booking)
    {
        $ret_timeslots = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($id_date);

        if ($ret_timeslots['resolution'] !== 1) {
            $this->send_json_response(0, 'Error in the daily timeslot creation.', null, $id_date,  null);
        }

        $validation = $this->TFIP_Booking_Verify_Availability_Prepare_Booking($id_date, $booking->participants, $booking->time_booking, $booking->status);
        
        switch ($validation['resolution']) {
            case 0:
                $this->send_json_response(0, 'Selected timeslot is already full or number of customers is over the slot limit.', null, $validation['day_id'],  $validation['timeslot_id']);
                break;
            case 1:

                $timeslots = (array) $ret_timeslots['timeslots'];
                $slot_response = $this->TFIP_Booking_Get_Timeslot_For_Booking($timeslots, $booking->time_booking);

                if ($slot_response['resolution'] !== 1) {
                    $this->send_json_response(0, 'Cannot find a valid timeslot for the selected time.', null, $validation['day_id'], null);
                }
                
                $this->finalize_booking($slot_response['selected_timeslot_id'], $booking);

            case 2:

                $date_obj = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($id_date);
                $this->handle_missing_timeslot_case($date_obj['id_date'], $booking);

                break;
            case 3:
                //day is blocked
                $this->send_json_response( $validation['resolution'], $validation['message'], null, $validation['day_id'],  null);
                break;
            case 4:
                //mismatch max day vs sum of all max timeslots
                $this->send_json_response( $validation['resolution'], $validation['message'], null, $validation['day_id'],  null);
                break;
            case 5:
                $this->send_json_response( $validation['resolution'], $validation['message'], null, $validation['day_id'],  $validation['timeslot_id']);

            default:
                $this->send_json_response( $validation['resolution'], $validation['message'], $validation['updated_availability'], $validation['day_id'],  $validation['timeslot_id']);
        }

    }


    private function finalize_booking($timeslot_id, $booking)
    {
        $res = $this->TFIP_Booking_Create_And_Return($timeslot_id, $booking);
        $this->send_json_response($res['resolution'], $res['message'], $res['updated_availability'], $res['day_id'], $res['timeslot_id']);
    }

    private function send_json_response($resolution, $message, $updated_ava = null, $day_id = null, $timeslot_id = null)
    {
        $response = [
            'timeslot_id' => $timeslot_id,
            'resolution' => $resolution,
            'message' => $message,
            'day_id' => $day_id,
            'updated_availability' => $updated_ava
        ];
        $this->send_json($response);
    }

    private function send_json($data)
    {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }


    public function read_booking($id) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'ipf_bookings';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        $result = $wpdb->get_row($query);

        return $result;
    }

    public function update_booking($id, $data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipf_bookings';

        $wpdb->update($table_name, $data, array('id' => $id));
    }

    public function delete_booking($id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipf_bookings';

        $wpdb->delete($table_name, array('id' => $id));
    }
    ///////





    public function tfip_getBookingData()
    {
        $html = "";

        if (isset($_POST['bookingdate']) && isset($_POST['bookingtime']) && !empty($_POST['bookingtime']) && !empty($_POST['bookingdate'])) {
            ob_start();
            include plugin_dir_path(__FILE__) . '../template/partial/booking_form.php';
            $html = ob_get_clean();

            $response = array(
                'succeded' => 1,
                'htmlToPrint' => $html
            );

            $encoded_answer = json_encode($response);
            header('Content-Type: application/json');
            echo $encoded_answer;
            exit();
        }

        $response = array(
            'succeded' => 0,
            'htmlToPrint' => $html
        );

        $encoded_answer = json_encode($response);
        header('Content-Type: application/json');

        echo $encoded_answer;
        exit();
    }

    public function tfip_confirmBooking() {

        // $datebooking = strtotime(date('Y-d-m'));
        // $timebooking = time();
        // $date_entity = new stdClass();
    
        // if (isset($_POST['data_form']['bookingdate'])) {
        //     $or_datebooking = sanitize_text_field(esc_attr($_POST['data_form']['bookingdate']));
        //     $datebooking = strtotime(date('Y-m-d', $or_datebooking));
        //     $timebooking = date('H:i', $or_datebooking);
        // }
    
        // $event_id = sanitize_key($_POST['data_form']['eventid']);
        // if (filter_var($event_id, FILTER_VALIDATE_INT) !== false) {
        //     $datebooking = date('Y-m-d', get_post_meta($event_id, '_tfIpf_event_date_time', true));
        //     $datebooking = strtotime($datebooking);
        //     $timebooking = date('H:i', get_post_meta($event_id, '_tfIpf_event_date_time', true));
        // } else {
        //     $event_id = 0;
        // }
    
        // $identification = sanitize_text_field($_POST['data_form']['uname']);
        // $code = $this->_ipfDatabase->TFIP_Database_generate_code($identification);

        // $participants = sanitize_text_field($_POST['data_form']['uguest']);


        // $phone = '+' . sanitize_text_field($_POST['data_form']['dialCode']) . sanitize_text_field($_POST['data_form']['uphone']);
        // $lang = $_POST['data_form']['countrycode'];


        
        // $extra_message = sanitize_text_field($_POST['data_form']['uspecial']);
        // $status = "forwarded";
        // $date_entity = $this->_ipfDatabase->tfIpf_get_date($datebooking);
        
        // if($date_entity == false)
        // {
        //     $response = array(
        //         'succeded' => 0,
        //         'htmlToPrint' => $this->_ipfDatabase->PrintErrorMessage("Date error")
        //     );
        //     $encoded_answer = json_encode($response);
        //     header('Content-Type: application/json');
        //     echo $encoded_answer;
        //     exit();
        // }


        // if ($date_entity->bookings + $participants > $date_entity->max_participants) {
        //     $response = array(
        //         'succeded' => 0,
        //         'htmlToPrint' => $this->_ipfDatabase->PrintErrorMessage("Event is complete")
        //     );
        //     $encoded_answer = json_encode($response);
        //     header('Content-Type: application/json');
        //     echo $encoded_answer;
        //     exit();
        // }
    
    
        // if ($participants > $date_entity->max_participants) {
        //     $response = array(
        //         'succeded' => 0,
        //         'htmlToPrint' => $this->_ipfDatabase->PrintErrorMessage("you enter a number too high of participants")
        //     );
        //     $encoded_answer = json_encode($response);
        //     header('Content-Type: application/json');
        //     echo $encoded_answer;
        //     exit();
        // }
    
        // $resulted_booking = $this->create_booking($date_entity->id, $code, $participants, $identification, $phone, $extra_message, $status, $timebooking, $event_id);
        // $newbookings = $date_entity->bookings + $participants;
        // $updated_bookings = $this->_ipfDatabase->tfIpf_update_days_date_bookings($date_entity->id, $newbookings);
        
        // if ($resulted_booking == false) {
        //     $response = array(
        //         'succeded' => 0,
        //         'htmlToPrint' => $this->_ipfDatabase->PrintErrorMessage("there is an error in creating booking."),
        //         'code' => $code
        //     );
        //     $encoded_answer = json_encode($response);
        //     header('Content-Type: application/json');
        //     echo $encoded_answer;
        //     exit();
        // }
        
        // ob_start();
        // include plugin_dir_path(__FILE__) . '../template/partial/confirm_booking.php';
        // $html = ob_get_clean();
        
        // //REACTIVATE
        // $send_code = $this->_manager->tf_ipf_send_code($phone, $identification, $code, $lang);
        // $resultflorence = $this->_manager->tf_ipf_communicate_booking($code, $identification, $datebooking, $timebooking, $participants, $extra_message, $phone);

        
        
        // $response = array(
        //     'succeded' => 1,
        //     'htmlToPrint' => $html
        // );
    
        // $encoded_answer = json_encode($response);
        // header('Content-Type: application/json');
        // echo $encoded_answer;
        // exit();
    }
    

    public function tfIpf_final_booking_confirm() {
        $html = '';
    
        if (isset($_POST['data_data']['idbooking']) && isset($_POST['data_data']['code'])) {
            $idbooking = sanitize_text_field($_POST['data_data']['idbooking']);
            $booking_code = mb_strtoupper(sanitize_text_field($_POST['data_data']['code']));
            
            $confirm = $this->_ipfDatabase->tfIpf_verify_code($idbooking, $booking_code);
    
            if ($confirm['error'] == 0) {
                
                $single_booking = $confirm['booking'];

                $phone_code = substr($single_booking->phone, 0, 3);
                $la_lang = 'en_GB';

                if($phone_code == '+39')
                {
                    $la_lang = 'it';
                }

                ob_start();
                include plugin_dir_path(__FILE__) . '../template/partial/booking_success.php';
                $html = ob_get_clean();
                
                //REACTIVATE
                $result = $this->_manager->tf_ipf_send_confirmation($single_booking->phone, $single_booking->identification, $booking_code, $la_lang);


            } else if ($confirm['error'] == 2) {
                ob_start();
                include plugin_dir_path(__FILE__) . '../template/partial/confirm_booking.php';
                $html = ob_get_clean();
            } else {
                ob_start();
                include plugin_dir_path(__FILE__) . '../template/booking_fail.php';
                $html = ob_get_clean();
            }
        }
    
        echo $html;
        exit();
    }


   

}
