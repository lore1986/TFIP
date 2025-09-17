<?php

class TFIP_Utils {

    /**
     * Normalize teams name so to find correct flag in the 'squadre' folder
     * @param string name of the team
     * @return string all lowercase team name except first letter + .png ending Manchester City ==> Manchestercity.png
    */
    public static function TFIP_Utils_Normalize_Team_Name($name) {

        $name = str_replace(' ', '', $name);
        $name = strtolower($name);

        $name = ucfirst($name);
        $name = $name . '.png';
    
        return $name;
    }


    /**
     * Return periods of time in between timeinterval. Also select timeperiod for booking time.
     * @param string timeslot start 16:00 format
     * @param string timeslot end 16:00 format
     * @param string nullable exact time for selected selection 16:00 format
     * @return array an array of time period of 15 minutes between timestart and timeend. 
     *               if timebooking and if timebooking falls between timestart and timeend, provided corrected time period is selected.
    */
    public static function TFIP_Utils_Search_Return_Exact_Times($timestart, $timeend, $timebooking = null)
    {
        $start = DateTime::createFromFormat('H:i', $timestart);
        $end   = DateTime::createFromFormat('H:i', $timeend);

        if ( ! $start || ! $end ) {
            return [];
        }
    
        $endAdjusted = clone $end;

        // $diffMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        // if ( $diffMinutes > 60 ) {
        //     $endAdjusted->modify('-1 hour');
        // }
    
        $interval = new DateInterval('PT15M');
        $period   = new DatePeriod($start, $interval, $endAdjusted);
    
        $exactTimes = [];

        foreach ($period as $dt) {

            $slotStart = $dt->format('H:i');
            
            $exactTimes[] = [
                'et' => [
                    'id'   => $slotStart,
                    'sel' => $slotStart == $timebooking ? 1 : 0
                ]
            ];
        }

        return $exactTimes;
    }


    /**
     * Format date time for comparison
     * @param int unix timestamp of date
     * @param string exact time specified in format 16:00
     * @return datetime formatted datetime 
     */
    public static function TFIP_Utils_Format_DateTime($idDate, $time)
    {
        $dt = new DateTime();
        $dt->setTimestamp($idDate);
        list($hours, $minutes) = explode(":", $time);
        $dt->setTime($hours, $minutes, 0); 
        
        return $dt->format('Y-m-d H:i:s.u');
    }
    
    /**
     * Return correct timeslot for selected time. loops timeslots->timeslotstart && timeslots->timeslotend
     * @param array array or object of all timeslots
     * @param int unix timestamp UTC of the day
     * @param string time of booking in format $booking_dt = $booking_dt->format('Y-m-d H:i:s'); 
     * @return object specific timeslot 
    */
    public static function TFIP_Utils_Return_Timeslot_For_Selected_Time( $timeslots, $active_day_id, $timebooking ) {

        $dt = new DateTime();
        $dt->setTimestamp( $active_day_id );
    
        foreach ( $timeslots as $ts_item ) {
           
    
            list( $hours, $minutes ) = explode( ":", $ts_item->timeslotstart );
            $dt->setTime( $hours, $minutes, 0 );
            $ts_start = clone $dt;

            list( $hours, $minutes ) = explode( ":", $ts_item->timeslotend );
            $dt->setTime( $hours, $minutes, 59 ); 
            $ts_end = clone $dt;
    

            $booking_dt = new DateTime( $timebooking );
    
            if ( $booking_dt >= $ts_start && $booking_dt <= $ts_end ) {
                return $ts_item;
            }
        }
    
        return null; 
    }
    

    /**
     * Correctly format existing timeslots for frontend and js. adds ts to each element of the array.
     * @param array array or object of all timeslots
     * @param int unix timestamp UTC of the day
     * @param int in case specific timeslot id for selection 
     * @param string time of booking in format 16:00 for selection
     * @return array formatted array of timeslots. 
     * 
    */
    public static function TFIP_Utiles_Format_Existing_Timeslots($timeslots, $dayId, $slotid = 0, $timebooking = null)
    {
        $slots  = null;
        foreach($timeslots as $tss)
        {
            $ts = $tss;

            $slot_ = [
                'id'               => $ts->id,
                'id_date'          => $dayId,
                'timeslotstart'    => $ts->timeslotstart,
                'timeslotend'      => $ts->timeslotend,
                'max_bookings'     => $ts->max_bookings,
                'active_bookings'  => $ts->active_bookings,
                'active'           => $ts->active,
                'timeslotTimeStr'  => $ts->timeslotstart . ' - ' . $ts->timeslotend,
                'timeslotSelected' => ($slotid != null && $ts->id == $slotid) ? 1 : 0,
                'exact_time'        => self::TFIP_Utils_Search_Return_Exact_Times($ts->timeslotstart, $ts->timeslotend, $timebooking)
            ];

            $slots[] = [
                'ts' => $slot_
            ]; 
        }

        return $slots;
    }
    

    /**
     * Check if timerange falls inside another timerange
     * @param string time in format 16:00 first range start
     * @param string time in format 16:00 first range end
     * @param string time in format 16:00 second range start
     * @param string time in format 16:00 second range end
     * @return bool true if range falls, false if not
     * 
    */
    public static function TFIP_Utils_Check_Time_Range($refSlotStartTime, $refSlotEndTime, $newSlotStartTime, $newSlotEndTime) {

        $innerStartObj = DateTime::createFromFormat('H:i', $refSlotStartTime);
        $innerEndObj   = DateTime::createFromFormat('H:i', $refSlotEndTime);
        $outerStartObj = DateTime::createFromFormat('H:i', $newSlotStartTime);
        $outerEndObj   = DateTime::createFromFormat('H:i', $newSlotEndTime);
    

        return ($innerStartObj >= $outerStartObj && $innerEndObj <= $outerEndObj);
    }

    /**
     * Correctly format non existing (default) timeslots for frontend and js. adds ts to each element of the array.
     * Retrieve default timeslots from plugin settings. get_option.
     * @param int unix timestamp UTC of the day
     * @return array formatted array of timeslots. 
     * 
    */
    public static function TFIP_Utils_Format_Default_Timeslots($dayId) {
        
        $slots = [];
        $default_slots = get_option('tfip_timeslots', []);

        foreach ( $default_slots as $ts ) {
                
            $ts = [
                'id'              => '',
                'id_date'         => $dayId,
                'timeslotstart'   => $ts['start'],
                'timeslotend'     => $ts['end'],
                'max_bookings'    => get_option('tfip_default_capienza', true),
                'active_bookings' => 0,
                'active'          => 1,
                'timeslotTimeStr'  => $ts['start'] . ' - ' . $ts['end'],
                'timeslotSelected' => 0,
                'exact_time'        => self::TFIP_Utils_Search_Return_Exact_Times($ts['start'], $ts['end'])
            ];

            $slots[] = [
                'ts' => $ts
            ];
        }

        return $slots;
    }
}
