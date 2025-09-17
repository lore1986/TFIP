<?php

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class TFIP_Database {


    /**
     * DAY MANAGMENT
    */

    /**
     * Create an active day entry with default capacity.
     *
     * @param int $id Day timestamp.
     * @return object|null Row from tfip_active_days table or null if no default capacity.
    */

    public function TFIP_Database_Create_Active_Day($id) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'tfip_active_days';
        $default_capienza = get_option('tfip_default_capienza', false);
    
        $id = intval($id);
    
        if ($default_capienza === false) {
            return null;
        }
    
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id_date = %d",
            $id
        ));
    
        if ($exists == 0) {
            $data = [
                'id_date' => $id,
                'day_max' => intval($default_capienza),
                'active'  => 1
            ];
            $format = ['%d', '%d', '%d'];
    
            $wpdb->insert($table_name, $data, $format);
        }
    
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id_date = %d",
            $id
        ));
    
        wp_reset_query();
    
        return $result; 
    }


    /**
     * Create or retrieve an active day entry 
     *
     * @param int $id Day timestamp.
     * @return object|null Row from tfip_active_days table or null if no default capacity.
    */
    public function TFIP_Booking_Get_Create_Active_Day($day_timestamp)
    {
        $new_active_day = $this->TFIP_Database_Get_Active_Day($day_timestamp);

        if($new_active_day == null)
        {
            $new_active_day = $this->TFIP_Database_Create_Active_Day($day_timestamp);
        }

        return $new_active_day;
    }

    /**
     * Retrieve a single active day record.
     *
     * @param int $id Day timestamp.
     * @return object|null Active day row or null if not found.
    */
    public function TFIP_Database_Get_Active_Day($id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_active_days';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $id));
        wp_reset_query();

        return $result;
    }


    /**
     * Update the maximum availability for a given day based on its timeslots.
     *
     * @param int $id_date Day timestamp.
     * @return array Result with resolution and message.
     */
    public function TFIP_Database_Update_Day_Max_Availability($id_date) {
        
        global $wpdb;

        $newmax = get_option( 'tfip_default_capienza');

        $table_name = $wpdb->prefix . 'tfip_active_days';

        $timeslots = $this->TFIP_Database_Get_All_Timeslots_For_Active_Day($id_date);

        $ret = null;
        
        if(count($timeslots) > 0)
        {
        
            $newmax = array_sum(array_map(function($slot) {
                return (int) $slot->max_bookings;
            }, $timeslots));
            

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


    /**
     * Block or unblock an active day by setting its status.
     *
     * @param int $id_date Day timestamp.
     * @param int $status Active flag (1 = active, 0 = blocked).
     * @return bool True if updated, false otherwise.
     */
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


    /**
     * Delete a day from the tfip_active_days table.
     *
     * @param int $id_date Day timestamp.
     * @return int|false Number of rows deleted or false on failure.
     */
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


    /**
     * Get combined calendar and event data for a specific day.
     *
     * @param int $timestamp Day timestamp.
     * @return array Calendar data + events for the day.
     */
    function TFIP_Database_Query_Day_Data($timestamp)
    {
        $obj = null;
        $calendar_data = $this->TFIP_Database_query_date($timestamp);


        $events = $this->TFIP_Database_Get_Events('date', $timestamp);
        
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


    /**
     * Query full day data including timeslots, bookings, and availability.
     *
     * @param int $id_day Day timestamp.
     * @return array Day data with timeslots, bookings, availability, and stats.
     */
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
            $timeslots = $this->TFIP_Database_Get_All_Timeslots_For_Active_Day($active_day->id_date);

            if (count($timeslots) != 0) {

               
                foreach ($timeslots as $single_timeslot) {
                    
                    $ts = $single_timeslot;

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


    /**
     * TIMESLOT MANAGMENT
    */


    /**
     * Create new timeslot instances for a given day and update max availability.
     *
     * @param array $timeslots_in List of timeslots with keys: id_date, start, end, capacity, active_bookings, active.
     * @return array Result with resolution, slot_data (created timeslots), and message.
    */
    public function TFIP_Database_Create_TimeSlots($timeslots_in)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';  

        $created_timeslots = [];
        $overlap_verification = 0;

        foreach ($timeslots_in as $timeslot) {
            
            if (isset($timeslot['id_date'], $timeslot['start'], $timeslot['end'], $timeslot['capacity'], $timeslot['active_bookings'], $timeslot['active'])) {
            
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
                    $data['id'] = $wpdb->insert_id;
                    $created_timeslots[] = (object)$data;
                } else {
                    return [
                        'resolution' => 0,
                        'slot_data' => null,
                        'message' => "Error inserting timeslot with ID: " . $timeslot['id_date']
                    ];
                }

            } else {
                return [
                    'resolution' => 0,
                    'slot_data' => null,
                    'message' => "Missing required data for timeslot with ID: " . $timeslot['id_date']
                ];
            }
        }

        if($overlap_verification == count($timeslots_in)) {  
            return [
                'resolution' => 0, 
                'slot_data' => null,
                'message' => "Overlapping timeslot found for all timeslots given" 
            ];

        } else {
            $success = $this->TFIP_Database_Update_Day_Max_Availability($timeslot['id_date']);

            if((bool)((int)$success['resolution'])) {
                return [
                    'resolution' => 1,
                    'slot_data' => $created_timeslots,
                    'message' => "Timeslots inserted in DB"
                ];
                
            } else {
                return [
                    'resolution' => 0,
                    'slot_data' => null,
                    'message' => "Problems in updating max day availability as sum of all slots availability"
                ];
            }
        }
    }


    /**
     * Get all timeslots for a day, or create default timeslots if none exist.
     *
     * @param int $day_timestamp Timestamp of the day.
     * @return array Timeslots for that day.
    */
    public function TFIP_Database_Get_Create_Format_Timeslots($day_timestamp)
    {
        $timeslots = $this->TFIP_Database_Get_All_Timeslots_For_Active_Day($day_timestamp);
        
        if(count($timeslots) == 0)
        {
            return $this->TFIP_Booking_Create_And_Return_Default_Timeslots($day_timestamp);
        }else
        {
            return $timeslots;
        }

    }

    /**
     * Get all timeslots for a specific active day. Very much used.
     *
     * @param int $id_date Day timestamp.
     * @return array List of timeslot rows.
    */
    public function TFIP_Database_Get_All_Timeslots_For_Active_Day($id_date) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslots = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id_date = %d", $id_date));

        wp_reset_query();

        return $timeslots;
    }

    /**
     * Get a specific timeslot by its ID.
     *
     * @param int $id_slot Timeslot ID.
     * @return object|null Timeslot row or null if not found.
    */
    public function TFIP_Database_Get_Specific_Timeslot($id_slot) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot));
        wp_reset_query();

        return $timeslot_instance;
    }


    /**
     * Get the day timestamp linked to a specific timeslot.
     *
     * @param int $id_slot Timeslot ID.
     * @return int|null Day ID or null if not found.
    */
    public function TFIP_Database_Get_Date_From_Timeslot($id_slot) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $day_instance = $wpdb->get_row($wpdb->prepare("SELECT id_date FROM $table_name WHERE id = %d", $id_slot));
        wp_reset_query();

        return $day_instance->id_date;
    }

    /**
     * Block or unblock a specific timeslot.
     *
     * @param int $id_slot Timeslot ID.
     * @param int $status Active flag (1 = active, 0 = blocked).
     * @return bool True if updated, false otherwise.
    */
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


    /**
     * Update a timeslot’s active bookings count by adding/removing participants.
     * If this function is called after an update inject as parameter the already calculated difference between original participants and new participants
     * int is not unsigned and it will work with negative number to remove participants
     *
     * @param int $id_slot Timeslot ID.
     * @param int $participants Change in participant count (can be negative).
     * @return array Result with resolution, message, and updated availability.
    */
    public function TFIP_Database_Update_Timeslots_Availability($id_slot, $participants)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_timeslot_instances';
        $timeslot_instance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_slot));
        wp_reset_query();


        $ret = null;

        if($timeslot_instance)
        {
            $new_active_bookings = 0;
            
            $new_active_bookings = (int)$timeslot_instance->active_bookings + (int)$participants;
            
            if($new_active_bookings <= $timeslot_instance->max_bookings)
            {
                $table_name = $wpdb->prefix . 'tfip_active_days';

                $same_slot_bookings = $timeslot_instance->active_bookings + (int) $participants;

                if($same_slot_bookings < 0)
                {
                    return [
                        'resolution' => 0,
                        'message' => "Removing too many participants. not allowed",
                        'updated_availability' => 0
                    ];
                    
                }else
                {
                
                    if((int)$timeslot_instance->max_bookings >= $same_slot_bookings )
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
                                'updated_availability' => $timeslot_instance->max_bookings - $same_slot_bookings
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


    /**
     * Get the maximum number of bookings allowed for a specific timeslot.
     *
     * @param int $id_slot Timeslot ID.
     * @return array Result with resolution, message, and availability info.
    */
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


    /**
     * Update maximum capacity for a timeslot and recalculate day capacity.
     * To be called always after timeslot creation or booking change
     *
     * @param int $id_slot Timeslot ID.
     * @param int $new_max New maximum bookings.
     * @return void (sends JSON response).
    */
    public function TFIP_Database_Update_Timeslot_Max_Capacity($id_slot, $new_max)
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


    /**
     * Get confirmed bookings for a specific timeslot.
     *
     * @param int $slot_id Timeslot ID.
     * @return array List of confirmed booking rows.
    */
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

    /**
     * Delete a single timeslot instance.
     *
     * @param int $timeslot_id Timeslot ID.
     * @return int|false Number of rows deleted or false on failure.
    */
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

    /**
     * Create default timeslots for a given day and return them. Very much used.
     *
     * @param int $id_day_date Day timestamp.
     * @return array List of created default timeslots.
    */
    public function TFIP_Booking_Create_And_Return_Default_Timeslots($id_day_date)
    {
        $timeslots  = get_option('tfip_timeslots', []);

        foreach ($timeslots as &$ts) {
            $ts['id_date'] = $id_day_date;
            $ts['active_bookings'] = 0;  
            $ts['active'] = 1;          
        }

        $res_timeslots = $this->TFIP_Database_Create_TimeSlots($timeslots);

        return $res_timeslots['slot_data'];

    }


    /**
     * BOOKING MANAGMENT 
    */

    /**
     * Retrieve a single booking record.
     *
     * @param int $booking_id Booking ID.
     * @return object|null Booking row or null if not found.
    */
    public function TFIP_Database_Get_Single_Booking($booking_id) {
        
        global $wpdb;

        $table_name = $wpdb->prefix . 'tfip_bookings';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE idbooking = %d", $booking_id));
        wp_reset_query();

        return $result;
    }


    /**
     * Update booking details in the database.
     *
     * @param array $booking_data Array with updated booking info.
     * @return bool True if success, false if failed.
    */
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

    /**
     * Generate a unique random 6-character booking code. Recursive, i think only one.
     *
     * @return string Unique booking code.
    */
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
    

    /**
     * EVENTS QUERIES AND MANAGMENT 
    */

    /**
     * Retrieve events from or for a specific date
     *
     * @param string $mode type of request 'list' from a specific date / 'date' for a specific date
     * @param int $date unix timestamp UTC no timezone
     * @param int $maxnum Max number of events to retrieve (-1 for all of them)
     * @return array List of event objects.
    */
    public function TFIP_Database_Get_Events($mode = 'list', $date = null, $maxnum = -1) {
        
        // $timestamp_now = strtotime(date('d-m-Y'));
        $meta_query = [];
    
        if ($mode === 'list') {
            // future events
            $meta_query[] = [
                'key'     => '_TFIP_event_timestamp',
                'value'   => $date,
                'type'    => 'NUMERIC',
                'compare' => '>='
            ];
        } elseif ($mode === 'date' && $date) {
            // events for specific day
            $meta_query[] = [
                'key'     => '_TFIP_event_timestamp',
                'value'   => $date,
                'type'    => 'NUMERIC',
                'compare' => '='
            ];
        }
    
        $query_tfIpf = new WP_Query([
            'post_type'      => 'tfipfevent',
            'post_status'    => 'publish',
            'posts_per_page' => $maxnum,
            'meta_query'     => $meta_query,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_TFIP_event_timestamp',
            'order'          => 'ASC'
        ]);
    
        $events = [];
        while ($query_tfIpf->have_posts()) {
            $query_tfIpf->the_post();
    
            $the_single_ipf = new stdClass();
            $the_single_ipf->id = get_the_ID();
            $the_single_ipf->title = get_the_title($the_single_ipf->id);
    
            // Description: prefer post_content, fallback to meta
            $post_content = get_post_field('post_content', $the_single_ipf->id);
            $the_single_ipf->event_description = !empty($post_content)
                ? wp_kses_post($post_content)
                : get_post_meta($the_single_ipf->id, '_tfIpf_event_description', true);
    
            // Timeslot
            $timeslot_id = get_post_meta($the_single_ipf->id, '_TFIP_event_timeslot', true);
            $timeslot = $this->TFIP_Database_Get_Specific_Timeslot($timeslot_id);
    
            if ($timeslot) {
                $the_single_ipf->timeslottime = $timeslot->timeslotstart . " - " . $timeslot->timeslotend;
                $the_single_ipf->max_participants = intval($timeslot->max_bookings);
                $the_single_ipf->available = intval($timeslot->max_bookings) - intval($timeslot->active_bookings);
            } else {
                $the_single_ipf->timeslottime = null;
                $the_single_ipf->max_participants = 0;
                $the_single_ipf->available = 0;
            }
    
            // Other metadata
            $the_single_ipf->time_event = get_post_meta($the_single_ipf->id, '_TFIP_exact_event_time', true);
            $the_single_ipf->date_event = get_post_meta($the_single_ipf->id, '_TFIP_event_date', true);
            $the_single_ipf->event_type = get_post_meta($the_single_ipf->id, '_TFIP_event_type', true);
            $the_single_ipf->image_p = get_post_meta($the_single_ipf->id, '_tfIpf_event_image', true);
            $the_single_ipf->teamone = get_post_meta($the_single_ipf->id, '_TFIP_event_TeamOne', true);
            $the_single_ipf->teamtwo = get_post_meta($the_single_ipf->id, '_TFIP_event_TeamTwo', true);
    
            $events[] = $the_single_ipf;
        }
    
        wp_reset_query();
    
        return $events;
    }
    

    /**
     * DATABASE 
    */



    /**
     * Create required plugin database tables (active_days, timeslot_instances, bookings).
     *
     * @return void
     */
    public function TFIP_Database_create_tables() {
  
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_active_days = $wpdb->prefix . 'tfip_active_days';
        $table_timeslot_instances = $wpdb->prefix . 'tfip_timeslot_instances';
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

    /**
     * Drop all plugin-related database tables.
     *
     * @return void
    */
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

    

    

    
}