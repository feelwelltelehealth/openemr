<?php

/**
 * This file is part of OpenEMR.
 *
 * @link https://github.com/openemr/openemr/tree/master
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services;

include_once "{$GLOBALS['fileroot']}/library/appointments.inc.php";

/**
 * Finding Appoitment Opening for Scheduling
 *
 * @package OpenEMR\Events
 * @subpackage Appointments
 * @author Wesley Lima <wesley@wesleylima.com>
 * @copyright Copyright (c) 2020 Wesley Lima <wesley@wesleylima.com>
 */
class AppointmentOpenings
{
    private $slotbase;
    private $slotstime;

    // Record an event into the slots array for a specified day.
    function doOneDay($catid, $udate, $starttime, $duration, $prefcatid)
    {
        // echo $catid . ' - ' . $udate . ' - ' . $starttime . ' - ' . $duration . ' - ' . $prefcatid . PHP_EOL;
        global $input_catid;
        $udate = strtotime($starttime, $udate);
        if ($udate < $this->slotstime) {
            return;
        }

        $this->slotsecs = $GLOBALS['calendar_interval'] * 60;
        
        $i = (int) ($udate / $this->slotsecs) - $this->slotbase;
        $iend = (int) (($duration + $this->slotsecs - 1) / $this->slotsecs) + $i;
        echo "CAT ID " . $catid . '- ' . $starttime . ' - ' . date("Y-m-d h:i a", $udate);
        if ($iend > $this->slotcount) {
            echo " baaa";
            $iend = $this->slotcount;
        } else {
            echo ' caaa ' . $this->slotcount . ' - ' . $iend;
        }

        echo PHP_EOL;

        if ($iend <= $i) {
            $iend = $i + 1;
        }

        echo "i = $i and iend = $iend" . PHP_EOL;
        for (; $i < $iend; ++$i) {
            if ($catid == 2) {        // in office
                // If a category ID was specified when this popup was invoked, then select
                // only IN events with a matching preferred category or with no preferred
                // category; other IN events are to be treated as OUT events.
                if ($input_catid) {
                    if ($prefcatid == $input_catid || !$prefcatid) {
                        $this->slots[$i] |= 1;
                    } else {
                        $this->slots[$i] |= 2;
                    }
                } else {
                    $this->slots[$i] |= 1;
                }

                break; // ignore any positive duration for IN
            } elseif ($catid == 3) { // out of office
                $this->slots[$i] |= 2;
                break; // ignore any positive duration for OUT
            } else { // all other events reserve time
                $this->slots[$i] |= 4;
            }
            echo "AAA: $i is ". $this->slots[$i];
        }
    }

    function getSlots($input_catid, $input_search_days, $input_start_date, $provider_id_input)
    {
        // $slots
        // seconds per time slot
        $this->slotsecs = $GLOBALS['calendar_interval'] * 60;
        $catslots = 1;
        if ($input_catid) {
            $srow = sqlQuery("SELECT pc_duration FROM openemr_postcalendar_categories WHERE pc_catid = ?", array($input_catid));
            if ($srow['pc_duration']) {
                $catslots = ceil($srow['pc_duration'] / $this->slotsecs);
            }
        }        
        $info_msg = "";

        $searchdays = 7; // default to a 1-week lookahead
        if ($input_search_days) {
            $searchdays = $input_search_days;
        }
        // echo $searchdays . ' aa';
        // Get a start date.
        if (
            $input_start_date && preg_match(
                "/(\d\d\d\d)\D*(\d\d)\D*(\d\d)/",
                $input_start_date,
                $matches
            )
        ) {
            $sdate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } else {
            $sdate = date("Y-m-d");
        }

        // Get an end date - actually the date after the end date.
        preg_match("/(\d\d\d\d)\D*(\d\d)\D*(\d\d)/", $sdate, $matches);
        $edate = date(
            "Y-m-d",
            mktime(0, 0, 0, $matches[2], $matches[3] + $searchdays, $matches[1])
        );

        // compute starting time slot number and number of slots.
        $this->slotstime = strtotime("$sdate 00:00:00");
        $slotetime = strtotime("$edate 00:00:00");
        $this->slotbase  = (int) ($this->slotstime / $this->slotsecs);
        $this->slotcount = (int) ($slotetime / $this->slotsecs) - $this->slotbase;

        if ($this->slotcount <= 0 || $this->slotcount > 100000) {
            die("Invalid date range.");
        }
        $slotsperday = (int) (60 * 60 * 24 / $this->slotsecs);
        // echo "--aa- " .$this->slotsecs;

        // If we have a provider, search.
        //
        if ($provider_id_input) {
            $providerid = $provider_id_input;
            // Create and initialize the slot array. Values are bit-mapped:
            //   bit 0 = in-office occurs here
            //   bit 1 = out-of-office occurs here
            //   bit 2 = reserved
            // So, values may range from 0 to 7.
            //
            $this->slots = array_pad(array(), $this->slotcount, 0);

            // Note there is no need to sort the query results.
            //  echo $sdate." -- ".$edate;
            $query = "SELECT pc_eventDate, pc_endDate, pc_startTime, pc_duration, " .
                "pc_recurrtype, pc_recurrspec, pc_alldayevent, pc_catid, pc_prefcatid, pc_title " .
                "FROM openemr_postcalendar_events " .
                "WHERE pc_aid = ? AND " .
                "((pc_endDate >= ? AND pc_eventDate < ?) OR " .
                "(pc_endDate = '0000-00-00' AND pc_eventDate >= ? AND pc_eventDate < ?))";

            $sqlBindArray = array();
            array_push($sqlBindArray, $providerid, $sdate, $edate, $sdate, $edate);
            //////
            // print_r($sqlBindArray);
            $events2 = fetchEvents($sdate, $edate, null, null, false, 0, $sqlBindArray, $query);
            // TODO: Fix the query so this is not necessary
            $events2 = array_filter($events2, function($event) use ($sdate) {
                return $event['pc_eventDate'] ==  $sdate;
            });
            foreach ($events2 as $row) {
                $thistime = strtotime($row['pc_eventDate'] . " 00:00:00");
                $this->doOneDay(
                    $row['pc_catid'],
                    $thistime,
                    $row['pc_startTime'],
                    $row['pc_duration'],
                    $row['pc_prefcatid']
                );
            }
            //////

            // Mark all slots reserved where the provider is not in-office.
            // Actually we could do this in the display loop instead.
            $inoffice = false;
            for ($i = 0; $i < $this->slotcount; ++$i) {
                if (($i % $slotsperday) == 0) {
                    $inoffice = false;
                }

                if ($this->slots[$i] & 1) {
                    $inoffice = true;
                }

                if ($this->slots[$i] & 2) {
                    $inoffice = false;
                }

                if (! $inoffice) {
                    $this->slots[$i] |= 4;
                }
            }
        }
        return $this->slots;
    }

    function getSlotTimes($input_catid, $input_search_days, $input_start_date, $provider_id_input)
    {
        date_default_timezone_set('EDT');
        $slots_times = [];

        $slot_avail = $this->getSlots($input_catid, $input_search_days, $input_start_date, $provider_id_input);
        // echo "this->slotcount" . $this->slotcount;
        $catslots = 1;
        $x = 0;
        for ($i = 0; $i < $this->slotcount; ++$i) {
            $available = true;
            for ($j = $i; $j < $i + $catslots; ++$j) {
                $utime = ($this->slotbase + $i) * $this->slotsecs;
                $thisdate = date("Y-m-d h:i a", $utime);

                if ($slot_avail[$j] >= 4) {
                    $available = false;
                } else {
                    echo $j . "|" . $slot_avail[$j] . ' |' . $thisdate . "| " . PHP_EOL;
                    echo $x++;
                }
            }

            if (!$available) {
                continue; // skip reserved slots
            }

            $utime = ($this->slotbase + $i) * $this->slotsecs;
            $thisdate = date("Y-m-d", $utime);

            $atitle = "Choose " . date("h:i a", $utime);
            $adate = getdate($utime);

            
            $slots2[] = $utime;

            // If category duration is more than 1 slot, increment $i appropriately.
            // This is to avoid reporting available times on undesirable boundaries.
            $i += $catslots - 1;
        }
        echo PHP_EOL . $x;
        return $slots_times;
    }
}
