<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\PaymentDetail;
use App\Models\PaymentLog;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;

class PaymentDetailService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Insert payment details
     */
    public function  addPaymentDetails($paymentDetails)
    {

        try {

            $paymentDetail = PaymentDetail::create([
                'user_id' => $paymentDetails['user_id'],
                'course_id' => $paymentDetails['course_id'],
                'payment_trans_id' => $paymentDetails['payment_trans_id'],
                'price' => $paymentDetails['price'],
                'is_manual_payment' => $paymentDetails['is_manual_payment'],
                'course_currency_id' => $paymentDetails['course_currency_id'],
                'promo_code_id' => $paymentDetails['promo_code_id'],
            ]);

            return $paymentDetail;
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return false;
        }
    }

    //add to payment log
    public function addToPaymentLog($data)
    {

        try {
            $paymentDetail = PaymentLog::create([
                'course_id' => $data['course_id'],
                'currency_id' => $data['currency_id'],
                'price' => $data['price'],
                'promo_code_id' => $data['promo_code_id'],
                'user_id' => $data['user_id'],
                'promo_discount_type' => $data['promo_discount_type'],
                'promo_discount' => $data['promo_discount'],
                'currency' => $data['currency'],
                'is_one_time_promo' => $data['is_one_time_promo'],
                'promo_code' => $data['promo_code']
            ]);
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
