<?php

/**
 * EncounterRestController
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Matthew Vita <matthewvita48@gmail.com>
 * @copyright Copyright (c) 2018 Matthew Vita <matthewvita48@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\RestControllers;

use OpenEMR\Services\AppointmentService;
use OpenEMR\RestControllers\RestControllerHelper;

class CalendarRestController
{
    private $encounterService;

    /**
     * White list of patient search fields
     */
    private const SUPPORTED_SEARCH_FIELDS = array(
        "pid",
        "provider_id"
    );

    public function __construct()
    {
        $this->appointmentService = new AppointmentService();
    }

    public function getOpenAppointments($query)
    {
        $serviceResult = $this->appointmentService->getOpenAppointments($query);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }

    public function scheduleAppointment($data)
    {
        $serviceResult = $this->appointmentService->scheduleAppointment($query);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }
}
