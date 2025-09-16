<?php

include_once (plugin_dir_path( __FILE__ ) . 'TFIP_Database.php');

// include_once plugin_dir_path( __FILE__ ) . 'TfIpfManager.php';



class TfIpCalendar {

    private TFIP_Database $_ipfDatabase;
  
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

    function __construct(TFIP_Database $database){


        $this->_ipfDatabase = $database;

        add_action( 'wp_ajax_tfip_get_all_bookings_calendar', array($this, 'TFIP_Calendar_all_bookings_calendar'));
        
        add_action('wp_ajax_TFIP_Calendar_Get_Timeslots', array($this, 'TFIP_Calendar_Get_Timeslots'));
        add_action('wp_ajax_nopriv_TFIP_Calendar_Get_Timeslots', array($this, 'TFIP_Calendar_Get_Timeslots'));

        add_action('wp_ajax_TFIP_Calendar_Exact_Booking_Time', array($this, 'TFIP_Calendar_Exact_Booking_Time'));
        add_action('wp_ajax_nopriv_TFIP_Calendar_Exact_Booking_Time', array($this, 'TFIP_Calendar_Exact_Booking_Time'));

        add_action('wp_ajax_nopriv_tfip_Check_If_Closed_or_Full', array($this, 'TFIP_Calendar_Check_If_Closed_or_Full'));
        add_action('wp_ajax_tfip_Check_If_Closed_or_Full', array($this, 'TFIP_Calendar_Check_If_Closed_or_Full'));


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

        

        add_action( 'wp_ajax_get_calendar_html', array($this, 'get_calendar_html'));
        add_action( 'wp_ajax_nopriv_get_calendar_html', array($this, 'get_calendar_html'));


    }

    function TFIP_Calendar_Check_If_Closed_or_Full()
    {
        $res = [
            'resolution'=> 1,
            'message' => "OK"
        ];

        $day_id = null;
        $timeslotTimeRange = null;
        $bookingTime = null;
        
        if(isset($_POST['dayId']) && intval($_POST['dayId']) != 0 && sanitize_text_field( $_POST['dayId']) != '')
        {
            $day_id = intval($_POST['dayId']);   
        }

        if(isset($_POST['tstime']) && sanitize_text_field( $_POST['tstime']) != '')
        {
            $timeslotTime = sanitize_text_field($_POST['tstime']);   
        }

        if(isset($_POST['btime']) && sanitize_text_field( $_POST['btime']) != '')
        {
            $bookingTime = sanitize_text_field($_POST['btime']);   
        }



        $active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($day_id);

        if($active_day)
        {
            $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($active_day->id_date);

            if(count($timeslots) == 0)
            {
                $timeslots = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($active_day->id_date);
                wp_send_json($res);

            }else
            {
                $timeslot = null;
                $timeslot = TFIP_Utils::TFIP_Utils_Return_Timeslot_For_Selected_Time($timeslots, $active_day->id_date, $bookingTime);
                
                if(($timeslot->active_bookings >= $timeslot->max_bookings) || !$timeslot->active)
                {
                    $res = [
                        'resolution'=> 0,
                        'message' => "Booking for the specific date and time are not available."
                    ];

                    wp_send_json($res);

                }else
                {
                    wp_send_json($res);
                }

            }

        }else
        {
            wp_send_json($res);
        }
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
                $_ = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($dateid);
                $_ = $this->_ipfDatabase->TFIP_Booking_Create_And_Return_Default_Timeslots($dateid);


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

            wp_send_json($obj_ret);

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
        
        $obj = [];

        $start = sanitize_text_field($_POST['start']);
        $end = sanitize_text_field($_POST['end']);
        $max_bookings = intval($_POST['max_bookings']);
        $id_day = intval($_POST['id_day']);

        $newtimeslots = [];

        if($start >= $end)
        {
            $obj ['resolution'] = 0;
            $obj ['message'] = "Start time must be earlier than end time.";
            $obj ['iddate'] = null;
        
        }else
        {

            $active_day = $this->_ipfDatabase->TFIP_Database_Get_Active_Day($id_day);
            
            if(!$active_day)
            {
                $active_day = $this->_ipfDatabase->TFIP_Database_Create_Active_Day($id_day);
            }

            $existing_timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($active_day->id_date);

            $single_slot = [
                'id_date' => $id_day,
                'start' =>  $start,
                'end' => $end,
                'capacity' => $max_bookings,
                'active_bookings' => 0,
                'active' =>  1 
            ];

            $newtimeslots[] = $single_slot;

            if(count($existing_timeslots) == 0)
            { 
                $validation = $this->_ipfDatabase->TFIP_Database_Create_TimeSlots($newtimeslots);
    
                $obj ['resolution'] = $validation ['resolution'];
                $obj ['message'] = $validation ['message'];
                $obj ['iddate'] = $id_day;

            }else
            {
                $valid = true;
                
                for ($i=0; $i < count($existing_timeslots); $i++) { 
                    
                    $ts = $existing_timeslots[$i];

                    $valid = TFIP_Utils::TFIP_Utils_Check_Time_Range($single_slot['start'], $single_slot['end'], 
                        $ts->timeslotstart, $ts->timeslotend);
                    
                    if(!$valid)
                    {
                        $obj ['resolution'] = 0;
                        $obj ['message'] = 'Timeslot are overlapping, cannot create';
                        $obj ['iddate'] = $id_day;
                        break;
                    }

                }

                if($valid)
                {
                    $validation = $this->_ipfDatabase->TFIP_Database_Create_TimeSlots($newtimeslots);
    
                    $obj ['resolution'] = $validation ['resolution'];
                    $obj ['message'] = $validation ['message'];
                    $obj ['iddate'] = $id_day;
                }
            }

            

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


    public function TFIP_Calendar_Exact_Booking_Time()
    {
        $timestart = null;
        $timeend = null;

        if (isset($_POST["timestart"]) && $_POST["timestart"] !== "" && isset($_POST["timeend"]) && $_POST["timeend"] !== "" ) {

            $timestart = sanitize_text_field($_POST["timestart"]);
            $timeend =sanitize_text_field($_POST["timeend"]);
        }

        if($timestart != null && $timeend != null)
        {
            $exactTimes = TFIP_Utils::TFIP_Utils_Search_Return_Exact_Times($timestart, $timeend);
        
            wp_send_json($exactTimes);
        }
    }

    /*
        ///TFIP///
        Function get form for booking admin
    */
    public function TFIP_Calendar_Get_Timeslots()
    {
        
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

        $timeslots = $this->_ipfDatabase->TFIP_Database_Get_All_Timeslots_For_Active_Day($timestamp); 

        if(count($timeslots) == 0)
        {
            $timeslots = TFIP_Utils::TFIP_Utils_Format_Default_Timeslots($timestamp);
        }else
        {
            $timeslots = TFIP_Utils::TFIP_Utiles_Format_Existing_Timeslots($timeslots, $timestamp, $slotid, $slot_time);
        }


        wp_send_json($timeslots);
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

            $raw = intval($_POST["datestart"]);
            $dateObj = (new DateTime())->setTimestamp($raw);
            $dateObj->modify('first day of this month');

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
            
                $arr_args['total_bookings'] > 0 ? $background_color_class = '#fce9d6' : $background_color_class = '#FFFFFF';
                   
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


    /*
    * Registered action to output full calendar
    */
    public function get_calendar_html() {

        if(isset($_POST['maxnum']))
        {
            $maxnum = sanitize_text_field($_POST['maxnum']);
            $calendar_events = $this->_ipfDatabase->TFIP_Database_Event_Query_List(intval($maxnum));
            $html = $this->TFIP_Calendar_Render_Event_List($calendar_events);
            echo $html;
            exit();
        }

    }

    function TFIP_Calendar_Render_Event_List($arra_event)
    {
        $html = '<div class="TFIP-style">';

        foreach($arra_event as $ev)
        {
            $ev_date = $ev->date_event;
            $ev_link = get_permalink($ev->id);
            $extended_date = DateTime::createFromFormat("d-m-Y", $ev_date)->format("d M Y");
            $featured_image_url = get_the_post_thumbnail_url($ev->id, 'thumbnail');


            switch ($ev->event_type) {
                case 'sport':
                    {
                        
                        $path_url = plugin_dir_url(__DIR__) . 'assets/squadre/';
                        $path_file = plugin_dir_path(__DIR__) . 'assets/squadre/';

                        $team_one_filename = TFIP_Utils::TFIP_Utils_Normalize_Team_Name($ev->teamone);
                        $team_two_filename = TFIP_Utils::TFIP_Utils_Normalize_Team_Name($ev->teamtwo);

                        $team_one_img = $path_url . $team_one_filename;
                        $team_two_img = $path_url . $team_two_filename;

                        $team_one_img_file = $path_file . $team_one_filename;
                        $team_two_img_file = $path_file . $team_two_filename;

 
                        if (!file_exists($team_one_img_file)) {
                            $team_one_img = $path_url . 'Neutral.png';
                        }

                        if (!file_exists($team_two_img_file)) {
                            $team_two_img = $path_url . 'Neutral.png';
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
                                <b>'. $extended_date .'</b>
                                </span>, <span class="orario-evento">'. $ev->time_event .' </span>

                                <button type="button" onclick="location.href=\'' . $ev_link . '\'" class="btn btn-outline prenota">Prenota</button>
                            </p>
                    </div>';

        }

        $html .= '</div>';

        return $html;
    }




    /*
    * Get previous month on array
    */
    function get_previous_month($ddate) {

        $current_month_number = (int) date('m', strtotime($ddate));
        return $this->months[$current_month_number - 1];
    }

    

    // public function tfIpf_return_booking_by_id($bookingid)
    // {
    //     global $wpdb;

    //     $query = $wpdb->prepare("
    //         SELECT *
    //         FROM {$wpdb->prefix}ipf_bookings
    //         WHERE id = %d
    //     ", $bookingid);

    //     $booking = $wpdb->get_row($query);

    //     if ($booking) {

    //         return $booking;

    //     } else {

    //         return false;
    //     }
    // }

    // public function ifpsave_edit_booking()
    // {
    //     if (isset($_POST['formData'])) {

    //         global $wpdb;

    //         $formData = json_decode(stripslashes($_POST['formData']), true);

    //         $booking = new stdClass();
    //         $booking->id = intval($formData['bookingid']);
    //         $booking->post_event_id = intval($formData['post_event_id']);
    //         $booking->identification = $formData['identification'];
    //         $booking->participants = intval($formData['participants']);
    //         $booking->phone = $formData['phone'];
    //         $booking->extra_message= $formData['extra_message'];
    //         $booking->code = $formData['code'];
    //         $booking->status  = $formData['status'];
    //         $booking->time_booking = date("H:i", strtotime($formData['time_booking']));
    //         $sandate = sanitize_text_field( esc_attr($formData['date_id']));
    //         $sandate = DateTime::createFromFormat('d/m/y', $sandate);
    //         $booking->date_id = strtotime($sandate->format('Y-m-d'));


    //         $table_name = $wpdb->prefix . 'ipf_bookings';

    //         $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $booking->id);
    //         $results = $wpdb->get_results($sql);

    //         $participants_before = intval($results[0]->participants);


    //         $table_name = $wpdb->prefix . 'ipf_days_date';
    //         $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($booking->date_id)));

    //         if ($row) {

    //             $bookings_now = $row->bookings;

    //             $updated_participants = ($bookings_now - $participants_before) + $booking->participants;

    //             $wpdb->update(
    //                 $table_name,
    //                 array('bookings' => $updated_participants),
    //                 array('id' => $booking->date_id),
    //                 array('%d'),
    //                 array('%d')
    //             );
    //         }


    //         $table_name = $wpdb->prefix . 'ipf_bookings';

    //         $result = $wpdb->update(
    //             $table_name,
    //             (array) $booking,
    //             array('id' => $booking->id),
    //             array(
    //                 '%d',
    //                 '%d',
    //                 '%s',
    //                 '%d',
    //                 '%s',
    //                 '%s',
    //                 '%s',
    //                 '%s',
    //                 '%s',
    //                 '%d'
    //             ),
    //         );


    //         if ($result === false) {

    //             $response = array(
    //                 'success' => true,
    //                 'htmlToPrint' => '<div class="alert alert-success" role="alert">Prenotazione aggiornata!</div>',

    //             );
    //             exit();

    //         } else {

    //             $response = array(
    //                 'success' => true,
    //                 'htmlToPrint' => '<div class="alert alert-success" role="alert">Prenotazione aggiornata!</div>',
    //                 'newdate' => $booking->date_id
    //             );

    //             wp_send_json($response);
    //             exit();
    //         }





    //     }

    //     exit();
    // }



}
