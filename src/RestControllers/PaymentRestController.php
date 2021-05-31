<?php

/**
 * AppointmentRestController
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Wesley Lima <wesley@wesleylima.com>
 * @copyright Copyright (c) 2021 Wesley Lima <wesley@wesleylima.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\RestControllers;

use OpenEMR\Services\PaymentService;
use OpenEMR\RestControllers\RestControllerHelper;

class PaymentRestController
{
    private $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }
    /*
    public function getOne($eid)
    {
        $serviceResult = $this->appointmentService->getAppointment($eid);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }

    public function getAll()
    {
        $serviceResult = $this->appointmentService->getAppointmentsForPatient(null);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }

    public function getAllForPatient($pid)
    {
        $serviceResult = $this->appointmentService->getAppointmentsForPatient($pid);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }
    // paymentService
    */
    public function post($pid, $data)
    {
        $validationResult = $this->paymentService->validate($data);

        $validationHandlerResult = RestControllerHelper::validationHandler($validationResult);
        if (is_array($validationHandlerResult)) {
            return $validationHandlerResult;
        }

        $serviceResult = $this->paymentService->insert($pid, $data);
        return RestControllerHelper::responseHandler(array("id" => $serviceResult), null, 200);
    }
}
