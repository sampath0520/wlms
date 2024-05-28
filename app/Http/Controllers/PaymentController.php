<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ResponseHelper;
use App\Services\PaymentService;
use App\Services\PaypalService;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Services\CustomerService;
use App\Services\CurrencyService;
use App\Services\GeneralService;
use log;
use Auth;

class PaymentController extends MainController
{
    //
    protected $paymentService;
    protected $paypalService;
    protected $generalService, $currencyService, $customerService;

    public function __construct(PaymentService $paymentService, PaypalService $paypalService, GeneralService $generalService, CurrencyService $currencyService, CustomerService $customerService)
    {
        $this->paymentService = $paymentService;
        $this->paypalService = $paypalService;
        $this->generalService = $generalService;
        $this->currencyService = $currencyService;
        $this->customerService = $customerService;
    }

    public function setupCurrency($request)
    {

        $currency = $request->currency ?? session()->get('currency');
        if ($currency == null) {
            $currency = 1;
        }
        return $this->generalService->setupCurrencyCode($currency);
    }
    public function checkout(Request $request)
    {

        self::setupCurrency($request);

        $currencyList = $this->currencyService->getActiveCurrencies();
        $cus_id = $this->customerService->getCustomerId(Auth::id());
        $customerData = $this->customerService->getCustomer($cus_id);
        $countries = $this->generalService->getCountryList();

        return view('website.pages.checkout', ['currencyList' => $currencyList, 'base_url' => AppConstants::WEBSITE_LINK, 'customerData' => $customerData, 'countries' => $countries]);
    }


    public function processTransaction(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('payment.success'),
                "cancel_url" => route('payment.cancel'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $request['currency'],
                        "value" =>  $request['total_value']
                    ]
                ]
            ]
        ]);
        if (isset($response['id']) && $response['id'] != null) {
            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
            return ['status' => 0, 'data' => '', 'message' => 'Payment Failed'];
        } else {
            return ['status' => 0, 'data' => '', 'message' => 'Payment Failed'];
        }
    }


    /**
     * Responds with a welcome message with instructions
     *
     * @return \Illuminate\Http\Response
     */
    public function successTransaction(Request $request)
    {


        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();


        // dd($request);

        $response = $provider->capturePaymentOrder($request['token']);
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return ['status' => 1, 'data' => '', 'message' => 'Payment Successful'];
        } else {
            return ['status' => 0, 'data' => '', 'message' => 'Payment Failed'];
        }
    }


    public function stripeCheckout(Request $request)
    {


        $paymentRequest = [
            'card_no' => $request->card_no,
            'exp_month' => $request->exp_month,
            'exp_year' => $request->exp_year,
            'cvc' => $request->cvv,
            'total_value' => $request->total_value,
            'currency' => $request->currency,
            'payment_transaction_id' => rand(1, 1000),
        ];


        // 'card_no' => ' 4242424242424242',
        // 'exp_month' => '12',
        // 'exp_year' => '2030',
        // 'cvc' => '123',
        // 'total_value' => '100',
        // 'currency' => 'GBP',

        $processPayment  = $this->paymentService->processStripePayment($paymentRequest);

        return $processPayment;
        //  print_r($processPayment) ;
    }

    public function loadOrderResponse(Request $request)
    {

        self::setupCurrency($request);

        $currencyList = $this->currencyService->getActiveCurrencies();
        $cus_id = $this->customerService->getCustomerId(Auth::id());
        $customerData = $this->customerService->getCustomer($cus_id);
        $countries = $this->generalService->getCountryList();

        return view('website.pages.orderResponse', ['currencyList' => $currencyList, 'base_url' => AppConstants::WEBSITE_LINK, 'customerData' => $customerData, 'countries' => $countries]);
    }
}
