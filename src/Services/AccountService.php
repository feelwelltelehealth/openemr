<?php

/**
 * AccountService
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Matthew Vita <matthewvita48@gmail.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2018 Matthew Vita <matthewvita48@gmail.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services;

// TODO: Fix autoloader
// include_once("{$GLOBALS['fileroot']}/src/Services/AppointmentOpenings.php");
require_once("../portal/account/account.lib.php");

use Particle\Validator\Validator;
use OpenEMR\Services\AppointmentOpenings;
use OpenEMR\Common\Utils\RandomGenUtils;
use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Auth\AuthHash;
use DateTime;
use MyMailer;

class AccountService
{

    /**
    * Default constructor.
    */
    public function __construct()
    {
    }

    public function validate($appointment)
    {
        $validator = new Validator();

        $validator->required('email')->email();
        return $validator->validate($appointment);
    }

    public function insert($pid, $data)
    {
        $startTime = date("H:i:s", strtotime($data['pc_startTime']));
        $endTime = $startTime + $data['pc_duration'];

        $sql  = " INSERT INTO openemr_postcalendar_events SET";
        $sql .= "     pc_pid=?,";
        $sql .= "     pc_aid=?,";
        $sql .= "     pc_catid=?,";
        $sql .= "     pc_title=?,";
        $sql .= "     pc_duration=?,";
        $sql .= "     pc_hometext=?,";
        $sql .= "     pc_eventDate=?,";
        $sql .= "     pc_apptstatus=?,";
        $sql .= "     pc_startTime=?,";
        $sql .= "     pc_endTime=?,";
        $sql .= "     pc_facility=?,";
        $sql .= "     pc_billing_location=?,";
        $sql .= "     pc_informant=1,";
        $sql .= "     pc_eventstatus=1,";
        $sql .= "     pc_sharing=1";

        $results = sqlInsert(
            $sql,
            array(
                $pid,
                $data["pc_aid"],
                $data["pc_catid"],
                $data["pc_title"],
                $data["pc_duration"],
                $data["pc_hometext"],
                $data["pc_eventDate"],
                $data['pc_apptstatus'],
                $startTime,
                $endTime,
                $data["pc_facility"],
                $data["pc_billing_location"]
            )
        );

        return $results;
    }

    private function generateOnetime()
    {
        // TODO: Factor out the $one_time code in account.lib.php in or out of this function
        $token_new = RandomGenUtils::createUniqueToken(32);
        $pin = RandomGenUtils::createUniqueToken(6);
    
        // Will send a link to user with encrypted token
        $crypto = new CryptoGen();
        $token = $crypto->encryptStandard($token_new);
        $expiry = new DateTime('NOW');
        if (empty($token)) {
            // Serious issue if this is case, so die.
            error_log('OpenEMR Error : Portal token encryption broken - exiting');
            die();
        }
        $encoded_link = sprintf("%s?%s", attr($GLOBALS['portal_onsite_two_address']), http_build_query([
            'forward' => $token,
            'site' => $_SESSION['site_id']
        ]));
    
        // Will store unencrypted token in database with the pin and expiration date
        $one_time = $token_new . $pin . bin2hex($expiry->format('U'));

        return $one_time;
    }

    public function requestPasswordReset($email)
    {
        if (!(validEmail($email))) {
            return false;
        }

        $patientData = sqlQuery("SELECT `patient_data`.*, `patient_access_onsite`.`portal_username` FROM `patient_data` LEFT JOIN `patient_access_onsite` ON `patient_access_onsite`.`pid` = `patient_data`.`pid` WHERE `patient_data`.`email`=? ", array($email));
        //if ($patientData['hipaa_allowemail'] != "YES" || empty($patientData['email']) || empty($GLOBALS['patient_reminder_sender_email'])) {
        //    return false;
        //}
        //print_r($patientData);
        //die();
        // Check if $patientData[`pid`.`patient_access_onsite'] exists 
        $pid = $patientData['pid'];

        // generate portal_onetime, set portal_pwd_status = 0 and save to db
        $one_time = $this->generateOnetime();
        // TODO: create patient_access_onsite row if it doesn't exist??
        if ($patientData['portal_username'] || $patientData['portal_login_username']) {
            sqlStatementNoLog("UPDATE patient_access_onsite SET portal_username=?,portal_login_username=?,portal_onetime=?,portal_pwd_status=0 WHERE pid=?", [
                $email,
                $email,
                $one_time,
                $pid
            ]);
        } else {
            if ($pid) {
                sqlStatementNoLog("INSERT INTO patient_access_onsite SET portal_username=?,portal_login_username=?,portal_onetime=?,portal_pwd_status=0,pid=?", [
                    $email,
                    $email,
                    $one_time,
                    $pid
                ]);
            } else {
                error_log('OpenEMR Error : Could not set password reset for ' . $email);
            }

        }

        // get portal_onetime and stick it into the email
        if (!(validEmail($GLOBALS['patient_reminder_sender_email']))) {
            return false;
        }
        // TODO: Make this base url dynamic
        $one_time_url = 'https://portal.feelwelltelehealth.com/password_reset?otc='.$one_time;

        $message .= "<strong>" . xlt("Follow the link below to reset your password.") . "</strong><br />";
        $message .= '<a href="' . $one_time_url . '">' . $one_time_url . '</a><br />';
        $message .= xlt("Thank you for allowing us to serve you.") . ":<br />";
    
        $mail = new MyMailer();
        $pt_name = $patientData['fname'] . ' ' . $patientData['lname'];
        $pt_email = $patientData['email'];
        $email_subject = xl('Reset Your Password');
        $email_sender = $GLOBALS['patient_reminder_sender_email'];
        $mail->AddReplyTo($email_sender, $email_sender);
        $mail->SetFrom($email_sender, $email_sender);
        $mail->AddAddress($pt_email, $pt_name);
        $mail->Subject = $email_subject;
        $mail->MsgHTML("<html><body><div class='wrapper'>" . $message . "</div></body></html>");
        $mail->IsHTML(true);
        $mail->AltBody = $message;
    
        if ($mail->Send()) {
            return true;
        } else {
            $email_status = $mail->ErrorInfo;
            error_log("EMAIL ERROR: " . errorLogEscape($email_status), 0);
            return false;
        }
        /*
        $openings_repository = new AppointmentOpenings();
        $slots = $openings_repository->getSlotTimes($query['email']);
        return $slots;
        */
    }

    /*
      Checks if this one time code is valid
    */
    public function verifyResetOtc($otc)
    {

        $patientAccessOnsite = sqlQuery("SELECT * FROM patient_access_onsite WHERE portal_onetime=?", [
            $otc
        ]);
        if ($patientAccessOnsite) {
            return true;
        }
        return false;
    }

    public function changePassword($data)
    {
        $otc = $data['otc'];
        $patientAccessOnsite = sqlQuery("SELECT * FROM patient_access_onsite WHERE portal_onetime=?", [
            $otc
        ]);

        if ($patientAccessOnsite) {
            $clear_pass = $data['password'];
            $hash = (new AuthHash('auth'))->passwordHash($clear_pass);
            sqlStatementNoLog("UPDATE patient_access_onsite SET portal_pwd=?,portal_pwd_status=1,portal_onetime=? WHERE pid=?", [
                $hash,
                null,
                $patientAccessOnsite['pid'],
            ]);
            return true;
        }
        return false;
    }

}
