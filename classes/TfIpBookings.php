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

        add_action('init', [$this,'TFIP_Booking_rewrite_rule']);
        add_filter('query_vars', [$this, 'TFIP_Booking_add_custom_query_var']);
        add_filter('template_include', [$this, 'TFIP_Booking_load_custom_plugin_template']);

        add_action( 'wp_ajax_tfip_confirmBooking', array( $this, 'tfip_confirmBooking'));
        add_action( 'wp_ajax_nopriv_tfip_confirmBooking', array( $this, 'tfip_confirmBooking'));
        add_action( 'wp_ajax_tfip_get_single_booking', array( $this, 'TFIP_Booking_Get_Single_Booking'));
        add_action( 'wp_ajax_tfip_admin_create_booking', array( $this, 'tfip_admin_create_booking'));
        add_action( 'wp_ajax_tfip_admin_update_booking', array( $this, 'TFIP_Booking_Update_Booking'));
        add_action( 'wp_ajax_tfip_admin_delete_booking', array( $this, 'TFIP_Booking_Delete_Booking'));
        add_action( 'wp_ajax_tfip_confirmBookingClient', array( $this, 'tfip_confirmBookingClient'));
        add_action( 'wp_ajax_nopriv_tfip_confirmBookingClient', array( $this, 'tfip_confirmBookingClient'));
        add_action( 'wp_ajax_tfip_update_timestamp', array( $this, 'TFIP_Booking_update_timestamp'));
        add_action( 'wp_ajax_nopriv_tfip_update_timestamp', array( $this, 'TFIP_Booking_update_timestamp'));

    }


    public function TFIP_Booking_update_timestamp()
    {
        $res_e = [
            'datestamp' => null
        ];

        if(isset($_POST['date']))  
        {
            $dateStr = sanitize_text_field( $_POST['date'] );
            
            $res_e['datestamp'] = strtotime($dateStr);

        } else
        {
            $res_e['datestamp'] = strtotime(date('today')); 
        }

        wp_send_json( $res_e );
    }


    function TFIP_Booking_rewrite_rule() {
        add_rewrite_rule(
            '^booking/([^/]*)/?',
            'index.php?booking_code=$matches[1]',
            'top'
        );
    }

    function TFIP_Booking_add_custom_query_var($vars) {
        $vars[] = 'booking_code';
        return $vars;
    }
    
    function TFIP_Booking_load_custom_plugin_template($template) {
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
                'dayStr' => null,
                'dayId' => null,
                'timeslotid'=>null,
                'resolution' => 0,
                'message' => 'You really should not be here. you are cheating with if($timeslot)the url parameters',
                'booking' => null,
                'timeslot' => null,
                'alltimeslots' => null,
                'postevent' => null
            ];


        }else
        {
            $single_booking = $this->_ipfDatabase->TFIP_Database_Get_Single_Booking($booking_id);

            if($single_booking)
            {
                $timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($single_booking->id_timeslot);
                $day_timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($timeslot->id_date);
                
                $targetTime = $single_booking->booking_time;

                $slots = [];

                if($timeslot)
                {
                    $slots = TFIP_Utils::TFIP_Utiles_Format_Existing_Timeslots($day_timeslots, $timeslot->id_date, $single_booking->id_timeslot, $targetTime);
                    
                   // $single_booking->exacttimes = TFIP_Utils::TFIP_Utils_Search_Return_Exact_Times($timeslot->timeslotstart, $timeslot->timeslotend, $single_booking->booking_time);

                    //fix here
                    $event_instance = null; 

                    if($single_booking->idpostevent)
                    {
                        $event_instance = get_post($single_booking->idpostevent);
                    }
                    ////fix here


                    $obj = [
                        'dayStr' => date("d-m-Y", $timeslot->id_date),
                        'dayId' => $timeslot->id_date,
                        'timeslotid'=>$timeslot->id,
                        'resolution' => 1,
                        'message' => 'OK',
                        'booking' => $single_booking,
                        'timeslot' => $timeslot,
                        'alltimeslots' => $slots,
                        'postevent' => $event_instance
                    ];

                
                }else
                {
                    $obj = [
                        'dayStr' => null,
                        'dayId' => null,
                        'timeslotid'=>null,
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

        $dt = new DateTime($timebooking);
        $timebooking = $dt->format('H:i');
        //$status_int = $status == "confirmed" ? 1 : 0;
        $posteventnullable = $post_event_id == 0 ? null : $post_event_id;
        
        if($posteventnullable != null)
        {
            $timebooking = get_post_meta( $posteventnullable, '_TFIP_exact_event_time', true);
        }


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
            'new_dateid'       => ( DateTime::createFromFormat('d-m-Y', sanitize_text_field($_POST['admin_date_id'])))->setTime(0, 0, 0)->getTimestamp(),
            'id_original_slot' => intval($_POST['original_timeslot']),
            'id_new_timeslot'  => isset($_POST['id_new_timeslot']) &&  $_POST['id_new_timeslot'] != "" ? intval($_POST['id_new_timeslot']) : null,
            'id_booking'       => intval($_POST['id_booking']),
            'post_event_id'    => intval($_POST['postevent_id']),
            'time_booking'     => (new DateTime(sanitize_text_field($_POST['exact_time_booking'])))->format('H:i'),
            'identification'   => sanitize_text_field($_POST['identification']),
            'phone'            => sanitize_text_field($_POST['phone']),
            'participants'     => intval($_POST['participants']),
            'extra_message'    => sanitize_textarea_field($_POST['extra_message']),
            'status'           => sanitize_text_field($_POST['status']) === 'confirmed' ? 1 : 0
        ];

        
        $validation = $this->TFIP_Booking_Check_Update_Booking($booking_data);
        $res = null;

        //c'e' un errore grave qui che prima non esisteva
        
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
                    $removed_participants = (int)$original_booking->participants; 
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
                        $res = $this->_ipfDatabase->TFIP_Database_Update_Day_Max_Availability($booking_data['new_dateid']);

                        if((bool)((int)$res['resolution']))
                        {
                            $res_e = [
                                'resolution'  => $res['resolution'],
                                'message' => "Booking Updated",
                                'date_booking' => $booking_data['new_dateid']
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
                        'date_booking' => $booking_data['new_dateid']
                    ];

                    $res = $res_e;
                    
                }

            }else
            {
                $res_e = [
                    'resolution'  => $res['resolution'],
                    'message' => $res['message'],
                    'date_booking' => $booking_data['new_dateid']
                ];

                $res = $res_e;
                
            }
            
            wp_send_json($res);

        }else
        {
            $res = [
                'resolution'  => $validation['resolution'],
                'message' => $validation['message'],
                'date_booking' => $booking_data['new_dateid']
            ];
        }

        wp_send_json($res);

    }


    
    

    //ok
    public function TFIP_Booking_Check_Update_Booking($booking_data)
    {
        $new_active_day_id = $booking_data['new_dateid'];

        $new_active_day = $this->_ipfDatabase->TFIP_Booking_Get_Create_Active_Day($new_active_day_id);
        
        // if($new_active_day == null)
        // {
        //     $new_active_day = $this->TFIP_Booking_Get_Create_Active_Day($new_active_day_id);
        // }
        
        $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($new_active_day_id);

        if(count($timeslots) == 0)
        {
            $timeslots = $this->_ipfDatabase->TFIP_Database_Get_Create_Format_Timeslots($new_active_day_id);
        }
        

        $original_booking = $this->_ipfDatabase->TFIP_Database_Get_Single_Booking($booking_data['id_booking']);
        $original_timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($original_booking->id_timeslot); 
        $new_booking_timeslot = null;

        if($booking_data['id_new_timeslot'] == null)
        {
            foreach ($timeslots as $ts) {
                
                if(($booking_data['time_booking'] >= $ts->timeslotstart) && ($booking_data['time_booking'] <= $ts->timeslotend))
                {
                    $new_booking_timeslot = $ts;
                }
            }
        }else
        {
            $new_booking_timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($booking_data['id_new_timeslot']);
        }

        if(!$new_booking_timeslot)
        {
            return [
                'timeslot_id' => null,
                'resolution' => 0,
                'day_id' => $new_active_day_id,
                'message' => "Could not identify correct timeslot from time booking"
            ];
        }else
        {
            $booking_data['id_new_timeslot'] = $new_booking_timeslot->id;
        }

        $temp_day_total_max  = 0;
        $day_local_active = 0;
        $availables = 0;

        foreach ($timeslots as $arrI) {
            
            $local_slot = $arrI;
            
            $temp_day_total_max += (int)$local_slot->max_bookings;
            $day_local_active += (int)$local_slot->active_bookings;     
        }

        if($new_booking_timeslot->id == $original_timeslot->id)
        {
            $same_slot = true;
        }

        $availables = 0;
        $availables = $new_booking_timeslot->max_bookings - $booking_data['participants'] - $new_booking_timeslot->active_bookings;

        if($same_slot)
        {
            if(($booking_data['status'] > 0) && ((int)$original_booking->status > 0) )
            {
                $availables = $new_booking_timeslot->max_bookings - $booking_data['participants'] 
                            + $original_booking->participants - $new_booking_timeslot->active_bookings;

            }else if(intval($booking_data['status']) < intval($original_booking->status))
            {
                $availables = $new_booking_timeslot->max_bookings - $new_booking_timeslot->active_bookings + $original_booking->participants;

            }
        }else
        {
            if(($booking_data['status'] < 1))
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


    

    public function TFIP_Booking_Get_Timeslot_For_Booking($timeslots, $booking_time)
    {
        $selected_slot = null;
        $res_booking = 0;

        if($timeslots)
        {
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
    
    

    /*
    
    */
    public function TFIP_Booking_Create_And_Return($slot_id, $booking, $post_id = 0)
    {

        $res = $this->TFIP_Booking_Create_Client_Booking(
            $slot_id, $booking->identification, $booking->participants,
            $booking->phone, $booking->extra_message, $booking->code, $booking->status, $booking->exact_booking_time, $post_id);
        
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

    private function return_json_response($resolution, $message,  $day_id = null, $timeslot_id = null, $updated_ava = null, $robject=null)
    {
        $response = [
            'timeslot_id' => $timeslot_id,
            'resolution' => $resolution,
            'message' => $message,
            'day_id' => $day_id,
            'updated_availability' => $updated_ava,
            'obj' => $robject
        ];

        return $response;
    }


    /*
        Verify availability in timeslot and return object with message
        obje =  [
            'timeslot_id' => null,
            'resolution' => 0,
            'day_id' => $active_day_id,
            'message' => "No matching time slot found for the selected time. You should not see this"
        ];
    */
    public function TFIP_Booking_Verify_Availability_Prepare_Booking($active_day_id, $number_participants, $timebooking, $status)
    {

        $return_obj =  [
            'timeslot_id' => null,
            'resolution' => 0,
            'day_id' => null,
            'message' => "empty"
        ];

        $active_day = $this->_ipfDatabase->TFIP_Booking_Get_Create_Active_Day($active_day_id);

    
        $timeslots_res = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($active_day_id);
        
        if($timeslots_res == null)
        {
            $timeslots_res = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($active_day_id);
            
            if(count($timeslots_res) == 0)
            {
                $return_obj['message'] = 'Error creating Timeslots';
                return $return_obj;
            }
        }
        

        if($active_day->active)
        {
            $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($active_day_id);
            $temp_day_total_max = 0;
            $temp_total_active = 0;


            $booking_timeslot = null;

            //select correct timeslot. selection is based on time.
            //count all the bookings already present
            
            if (count($timeslots) == 1) {
                
                $ts_item = $timeslots[0]; 
            
                $temp_day_total_max += $ts_item->max_bookings;
                $temp_total_active  += $ts_item->active_bookings;
            
                $booking_timeslot = $ts_item;
            
            } else {

                $booking_timeslot = TFIP_Utils::TFIP_Utils_Return_Timeslot_For_Selected_Time($timeslots, $active_day_id, $timebooking);
                
                foreach ($timeslots as $ts_item) {

                    $temp_day_total_max += $ts_item->max_bookings;
                    $temp_total_active  += $ts_item->active_bookings;
                }
            }
            
            if (!$booking_timeslot) {
                
                return [
                    'timeslot_id' => null,
                    'resolution' => 0,
                    'day_id' => $active_day_id,
                    'message' => "No matching time slot found for the selected time. You should not see this"
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
                    'resolution' => 0,
                    'day_id' => $active_day_id,
                    'message' => "Max capacity for the day does not match the sum of timeslot capacity. you should not be here."
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

    /*
        Create Booking    
    */
    public function tfip_admin_create_booking()
    {
        if (!isset($_POST['formdata'])) {
            $this->send_json($this->return_json_response(0, 'Dati prenotazione mancanti'));
        }

        $fin_res = null;

        $formData = json_decode(stripslashes($_POST['formdata']), true);

        // Prepare booking object
        $booking = new stdClass();
        $booking_date_raw = sanitize_text_field(esc_attr($formData['admin_date_id']));
        $booking_date = new DateTime($booking_date_raw . ' 00:00:00');

        $id_date = $booking_date->getTimestamp();
        $reference_timeslot_time =  sanitize_text_field($formData['time_booking']);

        $booking->reference_timeslot_time = TFIP_Utils::TFIP_Utils_Format_DateTime($id_date, $reference_timeslot_time);
        $booking->exact_booking_time = sanitize_text_field($formData['exact_time_booking']);

        $booking->participants = intval($formData['participants']);
        $booking->identification = sanitize_text_field(esc_attr($formData['identification']));
        $booking->phone = sanitize_text_field($formData['phone']);
        $booking->extra_message = sanitize_text_field(esc_attr($formData['extra_message']));
        $booking->status = sanitize_text_field($formData['status'] == 'confirmed') ? 1 : 0;
        $booking->code = $this->_ipfDatabase->TFIP_Database_generate_code($booking->identification);


        $validation = $this->TFIP_Booking_Verify_Availability_Prepare_Booking($id_date, $booking->participants, $booking->reference_timeslot_time, $booking->status);

        if(intval($validation['resolution']) == 1)
        {
            $res = $this->TFIP_Booking_Create_And_Return($validation['timeslot_id'], $booking);
            $fin_res = $this->return_json_response($res['resolution'], $res['message'], $res['day_id'], $res['timeslot_id'], $res['updated_availability']);
        }else
        {
            $fin_res = $this->return_json_response($validation['resolution'], $validation['message'], 
                $validation['day_id'], $validation['timeslot_id'], null);
        }

        $this->send_json($fin_res);
        
    }

    

    private function send_json($data)
    {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }


    public function TFIP_Booking_Delete_Booking() {

        $res = null;

        if (isset($_POST['bookingId'])) {
            global $wpdb;
            
            $id = intval($_POST['bookingId']);

            $table_name = $wpdb->prefix . 'tfip_bookings';

            $booking = $this->_ipfDatabase->TFIP_Database_Get_Single_Booking($id);
            $id_date = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($booking->id_timeslot)->id_date;
        
            $result = $wpdb->delete($table_name, array('idbooking' => $id));


            $res = $this->_ipfDatabase->TFIP_Database_Update_Timeslots_Availability($booking->id_timeslot, -$booking->participants);
            $res = $this->_ipfDatabase->TFIP_Database_Update_Day_Max_Availability($id_date);
            
            if($result !== false && $result > 0)
            {
                $res = $this->return_json_response(1, 'OK', $id_date, null, null);
            }else
            {
                $res = $this->return_json_response(0, 'Error in deleting from database');
            }
  
        }else
        {
            $res = $this->return_json_response(0, 'Missing booking data');
        }

        $this->send_json($res);        
    }

    public function tfip_confirmBookingClient()
    {
        // identification: identification,
        // participants: participants,
        // timeslotid: timeslotid,
        // dayid: dayid,
        // phone: phone,
        // extra: extra,
        // eventid: eventid
        // bcode :

        $formData = $_POST['data_form'];
        $booking = new stdClass();
        $day_id = sanitize_text_field(esc_attr($formData['dayid']));
        $event_id = intval($formData['eventid']);
        $timeBooking = sanitize_text_field($formData['timebook']);

        $timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot(intval($formData['timeslotid']));
        
        if(!$timeBooking)
        {
            $booking->time_booking =  $timeslot->timeslotstart;
        }else
        {
            $booking->time_booking = $timeBooking;
        }
        

        $dt = new DateTime();
        $dt->setTimestamp($day_id);
        list($hours, $minutes) = explode(":", $booking->time_booking);
        $dt->setTime($hours, $minutes, 0); 
        $booking->time_booking = $dt->format('Y-m-d H:i:s.u');

        $booking->exact_booking_time = $timeBooking;

        
        $booking->participants = intval($formData['participants']);
        $booking->identification = sanitize_text_field(esc_attr($formData['identification']));
        $booking->phone = sanitize_text_field($formData['phone']);
        $booking->extra_message = sanitize_text_field(esc_attr($formData['extra']));
        $booking->status = 1;
        $booking->code = sanitize_text_field(esc_attr($formData['bcode']));

        $validation = $this->TFIP_Booking_Verify_Availability_Prepare_Booking($day_id, $booking->participants, $booking->time_booking, $booking->status);

        if(intval($validation['resolution']) == 1)
        {
            $res = $this->TFIP_Booking_Create_And_Return($validation['timeslot_id'], $booking, $event_id);
            $fin_res = $this->return_json_response($res['resolution'], $res['message'], $res['day_id'], $res['timeslot_id'], $res['updated_availability']);
        }else
        {
            $fin_res = $this->return_json_response($validation['resolution'], $validation['message'], 
                $validation['day_id'], $validation['timeslot_id'], null);
        }

        $this->send_json($fin_res);
        
    }



    public function tfip_confirmBooking() {


        $eventId = null;
        $dayId = null;
        $timeslotTime = null;
        $timeslotId = null;


        $dataform = $_POST['data_form'];
        $timeslot = null;

        
        if(isset($dataform['eventid']) && sanitize_text_field($dataform['eventid']) != "")
        {
            $eventId = sanitize_key(intval($dataform['eventid']));
        }

        if(isset($dataform['timeslotid']) && sanitize_text_field($dataform['timeslotid']) != "")
        {
            $timeslotId = sanitize_key(intval($dataform['timeslotid']));
            $timeslot = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($timeslotId);

            if(isset($dataform['timeslottime']) && sanitize_text_field($dataform['timeslottime']) != "")
            {
                $timeslotTime = sanitize_text_field($dataform['timeslottime']);
            }else
            {
                $timeslotTime = $timeslot->timeslotstart;
            }

            // if($dateid == null || $dateid == '')
            // {
            //     $dateid = $this->_ipfDatabase->TFIP_Database_Get_Date_From_Timeslot($timeslot_id);
                
            // }
        }
       
        if(isset($dataform['dayid']) && sanitize_text_field($dataform['dayid']) != "" )
        {
            $dayId = sanitize_text_field(esc_attr($dataform['dayid']));
        }
       
        if(!$timeslotId || $timeslotId == "")
        {
            $timeslotTime = sanitize_text_field(esc_attr($dataform['timeslottime']));
        }


        $booking = new stdClass();

        $dt = new DateTime();
        $dt->setTimestamp($dayId);


        list($hours, $minutes) = explode(":", $timeslotTime);
        $dt->setTime($hours, $minutes, 0); 
        $booking->time_booking = $dt->format('Y-m-d H:i:s.u');
        

        $booking->participants =sanitize_text_field($dataform['guests']);
        $booking->identification = sanitize_text_field($dataform['identification']);
        $booking->phone = sanitize_text_field($dataform['idphone']);
        $booking->extra_message = sanitize_text_field($dataform['extramessage']);
        $booking->status = 1;
        $booking->code = $this->_ipfDatabase->TFIP_Database_generate_code($booking->identification);

        // Validate booking availability
        $validation = $this->TFIP_Booking_Verify_Availability_Prepare_Booking($dayId, $booking->participants, $booking->time_booking, $booking->status);
        $booking->status = 0;

        if(intval($validation['resolution']) == 1)
        {
            $bookingData = new stdClass();
            $bookingData->identification = $booking->identification; 
            $bookingData->participants   = $booking->participants; 
            $bookingData->timeslotid     = $timeslotId == null ? $validation['timeslot_id'] : $timeslotId;
            $bookingData->dayid          = $dayId;
            $bookingData->phone          = $booking->phone;
            $bookingData->extra          = $booking->extra_message;
            $bookingData->eventid        = $eventId;
            $bookingData->codeb          = $booking->code;
            $bookingData->timestart      = $timeslotTime;

            $res = $this->return_json_response($validation['resolution'], $validation['message'], null, null, null, $bookingData);
            $this->send_json($res);   
        }else
        {
            $res = $this->return_json_response($validation['resolution'], $validation['message'], null, null, null, null);
            $this->send_json($res);   
        }
        
    }
}
