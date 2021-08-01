<?php

/**
 * PasswordController
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Wesley Lima <wesley@wesleylima.com>
 * @copyright Copyright (c) 2021 Wesley Lima <wesley@wesleylima.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\RestControllers;

use OpenEMR\Services\AccountService;
use OpenEMR\RestControllers\RestControllerHelper;

class PasswordController
{
    private $appointmentService;

    public function __construct()
    {
        $this->accountService = new AccountService();
    }

    public function requestReset($email)
    {
        $serviceResult = $this->accountService->requestPasswordReset($email);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }

    
    public function verifyOtc($otc)
    {
        $serviceResult = $this->accountService->verifyResetOtc($otc);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }

    public function changePassword($data)
    {
        $serviceResult = $this->accountService->changePassword($data);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
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

    public function post($pid, $data)
    {
        $validationResult = $this->appointmentService->validate($data);

        $validationHandlerResult = RestControllerHelper::validationHandler($validationResult);
        if (is_array($validationHandlerResult)) {
            return $validationHandlerResult;
        }

        $serviceResult = $this->appointmentService->insert($pid, $data);
        return RestControllerHelper::responseHandler(array("id" => $serviceResult), null, 200);
    }

    public function delete($eid)
    {
        $serviceResult = $this->appointmentService->delete($eid);
        return RestControllerHelper::responseHandler($serviceResult, null, 200);
    }
    */
}
