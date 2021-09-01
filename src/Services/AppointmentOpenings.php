<?php

/**
 * This file is part of OpenEMR.
 *
 * @link https://github.com/openemr/openemr/tree/master
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services;

include_once "{$GLOBALS['fileroot']}/library/appointments.inc.php";

function sqlDate($timestamp) {
    $intTimeStamp = (int) $timestamp;
    return $edate = date( "Y-m-d", $intTimeStamp);
}

function sqlDateTime($timestamp) {
    $intTimeStamp = (int) $timestamp;
    return $edate = date("Y-m-d H:i:s", $intTimeStamp);
}
/**
 * Finding Appointment Opening for Scheduling
 *
 * @package OpenEMR\Events
 * @subpackage Appointments
 * @author Wesley Lima <wesley@wesleylima.com>
 * @copyright Copyright (c) 2020 Wesley Lima <wesley@wesleylima.com>
 */
class AppointmentOpenings
{

    function getSlots($input_catid, $input_search_days, $input_start_date, $provider_id_input)
    {
        // $slots
        // seconds per time slot
        // TODO: This should be configurable elsewhere
        $minutes_per_slot = 20; // $GLOBALS['calendar_interval'];
        $seconds_per_slot = $minutes_per_slot * 60;

        $providerid = $provider_id_input;

        $catslots = 1;
        if ($input_catid) {
            $srow = sqlQuery("SELECT pc_duration FROM openemr_postcalendar_categories WHERE pc_catid = ?", array($input_catid));
            if ($srow['pc_duration']) {
                $catslots = ceil($srow['pc_duration'] / $this->slotsecs);
            }
        }        

        // Get the apoitments in this date range
        $dt = new \DateTime();
        $now = $dt->getTimestamp();
        $start_date = strtotime($input_start_date);

        $end_date = $start_date + $input_search_days * (24*60*60); // Add 24 * number of days hours

        if ($start_date < $now) {
            $start_date = $now; //sqlDateTime
        }

        //echo sqlDateTime($start_date);

        // Note: "Out of Office" category will take up the rest of the day's availability (unless you add another "in office" avert after the OOO)
        // To block out selected time segments, use RESERVED


        $query = "SELECT pc_eid, pc_eventDate, pc_endDate, pc_startTime, pc_duration, " .
        "pc_recurrtype, pc_recurrspec, pc_alldayevent, pc_catid, pc_prefcatid, pc_title, pc_duration " .
        "FROM openemr_postcalendar_events " .
        "WHERE pc_aid = ? AND " .
        "((pc_endDate >= ? AND pc_eventDate < ?) OR " .
        "(pc_endDate = '0000-00-00' AND pc_eventDate >= ? AND pc_eventDate < ?))";

        $sqlBindArray = array();
        array_push($sqlBindArray, $providerid, sqlDate($start_date), sqlDate($end_date), sqlDate($start_date), sqlDate($end_date));
        $events = fetchEvents(sqlDate($start_date), sqlDate($end_date), null, null, false, 0, $sqlBindArray, $query);


        $number_of_days = (int) $input_search_days;

        $seconds_per_day = 24 * 60 * 60;

        $number_of_slots = $seconds_per_day * $number_of_days / $seconds_per_slot;

        // TODO: make this not 0
        $day_start = strtotime("midnight", $start_date);
        $slots = [];
        $slot_availability = []; // true if available false if not
        for ($i = 0; $i < $number_of_slots; $i++) {
            $slots[] = $day_start + ($i*$seconds_per_slot);
        }

        foreach($events as $event) {
            $minutes = $event['pc_duration'];
            $seconds = $minutes * 60;

            $event_start = strtotime($event['pc_eventDate'] . ' ' . $event['pc_startTime']);  // strtotime($event['pc_startTime']);
            $event_end = $event_start + $event['pc_duration']; // $seconds
            $catid = $event['pc_catid'];

            foreach ($slots as $i => $slot) {
                if (!array_key_exists($i, $slot_availability)) {
                    // This slot is available but there could be an event that fills it later in the loop
                    $slot_availability[$i] = 0;
                }

                if ($slot >= $event_start) {
                    if ($catid == 2) { // 2 is the available to apointments category
                        if ($slot_availability[$i] == 0 ) {
                            // There is no availability on this slot yet. So we'll call this slot AVAILABLE!
                            $slot_availability[$i] |= 1;
                        }
                    } elseif ($catid == 3) { // out of office
                        if ($slot_availability[$i] == 1) {
                            $slot_availability[$i] = 0;
                        }
                    } else { // Other events (like appointments)
                        if ($slot <= $event_end) {
                            $slot_availability[$i] |= 4;
                        }
                    }
                }
            }
        }
        $this->slots = $slots;
        return $slot_availability;
    }

    function getSlotTimes($input_catid, $input_search_days, $input_start_date, $provider_id_input)
    {
        // date_default_timezone_set('Americas/New York');
        $slots_times = [];

        $slots = $this->getSlots($input_catid, $input_search_days, $input_start_date, $provider_id_input);

        $now = new \DateTime();
        $now_timestamp = $now->getTimestamp() + 3600; // Give us an hour to prep
        $slots_avail = [];
        foreach ($slots as $slot_index => $availability) {
            if ($availability === 1  && ($this->slots[$slot_index]  > $now_timestamp)) {
                $slots_avail[] = $slot_index;
            }
        }

        // This won't be exact with thsi algorithm. It will always round to the lowest near divisible integer
        $maximum_slots = 5;

        $maximum_slots = $maximum_slots;
        $slot_count = count($slots_avail);
        //echo "total slots $slot_count";

        $skip = 1;
        if ($slot_count > $maximum_slots) {
            if ( ($slot_count / $maximum_slots) >= 2 ) {
                $skip = (int) ($slot_count / $maximum_slots);
            };
        }
        //echo "skip $skip";
        // $day_of_week = date("w", $this->slots[0]) + 1;
        $now = new \DateTime();
        $i = 0;
        $j = 0;
        while ($i < $slot_count) {
            $index = $slots_avail[$i];
            $time = $this->slots[$index];
            $slots_times[] = $time;
            // $j++;
            $i++;
            
        }
        // foreach($slot_avail as $index) {

        // }
        return $slots_times;
    }
}
