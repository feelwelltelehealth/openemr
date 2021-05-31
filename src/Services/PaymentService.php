<?php

/**
 * AppointmentService
 *
  * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Wesley Lima <wesley@wesleylima.com>
 * @copyright Copyright (c) 2021 Wesley Lima <wesley@wesleylima.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services;

// \Stripe\Stripe::setApiKey(\getenv('STRIPE_SECRET_KEY'));
// \Stripe\Stripe::setClientId(\getenv('STRIPE_CLIENT_ID'));
use Stripe\StripeClient;
use Particle\Validator\Validator;
use OpenEMR\Services\AppointmentOpenings;

class PaymentService
{

  /**
   * Default constructor.
   */
    public function __construct()
    {
    }

    public function validate($payment)
    {
        $validator = new Validator();
        /*
        $validator->required('pc_eid')->numeric();
        $validator->required('pc_catid')->numeric();
        $validator->required('pc_title')->lengthBetween(2, 150);
        $validator->required('pc_duration')->numeric();
        $validator->required('pc_hometext')->string();
        $validator->required('pc_apptstatus')->string();
        $validator->required('pc_eventDate')->datetime('Y-m-d');
        $validator->required('pc_startTime')->length(5); // HH:MM is 5 chars
        $validator->required('pc_facility')->numeric();
        $validator->required('pc_billing_location')->numeric();
        */
        return $validator->validate($payment);
    }

    public function insert($pid, $data)
    {
        /*
        $stripe = new \Stripe\StripeClient("sk_test_4eC39HqLyjWDarjtT1zdp7dc");
        $stripe->charges->create([
        "amount" => 2000,
        "currency" => "usd",
        "source" => "tok_visa", // obtained with Stripe.js
        "metadata" => ["order_id" => "6735"]
        ]);
        */
        // $stripe = new \Stripe\StripeClient("sk_test_4eC39HqLyjWDarjtT1zdp7dc");
        $stripe = new StripeClient(\getenv('STRIPE_SECRET_KEY'));
        $intent = $stripe->paymentIntents->create([
            "amount" => $data['amount'], // 2000,
            "currency" => "usd",
            "payment_method" => $data['token'], // "tok_visa", // obtained with Stripe.js
            "metadata" => ["order_id" => "6735"]
        ]);

        // echo 'status: ' . $intent->status; Should be requires_confirmation at this point

        $confirmation = $stripe->paymentIntents->confirm($intent->id);
        
        if ($confirmation->status !== 'succeeded') {
            return false;
        }

        // TODO: Use a fixed decimal point type
        $total_captured = 0; 
        foreach($confirmation->charges->data as $charges) {
            $total_captured = $total_captured + $charges->amount_captured;
        }

        if ($total_captured <= 0 ) {
            // Nothing was charged!
            return false;
        }

        if ($total_captured > $data['amount']) {
            // TODO:
            // WE CHARGED TOO MUCH
        }
        if ($total_captured < $data['amount']) {
            // TODO: WARN we charged, but not enough
        }


        $posted1 =  $total_captured;

        // print_r($confirmation);

        $d = new \DateTime();
        //$timestamp = date("H:i:s", strtotime($d));
        $timestamp = $d->format("Y-m-d H:i:s");
        /*
            `id`, `pid`, `dtime`, `encounter`, `user`, `method`, `source`, `amount1`, `amount2`, `posted1`, `posted2`
        */
        // $data["pid"] = $patient_id;
        $method = 'Stripe';

        
        $sql  = " INSERT INTO payments SET";
        $sql .= "     pid=?,";
        $sql .= "     dtime=?,";
        $sql .= "     method=?,";
        $sql .= "     source=?,";
        $sql .= "     amount1=?,";
        $sql .= "     posted1=?";


        $db_args = array(
            $pid,
            $timestamp,
            $method,
            $data["source"],
            $data['amount'],
            $posted1,
        );

        $results = sqlInsert(
            $sql,
            $db_args
        );
        // TODO: Log, but return success if payment is ok but SQL insert is not
        return $results;
    }
}
