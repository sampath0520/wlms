<?php

namespace App\Services;


use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\PaymentGatewayResponse;
use Illuminate\Http\Request;
use Stripe\Error\Card;
use Stripe\Error\Base;
use Validator;
use DB;
use Exception;

class PaymentService
{

    /**
     * @var \Stripe\StripeClient
     */
    private $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET'));
    }


    /**
     * @param $paymentRequest
     * @return array
     */
    public function processStripePayment($paymentRequest)
    {
        try {

            $payment_transaction_id = $paymentRequest['payment_transaction_id'];

            $token = $this->stripe->tokens->create([
                'card' => [
                    'number' => $paymentRequest['card_no'],
                    'exp_month' => $paymentRequest['exp_month'],
                    'exp_year' => $paymentRequest['exp_year'],
                    'cvc' => $paymentRequest['cvc'],
                ],
            ]);

            $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $token));

            try {
                $customer = $this->stripe->customers->create(
                    array(
                        "source" => $token->id,
                        "description" => "Winspert Customer"
                    )
                );


                $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $customer));


                $paymentRequest = array(
                    'stripe_customer_id' => $customer->id,
                    'payment_method_id' => $customer->default_source,
                    'total_value' => $paymentRequest['total_value'],
                    'currency' => $paymentRequest['currency'],
                    'payment_transaction_id' => $paymentRequest['payment_transaction_id'],
                );

                //process payments

                $paymentResponse = $this->doPayment($paymentRequest);


                if (isset($paymentResponse->status)) {

                    if (($paymentResponse->status != '') && ($paymentResponse->status === "succeeded")) {
                        $process = 1;
                    } else {
                        $process = 0;
                    }
                } else {
                    $process = 0;
                }
                if ($process == 1) {

                    return ['status' => 1, 'data' => $paymentResponse->id, 'message' => 'Payment Successful'];
                } else {
                    return ['status' => 0, 'data' => $paymentResponse->id, 'message' => 'Payment Failed'];
                }
            } catch (\Stripe\Error\Base $e) {
                ErrorLogger::logError($e);
                return false;
                // catch stripe errors
            } catch (Exception $e) {
                ErrorLogger::logError($e);
                return false;
                // Catch any other non-Stripe exceptions
            }

            $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $e->getMessage()));
            return ['status' => 0, 'data' => $e->getMessage(), 'message' => 'Payment Failed'];
        } catch (\Stripe\Exception\CardException $e) {
            ErrorLogger::logError($e);
            return false;
            // Since it's a decline, \Stripe\Exception\CardException will be caught
        } catch (\Stripe\Exception\RateLimitException $e) {
            ErrorLogger::logError($e);
            return false;
            // Too many requests made to the API too quickly
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ErrorLogger::logError($e);
            return false;
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            ErrorLogger::logError($e);
            return false;
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            ErrorLogger::logError($e);
            return false;
            // Network communication with Stripe failed
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ErrorLogger::logError($e);
            return false;
            // Display a very generic error to the user, and maybe send
            // yourself an email
        } catch (Exception $e) {
            ErrorLogger::logError($e);
            return false;
            // Something else happened, completely unrelated to Stripe
        }

        $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $e->getMessage()));
        return ['status' => 0, 'data' => $e->getMessage(), 'message' => 'Payment Failed'];
    }


    /**
     * @param $paymentRequest
     * @return string
     * service for make stripe payments
     */
    public function doPayment($paymentRequest)
    {

        try {

            $payment_transaction_id = $paymentRequest['payment_transaction_id'];

            $zeroDecimalCurrencies = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');

            if (!in_array($paymentRequest['currency'], $zeroDecimalCurrencies)) {
                $payment_amount = ($paymentRequest['total_value']) * 100;
            } else {
                $payment_amount = $paymentRequest['total_value'];
            }

            $paymentIntentCreate = $this->stripe->paymentIntents->create([
                "amount" => $payment_amount,
                "currency" => $paymentRequest['currency'],
                'payment_method_types' => ['card'],
                'customer' => $paymentRequest['stripe_customer_id']
            ]);

            $paymentIntentConfirm = $this->stripe->paymentIntents->confirm(
                $paymentIntentCreate->id,
                ['payment_method' => $paymentRequest['payment_method_id']]
            );

            $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $paymentIntentConfirm));

            return $paymentIntentConfirm;
        } catch (\Stripe\Exception\CardException $e) {
            ErrorLogger::logError($e);
            return false;
            // Since it's a decline, \Stripe\Exception\CardException will be caught
        } catch (\Stripe\Exception\RateLimitException $e) {
            ErrorLogger::logError($e);
            return false;
            // Too many requests made to the API too quickly
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ErrorLogger::logError($e);
            return false;
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            ErrorLogger::logError($e);
            return false;
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            ErrorLogger::logError($e);
            return false;
            // Network communication with Stripe failed
        } catch (\Stripe\Exception\ApiErrorException $e) {
            ErrorLogger::logError($e);
            return false;
            // Display a very generic error to the user, and maybe send
            // yourself an email
        } catch (Exception $e) {
            ErrorLogger::logError($e);
            return false;
            // Something else happened, completely unrelated to Stripe
        }
        $gateWayResponse = PaymentGatewayResponse::create(array('transaction_id' => $payment_transaction_id, 'response' => $e->getMessage()));
        return ['status' => 0, 'data' => $e->getMessage(), 'message' => 'Payment Failed'];
    }
}
