<?php

class TFIP_Utils {

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


    public static function TFIP_Utils_Format_DateTime($idDate, $time)
    {
        $dt = new DateTime();
        $dt->setTimestamp($idDate);
        list($hours, $minutes) = explode(":", $time);
        $dt->setTime($hours, $minutes, 0); 
        
        return $dt->format('Y-m-d H:i:s.u');
    }
    
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
    


    public static function TFIP_Utiles_Format_Existing_Timeslots($timeslots, $dayId, $slotid = 0, $timebooking = null)
    {
        $slots  = null;
        $selected = 0;


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


            //$ts['exact_times'] = 


            $slots[] = [
                'ts' => $slot_
            ]; 
        }

        return $slots;
    }
    
    public static function TFIP_Utils_Check_Time_Range($refSlotStartTime, $refSlotEndTime, $newSlotStartTime, $newSlotEndTime) {

        $innerStartObj = DateTime::createFromFormat('H:i', $refSlotStartTime);
        $innerEndObj   = DateTime::createFromFormat('H:i', $refSlotEndTime);
        $outerStartObj = DateTime::createFromFormat('H:i', $newSlotStartTime);
        $outerEndObj   = DateTime::createFromFormat('H:i', $newSlotEndTime);
    

        return ($innerStartObj >= $outerStartObj && $innerEndObj <= $outerEndObj);
    }

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
                'exact_time'        => self::TFIP_Utils_Search_Return_Exact_Times($ts->timeslotstart, $ts->timeslotend)
            ];

            $slots[] = [
                'ts' => $ts
            ];
        }

        // if ( count($default_slots) == 1 ) {
        

        //     $ts = [
        //         'id'              => '',
        //         'id_date'         => $dayId,
        //         'timeslotstart'   => $default_slots[0]['start'],
        //         'timeslotend'     => $default_slots[0]['end'],
        //         'max_bookings'    => get_option('tfip_default_capienza', true),
        //         'active_bookings' => 0,
        //         'active'          => 1,
        //         'timeslotTimeStr'  => $default_slots[0]['start'] . ' - ' . $default_slots[0]['end'],
        //         'timeslotSelected' => 1,
        //     ];


        //     $slots[] = [
        //         'ts' => $ts
        //     ];


            
            
        // } else {

        //     foreach ( $default_slots as $ts ) {
                
        //         $ts = [
        //             'id'              => '',
        //             'id_date'         => $dayId,
        //             'timeslotstart'   => $ts['start'],
        //             'timeslotend'     => $ts['end'],
        //             'max_bookings'    => get_option('tfip_default_capienza', true),
        //             'active_bookings' => 0,
        //             'active'          => 1,
        //             'timeslotTimeStr'  => $ts['start'] . ' - ' . $ts['end'],
        //             'timeslotSelected' => 0,
        //         ];

        //         $slots[] = [
        //             'ts' => $ts
        //         ];
        //     }
        // }

        return $slots;
    }
}
