<?php

class TFIP_Utils {
    
    

    public static function TFIP_Utiles_Format_Existing_Timeslots($timeslots, $dayId, $slotid = 0, $slotTime = null)
    {
        $slots  = null;

        if (count($timeslots) == 1) {

            $ts_item = $timeslots[0]['ts']; 
        
            $start = new DateTime($ts_item->timeslotstart);
            $end   = new DateTime($ts_item->timeslotend);
        
            $interval = new DateInterval('PT15M'); 
            $period = new DatePeriod($start, $interval, $end); 
            
            foreach ($period as $time) {
                $slot_ = [
                    'id'               => $ts_item->id,
                    'id_date'          => $dayId,
                    'timeslotstart'    => $time->format('H:i'),
                    'timeslotend'      => $ts_item->timeslotend,
                    'max_bookings'     => $ts_item->max_bookings,
                    'active_bookings'  => $ts_item->active_bookings,
                    'active'           => $ts_item->active,
                    'timeslotTimeStr'  => $time->format('H:i'),
                    'timeslotSelected' => $slotTime != null && $slotTime == $time->format('H:i') ? 1 : 0,
                ];
        
                $slots[] = [
                    'ts' => $slot_
                ]; 
            } 
              
        }else
        {
            foreach($timeslots as $tss)
            {
                $ts = $tss['ts'];

                $slot_ = [
                    'id'               => $ts->id,
                    'id_date'          => $dayId,
                    'timeslotstart'    => $ts->timeslotstart,
                    'timeslotend'      => $ts->timeslotend,
                    'max_bookings'     => $ts->max_bookings,
                    'active_bookings'  => $ts->active_bookings,
                    'active'           => $ts->active,
                    'timeslotTimeStr'  => $ts->timeslotstart . ' - ' . $ts->timeslotend,
                    'timeslotSelected' => $slotid != null && $ts->id == $slotid ? 1 : 0,
                ];

                $slots[] = [
                    'ts' => $slot_
                ]; 
            }
            
        }

        return $slots;
    }


    public static function TFIP_Utils_Format_No_Create_Default_Timeslots($dayId) {
        
        $slots = [];
        $default_slots = get_option('tfip_timeslots', []);

        if ( count($default_slots) == 1 ) {
            $start = new DateTime($default_slots[0]['start']);
            $end   = new DateTime($default_slots[0]['end']);

            $interval = new DateInterval('PT15M');
            $period   = new DatePeriod($start, $interval, $end);

            foreach ( $period as $time ) {
                
                $ts = [
                    'id'               => '',
                    'id_date'          => $dayId,
                    'timeslotstart'    => $time->format('H:i'),
                    'timeslotend'      => $time->format('H:i'),
                    'max_bookings'     => get_option('tfip_default_capienza', true),
                    'active_bookings'  => 0,
                    'active'           => 1,
                    'timeslotTimeStr'  => $time->format('H:i'),
                    'timeslotSelected' => 0,
                ];

                $slots[] = [
                    'ts' => $ts
                ];
            }
            
        } else {
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
                ];

                $slots[] = [
                    'ts' => $ts
                ];
            }
        }

        return $slots;
    }
}
