<?php

include_once plugin_dir_path( __FILE__ ) . 'TFIP_Database.php';
// include_once plugin_dir_path( __FILE__ ) . 'TfIpfManager.php';



class TfIpCalendar {

    private TFIP_Database $_ipfDatabase;
    // private $startingDate;
    private $daysArr = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    private $months = [
        0 => 'December',
        1 => 'January',
        2 => 'February',
        3 => 'Mach',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
      ];

    function __construct(TFIP_Database $database, $start_date = null){

        add_action( 'wp_ajax_tfip_get_all_bookings_calendar', array($this, 'TFIP_Calendar_all_bookings_calendar'));
        
        add_action('wp_ajax_tfip_get_form_timeslots_booking_admin', array($this, 'TFIP_Calendar_Get_Timeslots_Date'));

        add_action( 'wp_ajax_tfip_unblock_day', array($this, 'TFIP_Calendar_Unblock_Day'));
        add_action( 'wp_ajax_tfip_unblock_timeslot', array($this, 'TFIP_Calendar_Unblock_Timeslot'));

        add_action( 'wp_ajax_tf_ipf_get_day_bookings', array($this, 'TFIP_Get_Single_Day_Data'));

        add_action( 'wp_ajax_tfip_get_timeslots', array($this, 'TFIP_Calendar_Return_TimeSlots_For_Editing'));

        add_action( 'wp_ajax_tfip_create_timeslot', array($this, 'TFIP_Calendar_Create_New_Timeslot'));

        add_action( 'wp_ajax_tfip_update_max_timeslot',  array($this, 'TFIP_Calendar_Update_Timeslot_Availability'));
    
        add_action( 'wp_ajax_tfip_pre_delete_timeslot',  array($this, "TFIP_Calendar_Pre_Delete_Timeslot"));

        add_action( 'wp_ajax_tfip_confirm_delete_timeslot',  array($this, "TFIP_Calendar_Confirm_Delete"));

        
        add_action( 'wp_ajax_tfip_disable_enable_timeslot',  array($this, "TFIP_Calendar_Enable_Disable_Timeslot"));
        add_action( 'wp_ajax_tfip_get_disable_day',  array($this, "TFIP_Calendar_Enable_Disable_Day"));


        add_action( 'wp_ajax_tfip_get_disable_day',  array($this, "TFIP_Calendar_Enable_Disable_Day"));

        

        

        
        // add_action( 'wp_ajax_ipf_editMaxCapacity', array( $this, 'ipf_editMaxCapacity'));
        // add_action( 'wp_ajax_tfipf_conf_book', array( $this, 'tfipf_conf_book'));
        // add_action( 'wp_ajax_tfipf_delete_booking', array( $this , 'tfipf_delete_booking'));

        // add_action( 'wp_ajax_get_calendar_html', array($this, 'get_calendar_html'));
        // add_action( 'wp_ajax_nopriv_get_calendar_html', array($this, 'get_calendar_html'));

        // add_action( 'wp_ajax_tf_ipf_get_admin_calendar', array($this, 'get_admin_calendar'));

        
        
        

        // add_action( 'wp_ajax_tfipf_return_edit_booking_form_ajax', array($this, 'tfipf_return_edit_booking_form_ajax'));
        // add_action( 'wp_ajax_ifpsave_edit_booking', array($this, 'ifpsave_edit_booking'));

        // if ($start_date === null) {
        //     $this->startingDate = date('d-m-Y');
        // } else {
        //     $this->startingDate = $start_date;
        // }

        $this->_ipfDatabase = $database;
    }

    function TFIP_Calendar_Enable_Disable_Day()
    {
        $resp_obj = null;

        if((isset($_POST['dateId']) && $_POST['dateId'] != '') && (isset($_POST['newStatus']) && $_POST['newStatus'] != ''))
        {
            $dateid = intval($_POST['dateId']);
            $status = intval($_POST['newStatus']);

            $res = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($dateid);

            if($res != null)
            {
                $updated = $this->_ipfDatabase->TFIP_Database_Block_Unblock_Day($dateid, $status);

                if($updated)
                {
                    $resp_obj = [
                        'resolution' => 1,
                        'message' => 'OK',
                        'dateid' => $dateid,
                    ];
                }else
                {
                    $resp_obj = [
                        'resolution' => 0,
                        'message' => 'error in disabling the day',
                        'dateid' => $dateid,
                    ];
                }
            }else
            {
                $new_day = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($dateid);
                $timeslots = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($dateid);


                $resp_obj = [
                    'resolution' => 1,
                    'message' => 'day still was created',
                    'dateid' => $dateid,
                ];
            }
        }else
        {
            $resp_obj = [
                'resolution' => 0,
                'message' => 'error in date or status',
                'dateid' => null,
            ];
            
        }

        wp_send_json($resp_obj);
    }

    function TFIP_Calendar_Confirm_Delete()
    {
        if (isset($_POST["slot_id"]))
        {
            $slot_id = absint($_POST['slot_id']); 
            $day_id = absint($_POST['dayid']);

            $success = $this->_ipfDatabase->TFIP_Database_Delete_Single_Timeslot($slot_id);

            $obj = null;

            if($success)
            {
                $_suc = $this->_ipfDatabase->TFIP_Database_Update_Day_Max_Availability($day_id);
                $obj = $_suc;

            }else
            {
                $obj = [
                    'resolution' => 0,
                    'message' => 'Cannot Delete Timeslot. Something went wrong with call to database',
                ];
            }

            wp_send_json($obj);

        }
    }

    function TFIP_Calendar_Pre_Delete_Timeslot()
    {
        if (isset($_POST["slot_id"]))
        {
            $slot_id = sanitize_text_field(esc_attr($_POST["slot_id"]));

            $obj_ret = null;
            //get timeslot info
            $timeslot_instance = $this->_ipfDatabase->TFIP_Database_Get_Specific_Timeslot($slot_id);
            $obj_ret['timeslot_id'] = $slot_id;
            $obj_ret['start'] = $timeslot_instance->timeslotstart;
            $obj_ret['end'] = $timeslot_instance->timeslotend;
            $obj_ret['max_bookings'] = $timeslot_instance->max_bookings;
            $obj_ret['active_bookings'] = $timeslot_instance->active_bookings;

            $bookings = $this->_ipfDatabase->TFIP_Database_Get_Timeslot_Confirmed_Bookings($slot_id);

            $obj_ret['confirmed_bookings'] = [];

            foreach ($bookings as $bk) {
                $obj_booking = null;
                $obj_booking['identification'] = $bk->identification;
                $obj_booking['participants'] = $bk->participants;
                $obj_booking['booking_time'] = $bk->booking_time;
                $obj_booking['code'] = $bk->code;

                $obj_ret['confirmed_bookings'][] = $obj_booking;
                
            }
            // $confirmed_bookings = null;
            wp_send_json($obj_ret);
            //get bookings for the day
            //
        }
    }
    
    function TFIP_Calendar_Update_Timeslot_Availability()
    {
        if (isset($_POST["max_bookings"]) && isset($_POST["slot_id"]))
        {
            $new_max = sanitize_text_field(esc_attr($_POST['max_bookings']));
            $slot_id = sanitize_text_field(esc_attr($_POST["slot_id"]));
            
            $res = $this->_ipfDatabase->TFIP_Database_Update_Max_Capacity($slot_id, $new_max);

            //$obj = null;

            wp_send_json($res);
        }
    }


    function TFIP_Calendar_Create_New_Timeslot() {
        
        // // Nonce check (optional but recommended)
        // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'your_nonce_action')) {
        //     wp_send_json_error(['message' => 'Invalid nonce']);
        // }

        $obj = [];

        // Access the form values
        $start = sanitize_text_field($_POST['start']);
        $end = sanitize_text_field($_POST['end']);
        $max_bookings = intval($_POST['max_bookings']);
        $id_day = intval($_POST['id_day']);

        $newtimeslots = [];

        if($start >= $end)
        {
            $obj ['resolution'] = 0;
            $obj ['message'] = "Start time must be earlier than end time.";

        
        }else
        {
            //check if day exist
            $active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($id_day);
            
            if(!$active_day)
            {
                $active_day = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($id_day);
            }

            $single_slot = [
                'id_date' => $id_day,
                'start' =>  $start,
                'end' => $end,
                'capacity' => $max_bookings,
                'active_bookings' => 0,
                'active' =>  1 //change here
            ];

            $newtimeslots[] = $single_slot;
            $validation = $this->_ipfDatabase->TFIP_Database_Create_TimeSlots($newtimeslots);

            $obj ['resolution'] = $validation ['resolution'];
            $obj ['message'] = $validation ['message'];

        }

        wp_send_json($obj);
    }

    public function TFIP_Calendar_Return_TimeSlots_For_Editing()
    {
        if (isset($_POST["dayid"]))
        {
            $date_id_in = sanitize_text_field(esc_attr($_POST["dayid"]));

            $obj = $this->_ipfDatabase->TFIP_Database_query_date($date_id_in);

            $fmt = new IntlDateFormatter(
                'it_IT',                 
                IntlDateFormatter::FULL, 
                IntlDateFormatter::NONE, 
                'Europe/Rome',           
                IntlDateFormatter::GREGORIAN
            );
            
            $formattedDate = $fmt->format($obj['id_day']);
    
            $obj['formatted_date'] =  $formattedDate;

            wp_send_json( $obj, 1);
        }
    }

    public function TFIP_Get_Single_Day_Data()
    {
        $startingDate = 0;
        setlocale(LC_TIME, 'it_IT.utf8');


        if(isset($_POST["timestampdate"]))
        {
            $startingDate = sanitize_key( esc_attr($_POST["timestampdate"]));

            $results = $this->_ipfDatabase->TFIP_Database_Query_Day_Data($startingDate);
            
            wp_send_json($results);

        }


    }

    //ACAB
    public function TFIP_Calendar_Unblock_Day()
    {
        if (isset($_POST["day_id"]))
        {
            $date_id_in = sanitize_text_field($_POST["day_id"]);
            $success = $this->_ipfDatabase->TFIP_Database_Block_Unblock_Day($date_id_in, 1);
            $string_date = DateTime::createFromTimestamp($date_id_in)->format('d-m-y');
            
            if($success)
            {
                $obj = [
                    'resolution' => 1,
                    'message' => 'Day '. $string_date .' is now active. You can proceed with booking creation',
                ];

            }else
            {
                $obj = [
                    'resolution' => 0,
                    'message' => 'Error while trying to unblock day '. $string_date .' .This should not happen',
                ];
            }

            wp_send_json( $obj);
        }
    }


    //ACAB
    public function TFIP_Calendar_Unblock_TimeSlot()
    {
        if (isset($_POST["timeslot_id"]))
        {
            $timeslot_id_in = sanitize_text_field($_POST["timeslot_id"]);
            $success = $this->_ipfDatabase->TFIP_Database_Block_Unblock_Timeslot($timeslot_id_in, 1);
            
            
            if($success)
            {
                $obj = [
                    'resolution' => 1,
                    'message' => 'Timeslot unblocked',
                ];

            }else
            {
                $obj = [
                    'resolution' => 0,
                    'message' => 'Error while trying to unblock timeslot '. $timeslot_id_in .' .This should not happen',
                ];
            }

            wp_send_json($obj);
        }
    }

    /*
        ///TFIP///
        Function get form for booking admin
    */
    public function TFIP_Calendar_Get_Timeslots_Date()
    {
        global $wpdb;
        setlocale(LC_TIME, 'it_IT.utf8');

        //$today = date('Y-m-01');
        $today = new DateTime('today');
        $timestamp = $today->getTimestamp(); 
        $slotid = 0;
        $slot_time = null;
        
        if (isset($_POST["date"]) && $_POST["date"] !== "") {

            $date_id_in = sanitize_text_field($_POST["date"]);
            $timestamp = DateTime::createFromFormat('d-m-Y', $date_id_in)->setTime(0,0,0)->getTimestamp();
        }

        if (isset($_POST["slotid"]) && $_POST["slotid"] !== "") {

            $slotid = intval($_POST["slotid"]);
            $timestamp = $this->_ipfDatabase->TFIP_Database_Get_Date_From_Timeslot($slotid);
        }

        if(isset($_POST["time_s"]) && $_POST["time_s"] !== "")
        {
            $slot_time = sanitize_text_field( $_POST["time_s"]);
        }
        
        
        $table_name = $wpdb->prefix . 'tfip_active_days';
        $active_day = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $timestamp)
        );

        $timeslots  = [];

        if($active_day)
        {
            $allts = $this->_ipfDatabase->TFIP_Database_Get_All_Peculiar_Timeslots_For_The_Day($timestamp); //$wpdb->get_results($wpdb->prepare("SELECT * FROM  $table_is WHERE id_date = %d", $timestamp));
            
            for($i = 0; $i < count($allts); $i++)
            {
                $ts_object = array(
                    'ids' => $allts[$i]->id,
                    'start' => $allts[$i]->timeslotstart,
                    'end' => $allts[$i]->timeslotend,
                    'sel' => 0
                );

                $timeslots[$i] = $ts_object;
            }

        }

        $slots = [];

        if(count($timeslots) == 0)
        {
            $timeslots = get_option('tfip_timeslots', []);
        }

        if(count($timeslots) == 1)
        {
            $start = new DateTime($timeslots[0]['start']);
            $end = new DateTime($timeslots[0]['end']);

            $interval = new DateInterval('PT15M'); 
            $period = new DatePeriod($start, $interval, $end); 
            
            foreach ($period as $time) {
                
                $slot_ = [
                    'ids' => $timeslots[0]['ids'],
                    'objt' => $time->format('H:i'),
                    'valt' => $time->format('H:i'),
                    'sel' => $slot_time != null && $slot_time == $time->format('H:i') ? 1 : 0
                ];
                
                $slots[] = $slot_; 
            }

            //$slots[] = ['objt' => $end->format('H:i'), 'valt' => $end->format('H:i')];
            
        }else
        {


            foreach($timeslots as $ts)
            {
                $start = new DateTime($ts['start']);
                $end = new DateTime($ts['end']);


                $slot_ = [
                    'ids' => $ts['ids'],
                    'objt' => $start->format('H:i') . " - " . $end->format('H:i'),
                    'valt' => $start->format('H:i'),
                    'sel' => $ts['ids'] == $slotid ? 1 : 0
                ];

                $slots[] = $slot_; 
            }
        }

        wp_send_json($slots);
    }

    public function TFIP_Calendar_Enable_Disable_Timeslot()
    {
        $obj_response = null;

        if(isset($_POST['slot_id']) && isset($_POST['dayid']) && isset($_POST['status']))
        {
            $timeslot_id = intval($_POST['slot_id']);
            $day_id = intval($_POST['dayid']);
            $status = intval($_POST['status']);

            if(!$timeslot_id || !$day_id)
            {
                $obj_response = [
                    'resolution' => 0,
                    'message' => "Data are not set, many thanks",
                    'day_id' => null,
                    'timeslot_id' => null,
                    'status' =>  null
                ];
                
                wp_send_json($obj_response);
                return;
            }

            $success = $this->_ipfDatabase->TFIP_Database_Block_Unblock_Timeslot($timeslot_id, $status);

            if($success)
            {
                $obj_response = [
                    'resolution' => 1,
                    'message' => "",
                    'day_id' => $day_id,
                    'timeslot_id' => $timeslot_id,
                    'status' =>  $status
                ];
                
                wp_send_json($obj_response);
                return;
            }

            $obj_response = [
                'resolution' => 0,
                'message' => "Database error updating status",
                'day_id' => null,
                'timeslot_id' => null,
                'status' =>  null
            ];
            
            wp_send_json($obj_response);
            return;


        }
        
    }

    /*
        Main function for getting "booking" page calendar
    */
    
    public function TFIP_Calendar_all_bookings_calendar() {

        setlocale(LC_TIME, 'it_IT.utf8');

        $startingDate = date('Y-m-01');

        if (isset($_POST["datestart"])) {
            $raw = sanitize_text_field(esc_attr($_POST["datestart"])); 
            $dateObj = DateTime::createFromFormat('d-m-Y', $raw);

            if ($dateObj !== false) {
                $startingDate = $dateObj->format('Y-m-01'); 
            }
        }
    
        if (isset($_POST["direction"])) {
            
            $direction = sanitize_text_field(esc_attr($_POST["direction"]));

            if($direction != -1)
            {
                $startingDate = date('Y-m-01', strtotime(($direction == 1 ? '+1 month' : '-1 month'), strtotime($startingDate)));
            }
            
        }
    

        $firstDayOfMonth = new DateTime($startingDate);
        $firstDayOfMonth->modify('first day of this month');

        $original_day_week = (int)$firstDayOfMonth->format('N');
        $dayOfWeek = $original_day_week; // 1 (Mon) to 7 (Sun)

        $monday = clone $firstDayOfMonth;

        
        if ($dayOfWeek > 1) {
            $monday->modify('-' . ($dayOfWeek - 1) . ' days'); //litterally a subtraction
        }


    
        for ($i = 1; $i <= 5; $i++) {
            $week = [];
            for ($j = 1; $j <= 7; $j++) {
                
                $dateToPrint = '';
                $dateToSearch = '';

            
                $dateToPrint = $monday->format('d-m-y');
                $monday->modify('+1 day');

                $date = DateTime::createFromFormat('d-m-y', $dateToPrint);
                $date->setTime(0, 0, 0);
                $dateToSearch = $date->getTimestamp();
                
                $arr_args = $this->_ipfDatabase->TFIP_Database_query_date($dateToSearch);
                

                $background_color_class = null;
            
                $arr_args['total_bookings'] > 0 ? $background_color_class = '#c6e8f8' : $background_color_class = '#FFFFFF';
                   
                $week[] = [
                    'weekday' => $date->format('l'),
                    'timestamp' => $dateToSearch,
                    'timeslots' => $arr_args['timeslots'],
                    'backgroundcolorclass' => $background_color_class,
                    'day' => $date->format('d'),
                    'bookings' => $arr_args['total_bookings'] == null ? 0 : $arr_args['total_bookings'],
                    'max_day' => $arr_args['max_day_availability'],
                    'availability' => $arr_args['day_availability'],
                    'customers_in' => $arr_args['customers_in']
                ];
            }
            $weeks[] = $week;
        }
        
        $formatter = new \IntlDateFormatter(
            'it_IT',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'Europe/Rome',
            \IntlDateFormatter::GREGORIAN,
            'LLLL yyyy' 
        );
        
        $newDateFormatted = strtoupper($formatter->format(strtotime($startingDate)));

        $response = [
            'newTimestamp' => strtotime($startingDate),
            'newDate' =>  $newDateFormatted,
            'weeks' => $weeks
        ];
    
        wp_send_json($response);
    }

    public function returnMonthsArray()
    {
        return $this->daysArr;
    }

    /*
        id [primarykey] |--| id_date (reference to table 1, datetimestampfield) |--| timeslotstart (format HH:MM) |--| 
        timeslots end (format HH:MM) (can be null) |--| max number of allowed bookings |--| active / no active (int 1/0)

        ...wp_tfip_bookings
    */

    // public function TFIP_Create_Timeslot($timestamp_date, $start, $end)
    // {
    //     global $wpdb;

    //     $table_name = $wpdb->prefix . 'wp_tfip_bookings';

    //     $data = array(
    //         'id_date' => $timestamp_date,
    //         'participants' => $participants,
    //         'identification' => $identification,
    //         'phone' => $phone,
    //         'extra_message' => $extra_message,
    //         'post_event_id' => $post_event_id,
    //         'status' => $status,
    //         'time_booking'=> $timebooking,
    //         'date_id' => $iddate
    //     );

    //     $success = $wpdb->insert($table_name, $data);

    //     if ($success !== false) {
    //         return $wpdb->insert_id;
    //     } else {
    //         return false;
    //     }
    // }

    public function tfipf_conf_book()
    {
        if(isset($_POST["confirm"]) && isset($_POST["date_d"]))
        {
            $bookingid = $_POST["confirm"];

            global $wpdb;

            $table_name = $wpdb->prefix . 'ipf_bookings';
            $qbooking = $wpdb->get_results($wpdb->prepare("SELECT * FROM  $table_name WHERE id = %d", $bookingid));
            $thebooking = $qbooking[0];

            $table_name = $wpdb->prefix . 'ipf_days_date';
            $qdate = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $thebooking->date_id));
            $ddate = $qdate[0];

            $newstatus = $thebooking->status;

            if($thebooking->status == "confirmed")
            {
                $participants = $thebooking->participants;
                $bookingsupdated = $ddate->bookings - $participants;

                $wpdb->update($table_name, array("bookings" => $bookingsupdated), array('id' => $ddate->id));

                $newstatus = 'forwarded';
            }else
            {
                $newstatus = 'confirmed';

                
            }


            $table_name = $wpdb->prefix . 'ipf_bookings';
            $wpdb->update($table_name, array("status" => $newstatus), array('id' => $thebooking->id));

            echo 'working';
            exit();
        }
    }

    public function tfipf_delete_booking()
    {
        if(isset($_POST["booking_id"]))
        {
            global $wpdb;

            $bookingid = $_POST["booking_id"];

            $table = $wpdb->prefix . 'ipf_bookings';

            $query = $wpdb->get_results($wpdb->prepare(
                "SELECT * from $table WHERE id = %d", $bookingid
            ));

            $booking = $query[0];

            $table = $wpdb->prefix . 'ipf_days_date';
            $query = $wpdb->get_results($wpdb->prepare(
                "SELECT * from $table WHERE id = %d", $booking->date_id
            ));

            $ddate = $query[0];

            $totbookings = $ddate->bookings;

            $updatedbookingsval = 0;

            if($booking->status == "confirmed")
            {
                // if(intval($totbookings + $booking->participants) > intval($ddate->max_participants))
                // {
                //     $updatedbookingsval = $totbookings - $ddate->max_participants;
                // }else
                // {
                //     $updatedbookingsval = intval($totbookings + $booking->participants);
                // }

                $updatedbookingsval = intval($totbookings - $booking->participants );

                $re = $wpdb->update(
                    $table,
                    array('bookings' => $updatedbookingsval),
                    array('id' => intval($ddate->id)),
                    array('%d'),
                    array('%d')
                );

            }


            $table = $wpdb->prefix . 'ipf_bookings';
            $result = $wpdb->delete(
                $table,
                array(
                    'id' => intval($booking->id)
                ),
                array(
                    '%d'
                )
            );

            wp_reset_query();
            echo $result;
            exit();


        }
    }

   

    
    public function ipf_editMaxCapacity()
    {
        if(isset($_POST['daydate']) && isset($_POST['capacity']))
        {
            $timestampday = sanitize_text_field( $_POST['daydate'] );
            $capacity = sanitize_text_field( $_POST['capacity'] );

            $result = $this->_ipfDatabase->EditMaxCapacity($timestampday, $capacity);

            $html = "";

            $result == true  ? $html .= '<div id="edit_'.$timestampday .'" >
            <span class="span_args_max"> Massima Capienza:'. $capacity .'</span>
            <i class="fa-solid fa-pencil"  data-id="'. $timestampday .
            '" data-max="'. $capacity .'"  onclick="EditMaxCapacity(this)"  ></i></div>'
            : $html .= '<span><i class="fa fa-times text-warning"></i>  massima capienza non puo essere minore del numero di prenotazioni </span>';

            echo $html;
            exit();
        }
    }


    /*
    * Registered action to output full calendar
    */
    public function get_calendar_html() {

        if(isset($_POST['maxnum']))
        {
            $maxnum = sanitize_text_field($_POST['maxnum']);
            $calendar_events = $this->_ipfDatabase->tfIpf_event_query_list(intval($maxnum));
            $html = $this->tfIpf_render_events_list($calendar_events);
            echo $html;
            exit();
        }

    }



    function tfIpf_render_events_list($arra_event)
    {
        $html = '';

        foreach($arra_event as $ev)
        {
            $ev_date = date('Y-m-d', strtotime($ev->date_event));
            $ev_link = $permalink = get_permalink($ev->id);

            $featured_image_url = get_the_post_thumbnail_url($ev->id, 'thumbnail');


            switch ($ev->event_type) {
                case 'sport':
                    {
                        $path_check = plugin_dir_path( __DIR__) . 'squadre/';
                        $path = plugin_dir_url( __DIR__) . 'squadre/';

                        $team_one_img = $path_check . $ev->teamone . '.png';
                        $team_two_img = $path_check . $ev->teamtwo . '.png';

                        if (!file_exists($team_one_img)) 
                        {
                            $team_one_img = $path . 'Neutral.png';
                        }else
                        {
                            $team_one_img = $path . $ev->teamone . '.png';
                        }

                        if(!file_exists($team_two_img))
                        {
                            $team_two_img = $path . 'Neutral.png';
                        }else
                        {
                            $team_two_img = $path . $ev->teamtwo . '.png';
                        }



                        $html .= '<div class="eventi-home-riga evento-sport">
                                            <img src="' . $team_one_img . '" alt="flag squadra1">
                                            <img src="' . $team_two_img . '" alt="flag squadra2">
                                            <a href="'. $ev_link .'">
                                                <span class="squadra1">'. $ev->teamone .'</span>
                                                - <span class="squadra2">'. $ev->teamtwo .'</span>
                                            </a>';
                    }
                    break;
                case 'food':
                    {
                        $html .= '<div class="eventi-home-riga evento-degustazione">
                                    <img src="'.$featured_image_url.'" alt="Titolo Evento">
                                    <a href="'. $ev_link .'">'. $ev->title .'</a>';
                    }
                    break;
                case 'music':
                    {
                        $html .= '<div class="eventi-home-riga evento-music">
                                        <img src="'. $featured_image_url.'" alt="Titolo Evento">
                                        <a href="'. $ev_link .'">'. $ev->title .'</a>';
                    }
                    break;
                default:

                    break;
            }

            $html .= '<p>
                                <span class="data-evento">
                                <b>'. date('d M Y', strtotime($ev_date)) .'</b>
                                </span>, <span class="orario-evento">'. $ev->time_event .' </span>

                                <button type="button" onclick="location.href=\'' . $ev_link . '\'" class="btn btn-outline prenota">Prenota</button>
                            </p>
                    </div>';

        }

        return $html;
    }




    /*
    * Get previous month on array
    */
    function get_previous_month($ddate) {

        $current_month_number = (int) date('m', strtotime($ddate));
        return $this->months[$current_month_number - 1];
    }

    public function tfipf_return_edit_booking_form_ajax()
    {
        $html = '';

        if(isset($_POST["bookingid"]))
        {
            $bookingid = sanitize_text_field( $_POST['bookingid'] );

            $booking = $this->tfIpf_return_booking_by_id(intval($bookingid));
            $booking_time = date("H:i", strtotime($booking->time_booking));
            
            $html = '
                    <form id="editBookingForm">
                        <div class="row  mb-3">
                            <input type="hidden" value="'. $booking->id. '" id="bookingid" name="bookingid" />
                            <div class="col-12 col-md-6 admin-modifica">
                                <label for="time_booking">Orario:</label>
                                <select id="time_booking" name="time_booking" class="form-control form-select">
                                    <option value="0">Seleziona orario</option>
                                    <option value="17:00"  '. ($booking_time == "17:00" ? "selected" : "") . ' >17:00</option>
                                    <option value="17:15"  '. ($booking_time == "17:15" ? "selected" : "") . ' >17:15</option>
                                    <option value="17:30"  '. ($booking_time == "17:30" ? "selected" : "") . ' >17:30</option>
                                    <option value="17:45"  '. ($booking_time == "17:45" ? "selected" : "") . ' >17:45</option>
                                    <option value="18:00"  '. ($booking_time == "18:00" ? "selected" : "") . ' >18:00</option>
                                    <option value="18:15"  '. ($booking_time == "18:15" ? "selected" : "") . ' >18:15</option>
                                    <option value="18:30"  '. ($booking_time == "18:30" ? "selected" : "") . ' >18:30</option>
                                    <option value="18:45"  '. ($booking_time == "18:45" ? "selected" : "") . ' >18:45</option>
                                    <option value="19:00"  '. ($booking_time == "19:00" ? "selected" : "") . ' >19:00</option>
                                    <option value="19:15"  '. ($booking_time == "19:15" ? "selected" : "") . ' >19:15</option>
                                    <option value="19:30"  '. ($booking_time == "19:30" ? "selected" : "") . ' >19:30</option>
                                    <option value="19:45"  '. ($booking_time == "19:45" ? "selected" : "") . ' >19:45</option>
                                    <option value="20:00"  '. ($booking_time == "20:00" ? "selected" : "") . ' >20:00</option>
                                    <option value="20:15"  '. ($booking_time == "20:15" ? "selected" : "") . ' >20:15</option>
                                    <option value="20:30"  '. ($booking_time == "20:30" ? "selected" : "") . ' >20:30</option>
                                    <option value="20:45"  '. ($booking_time == "20:45" ? "selected" : "") . ' >20:45</option>
                                    <option value="21:00"  '. ($booking_time == "21:00" ? "selected" : "") . ' >21:00</option>
                                    <option value="21:15"  '. ($booking_time == "21:15" ? "selected" : "") . ' >21:15</option>
                                    <option value="21:30"  '. ($booking_time == "21:30" ? "selected" : "") . ' >21:30</option>
                                    <option value="21:45"  '. ($booking_time == "21:45" ? "selected" : "") . ' >21:45</option>
                                    <option value="22:00"  '. ($booking_time == "22:00" ? "selected" : "") . ' >22:00</option>
                                    <option value="22:15"  '. ($booking_time == "22:15" ? "selected" : "") . ' >22:15</option>
                                    <option value="22:30"  '. ($booking_time == "22:30" ? "selected" : "") . ' >22:30</option>
                                    <option value="22:45"  '. ($booking_time == "22:45" ? "selected" : "") . ' >22:45</option>
                                    <option value="23:00"  '. ($booking_time == "23:00" ? "selected" : "") . ' >23:00</option>
                                    <option value="23:15"  '. ($booking_time == "23:15" ? "selected" : "") . ' >23:15</option>
                                    <option value="23:30"  '. ($booking_time == "23:30" ? "selected" : "") . ' >23:30</option>
                                </select>
                            </div>                          
                            <div class="col-12 col-md-6 admin-modifica mb-3">
                                <label for="date_id">Data prenotazione:</label>
                                <input type="text" class="form-control"  value="'. date('Y-m-d', $booking->date_id) . '" id="date_id" name="date_id">
                            </div>
                        </div>
                        <div class="row  mb-3">
                            <div class="col-12 col-md-6 admin-modifica">
                                <label for="admin_identification">Nome:</label>
                                <input type="text" class="form-control" value="'. $booking->identification . '" id="identification"  name="identification">
                            </div>
                            <div class="col-12 col-md-6 admin-modifica">
                                <label for="admin_participants">Numero partecipanti:</label>
                                <input type="number" class="form-control" value="'. $booking->participants . '" id="participants" name="participants">
                            </div>
                        </div>
                        <div class="row  mb-3">
                            <div class="col-12 col-md-4 admin-modifica">
                                <label for="phone">Numero di telefono:</label>
                                <input type="text" class="form-control" value="'.  $booking->phone . '" id="phone"  name="phone">
                            </div>
                            <div class="col-12 col-md-8 admin-modifica">
                                <label for="extra_message">Richieste particolari:</label>
                                <textarea class="form-control" id="extra_message" name="extra_message">'. $booking->extra_message . '</textarea>
                            </div>
                        </div>
                        <div class="row  mb-3">
                            <div class="col-12 col-md-6 admin-modifica">
                                <label for="tatus">Stato:</label>
                                <select class="form-control"  id="status" name="status">
                                    <option value="confirmed" '. ($booking->status == "confirmed" ? "selected" : "") . '> Confermata </option>
                                    <option value="forwarded" '. ($booking->status == "forwarded" ? "selected" : "") . '> Inoltrata </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 admin-modifica">
                                <label for="code">Code:</label>
                                <input class="form-control" value="'. $booking->code . '" id="code" name="code" disabled>
                            </div>
                        </div>
                        <div class="row  mb-3">
                            <div class="col-12">
                                <button type="button" onclick="save_edit_form_data()" class="refresh-booking">Aggiorna prenotazione</button>
                            </div>
                        </div>
                    </form>
                </div>
            <script>
                jQuery(\'#date_id\').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    startDate: new Date('. date('Y-m-d', $booking->date_id) . '),
                    format: "dd/mm/yy",
                }).datepicker(\'update\', new Date())


                var input = document.querySelector("#phone");

                var iti = window.intlTelInput(input, {

                    allowDropdown: true,
                    initialCountry: "it",
                    autoPlaceholder: "polite",
                    separateDialCode: true,
                    utilsScript: "https://raw.githack.com/jackocnr/intl-tel-input/master/build/js/utils.js"

                });
            </script>

            ';


            $response = array(
                'succeded' => 1,
                'htmlToPrint' => $html,
                'date_id' => date('Y-m-d', $booking->date_id),
            );

            $encoded_answer = json_encode($response);
            header('Content-Type: application/json');

            echo $encoded_answer;
            exit();
        }



    }


    public function tfIpf_return_booking_by_id($bookingid)
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}ipf_bookings
            WHERE id = %d
        ", $bookingid);

        $booking = $wpdb->get_row($query);

        if ($booking) {

            return $booking;

        } else {

            return false;
        }
    }

    public function ifpsave_edit_booking()
    {
        if (isset($_POST['formData'])) {

            global $wpdb;

            $formData = json_decode(stripslashes($_POST['formData']), true);

            $booking = new stdClass();
            $booking->id = intval($formData['bookingid']);
            $booking->post_event_id = intval($formData['post_event_id']);
            $booking->identification = $formData['identification'];
            $booking->participants = intval($formData['participants']);
            $booking->phone = $formData['phone'];
            $booking->extra_message= $formData['extra_message'];
            $booking->code = $formData['code'];
            $booking->status  = $formData['status'];
            $booking->time_booking = date("H:i", strtotime($formData['time_booking']));
            $sandate = sanitize_text_field( esc_attr($formData['date_id']));
            $sandate = DateTime::createFromFormat('d/m/y', $sandate);
            $booking->date_id = strtotime($sandate->format('Y-m-d'));


            $table_name = $wpdb->prefix . 'ipf_bookings';

            $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $booking->id);
            $results = $wpdb->get_results($sql);

            $participants_before = intval($results[0]->participants);


            $table_name = $wpdb->prefix . 'ipf_days_date';
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($booking->date_id)));

            if ($row) {

                $bookings_now = $row->bookings;

                $updated_participants = ($bookings_now - $participants_before) + $booking->participants;

                $wpdb->update(
                    $table_name,
                    array('bookings' => $updated_participants),
                    array('id' => $booking->date_id),
                    array('%d'),
                    array('%d')
                );
            }


            $table_name = $wpdb->prefix . 'ipf_bookings';

            $result = $wpdb->update(
                $table_name,
                (array) $booking,
                array('id' => $booking->id),
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d'
                ),
            );


            if ($result === false) {

                $response = array(
                    'success' => true,
                    'htmlToPrint' => '<div class="alert alert-success" role="alert">Prenotazione aggiornata!</div>',

                );
                exit();

            } else {

                $response = array(
                    'success' => true,
                    'htmlToPrint' => '<div class="alert alert-success" role="alert">Prenotazione aggiornata!</div>',
                    'newdate' => $booking->date_id
                );

                wp_send_json($response);
                exit();
            }





        }

        exit();
    }



}
