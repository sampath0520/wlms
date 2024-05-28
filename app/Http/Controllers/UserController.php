<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\DiscountPriceRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ReportRequest;
use App\Http\Requests\StudentCreateRequest;
use App\Http\Requests\UserAddToCourseRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserFetchRequest;
use App\Http\Requests\UserStatusRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Mail\UserPasswordEmail;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\UserService;
use App\Services\CourseService;
use App\Services\PaymentDetailService;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{

    protected $paymentService;
    protected $userService;
    protected $courseService;
    protected $paymentDetailService;
    protected $promoCodeService;

    public function __construct(UserService $userService, PaymentService $paymentService, CourseService $courseService, PaymentDetailService $paymentDetailService, PromoCodeService $promoCodeService)
    {
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->courseService = $courseService;
        $this->paymentDetailService = $paymentDetailService;
        $this->promoCodeService = $promoCodeService;


        //check authentication except for login and register
        $this->middleware('auth:api', ['except' => ['login', 'register', 'resetDevice']]);
    }


    /**
     * Process the payment and register the user
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * register a new user
     */

    public function register(RegistrationRequest $request)
    {

        $validated = $request->validated();

        try {
            //transaction begin
            DB::beginTransaction();
            $userRequest = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
                'course_id' => $request->course_id,
                'device_id' => $request->device_id,
            ];

            if ($request->is_free == 0) {
                //course service
                // $course = $this->courseService->getCourseById($request->course_id);
                $course = $this->courseService->getCourseByIdAndCurrency($request->course_id, $request->currency_id);

                if (!$course['status']) {
                    return ResponseHelper::error($course['message']);
                }
                $course = $course['data'];
                $course_price = $request->type == 1 ? $course['price'] : $course['other_price'];
                //check promo code exist
                if (isset($request->promo_code)) {
                    $promoCode = $this->promoCodeService->checkPromoCode($request->promo_code, $request->course_id, $request->currency_id);

                    if (!$promoCode) {
                        return ResponseHelper::error('Invalid Promo Code');
                    }
                    $promo_code_id = $promoCode['promocode_id'];
                    if ($promoCode['dicount_type'] == AppConstants::PERCENTAGE) {

                        $discount =  $course_price * $promoCode['discount'] / 100;
                        $total_value =  $course_price - $discount;
                    } else {
                        $total_value =  $course_price - $promoCode['discount'];
                    }
                } else {
                    $total_value = $course_price;
                }
                if ($total_value <= 0) {
                    $total_value = 0;
                    $payment_type =  AppConstants::MANUAL_PAYMENT;
                    $currency = $course['currency']['currency'];
                    $processPayment['status'] = 1;
                }

                if (!($total_value <= 0)) {

                    $currency = $course['currency']['currency'];
                    //promo code check, whether it is used before or not

                    $paymentRequest = [
                        'card_no' => $request->card_no,
                        'exp_month' => $request->exp_month,
                        'exp_year' => $request->exp_year,
                        'cvc' => $request->cvv,
                        'total_value' => $total_value > 0 ? $total_value : 0,
                        // 'currency' => AppConstants::CANADIAN_DOLLAR,
                        'currency' => $currency,
                        'payment_transaction_id' => rand(1, 1000),
                    ];

                    $processPayment  = $this->paymentService->processStripePayment($paymentRequest);
                    if (!$processPayment || $processPayment['status'] != 1) {
                        return ResponseHelper::error(trans('messages.payment_failed'));
                    }
                    $transaction_id = $paymentRequest['payment_transaction_id'];
                    $payment_type = AppConstants::CARD_PAYMENT;
                }
            } else {
                $total_value = 0;
                $transaction_id = 0;
                $payment_type = AppConstants::FREE_COURSE;
                $processPayment['status'] = 1;
                $currency = AppConstants::CANADIAN_DOLLAR;
            }

            $userRegistration = $this->userService->createUser($userRequest, AppConstants::STUDENT_ROLE_ID);

            $paymentDetails = [
                'user_id' => $userRegistration['id'],
                'course_id' => $request->course_id,
                'payment_trans_id' => $transaction_id,
                'price' => $total_value,
                'is_manual_payment' => $payment_type,
                'course_currency_id' => $request->currency_id ?? 1,
                'promo_code_id' => $promo_code_id ?? 0,
            ];

            $paymentDetail = $this->paymentDetailService->addPaymentDetails($paymentDetails);

            $forLog = [
                'course_id' => $request->course_id,
                'currency_id' => $request->currency_id ?? 1,
                'price' => $total_value,
                'promo_code_id' => $promo_code_id ?? 0,
                'user_id' => $userRegistration['id'],
                'promo_discount_type' => $promoCode['discount_type'] ?? 0,
                'promo_discount' => $promoCode['discount'] ?? 0,
                'currency' => $currency,
                'is_one_time_promo' => $promoCode['is_one_time'] ?? 0,
                'promo_code' => $promoCode['promo_code'] ?? 0,
            ];

            //add to payment log
            $paymentLog = $this->paymentDetailService->addToPaymentLog($forLog);

            // If any of the services return false, rollback the transaction
            if (
                !$processPayment || $processPayment['status'] != 1 ||
                !$userRegistration ||
                !$paymentDetail
                //  ||!$courseContent
            ) {
                DB::rollBack();
                return ResponseHelper::error(trans('messages.user_registration_failed'));
            }

            //transaction commit
            DB::commit();
            return ResponseHelper::success(trans('messages.user_registration_success'));
        } catch (\Exception $e) {
            dd($e);
            ErrorLogger::logError($e);
            DB::rollBack();
            // Return an error response to the user
            return ResponseHelper::error(trans('messages.user_registration_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * create a new user
     */

    public function createUser(UserCreateRequest $request)
    {

        $validated = $request->validated();
        $password = Str::random(8);

        $adminRequest = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
            'password' => $password,
        ];

        //transaction begin
        DB::beginTransaction();
        try {
            $adminCreate = $this->userService->createAdminUser($adminRequest);

            // Send the OTP email
            $email = Mail::to($request->email)->send(new UserPasswordEmail($password));
            //transaction commit
            DB::commit();
            return ResponseHelper::success(trans('messages.password_sent'));
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            DB::rollBack();
            // Return an error response to the user
            return ResponseHelper::error(trans('messages.user_registration_failed'));
        }
    }



    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetch all admin users
     */

    public function fetchAllUsers(UserFetchRequest $request)
    {
        $request->validated();
        $userFetch = [
            'course_id' => $request->course,
            'search_text' => $request->search_text,
            'type' => $request->type,
        ];

        $users = $this->userService->fetchUsers($userFetch);
        if ($users) {
            return ResponseHelper::success(trans('messages.record_fetch_success'), $users);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * activate_deactivate a user
     */

    public function activateDeactivate(UserStatusRequest $request)
    {
        $validated = $request->validated();
        $userStatus = [
            'user_id' => $request->user_id,
            'status' => $request->status,
        ];

        $status = $this->userService->activate_deactivate($userStatus);
        if ($status) {
            return ResponseHelper::success('User status updated successfully');
        } else {
            return ResponseHelper::error('User status update failed');
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetch a user by id
     */

    public function fetchUserById($user_id)
    {
        $user = $this->userService->fetchUserById($user_id);
        if ($user) {
            return ResponseHelper::success(trans('messages.record_fetched'), $user);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * update a user by id
     */

    public function updateUserById(UserUpdateRequest $request)
    {
        $validated = $request->validated();

        $adminUpdate = $this->userService->updateAdminUser($validated);
        if ($adminUpdate) {
            return ResponseHelper::success('User updated successfully');
        } else {
            return ResponseHelper::error('User update failed');
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * delete a user by id
     */

    public function deleteUser($user_id)
    {
        $user = $this->userService->fetchUserById($user_id);
        if ($user) {
            $adminDelete = $this->userService->deleteUser($user_id);
            if ($adminDelete['status']) {
                return ResponseHelper::success(trans('messages.delete_success'));
            } else {
                return ResponseHelper::error($adminDelete['message']);
            }
        } else {
            return ResponseHelper::error(trans('messages.user_not_found'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * create a student
     */

    // public function createStudent(StudentCreateRequest $request)
    // {

    //     $validated = $request->validated();

    //     try {
    //         //transaction begin
    //         DB::beginTransaction();
    //         $password = Str::random(8);
    //         $userRequest = [
    //             'first_name' => $validated['first_name'],
    //             'last_name' => $validated['last_name'],
    //             'email' => $validated['email'],
    //             'password' =>  $password,
    //             'course_id' => $validated['course_id'],
    //         ];

    //         $userRegistration = $this->userService->createUser($userRequest, AppConstants::ADMIN_ROLE_ID);

    //         // $course = $this->courseService->getCourseById($validated['course_id']);
    //         $course = $this->courseService->getCourseByIdAndCurrency($validated['course_id'], $validated['currency_id']);

    //         if (!$course['status']) {
    //             return ResponseHelper::error($course['message']);
    //         }

    //         $course_currency = $course['data']['courseCurrencies'][0];
    //         $paymentDetails = [
    //             'user_id' => $userRegistration['id'],
    //             'course_id' => $validated['course_id'],
    //             'price' => $course_currency['price'],
    //             'is_manual_payment' => AppConstants::MANUAL_PAYMENT,
    //             'payment_trans_id' => 0,
    //             'course_currency_id' => $course_currency['currency_id'],
    //             'promo_code_id' => 0,
    //         ];

    //         $paymentDetail = $this->paymentDetailService->addPaymentDetails($paymentDetails);

    //         // $courseContent = $this->courseService->insertStudentsCourseContent($validated['course_id'], $userRegistration['id']);

    //         // Send the OTP email
    //         $email = Mail::to($request->email)->send(new UserPasswordEmail($password));
    //         //transaction commit
    //         DB::commit();
    //         return ResponseHelper::success(trans('messages.password_sent'));
    //     } catch (\Exception $e) {
    //         ErrorLogger::logError($e);
    //         DB::rollBack();
    //         // Return an error response to the user
    //         return ResponseHelper::error(trans('messages.user_registration_failed'));
    //     }
    // }
    public function createStudent(StudentCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            // Start transaction
            DB::beginTransaction();
            $user = auth()->user()->id;
            $password = Str::random(8);
            $userRequest = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' =>  $password,
                'course_id' => $validated['course_id'],
            ];

            $userRegistration = $this->userService->createUser($userRequest, AppConstants::ADMIN_ROLE_ID);

            $course = $this->courseService->getCourseByIdAndCurrency($validated['course_id'], $validated['currency_id']);

            if (!$course['status']) {
                DB::rollBack(); // Rollback transaction if course retrieval fails
                return ResponseHelper::error($course['message']);
            }

            $course_currency = $course['data']['courseCurrencies'][0];
            $paymentDetails = [
                'user_id' => $userRegistration['id'],
                'course_id' => $validated['course_id'],
                'price' => $course_currency['price'],
                'is_manual_payment' => AppConstants::MANUAL_PAYMENT,
                'payment_trans_id' => 0,
                'course_currency_id' => $course_currency['currency_id'],
                'promo_code_id' => 0,
            ];

            $paymentDetail = $this->paymentDetailService->addPaymentDetails($paymentDetails);

            DB::commit(); // Commit transaction after successful user creation and payment details insertion

            // Send the OTP email outside of the transaction block
            try {
                $email = Mail::to($request->email)->send(new UserPasswordEmail($password));
            } catch (\Exception $e) {
                // Log email sending error
                ErrorLogger::logError($e);
            }

            $userModel = User::find($userRegistration['id']);
            activity()
                ->causedBy($user)
                ->performedOn($userModel)
                ->withProperties(['password' => $password, 'email' => $validated['email']])
                ->log('student_create_success');
            return ResponseHelper::success(trans('messages.password_sent'));
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            DB::rollBack(); // Rollback transaction if an exception occurs
            // Return an error response to the user
            return ResponseHelper::error(trans('messages.user_registration_failed'));
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetch latest registrations for the week
     */

    public function fetchLatestRegistrations()
    {
        $userRegistration = $this->userService->fetchLatestStudents();
        if ($userRegistration) {
            return ResponseHelper::success(trans('messages.record_fetched'), $userRegistration);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * User's dropdown according to user type
     */

    public function usersDropdown($type)
    {
        $users = $this->userService->usersDropdown($type);
        if ($users) {
            return ResponseHelper::success(trans('messages.record_fetched'), $users);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * change password
     */

    public function change_password(ChangePasswordRequest $request)
    {
        $validated = $request->validated();
        $changePass = $this->userService->changePassword($validated);
        if ($changePass) {
            return ResponseHelper::success(trans('messages.record_updated'));
        } else {
            return ResponseHelper::error(trans('messages.password_change_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * student Report
     */
    public function studentReport(ReportRequest $request)
    {
        $validated = $request->validated();
        $students = $this->userService->studentReport($validated);
        if ($students) {
            return ResponseHelper::success(trans('messages.record_fetched'), $students);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * payment Report
     */

    public function paymentReport(ReportRequest $request)
    {
        $validated = $request->validated();
        $payments = $this->userService->paymentReport($validated);
        if ($payments) {
            return ResponseHelper::success(trans('messages.record_fetched'), $payments);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * student Report export
     */

    public function studentReportExport(ReportRequest $request)
    {
        $validated = $request->validated();
        $payments = $this->userService->studentReportExcel($validated);
        if ($payments) {
            return $payments;
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * payment Report export
     */

    public function paymentReportExport(ReportRequest $request)
    {
        $validated = $request->validated();
        $payments = $this->userService->paymentReportExcel($validated);
        if ($payments) {
            return $payments;
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * User Details
     */

    public function userDetails()
    {
        $userDetails = $this->userService->userDetails();
        if ($userDetails) {
            return ResponseHelper::success(trans('messages.record_fetched'), $userDetails);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    //courseDisable
    public function courseDisable(Request $request)
    {
        $disable = $this->userService->courseDisable($request->all());
        if ($disable['status']) {
            return ResponseHelper::success($disable['message']);
        } else {
            return ResponseHelper::error($disable['message']);
        }
    }

    //fetchAllStudents
    public function fetchAllStudents(UserFetchRequest $request)
    {
        $request->validated();
        $userFetch = [
            'course_id' => $request->course,
            'search_text' => $request->search_text,
            'type' => $request->type,
        ];

        $users = $this->userService->fetchAllStudents($userFetch);
        if ($users['status']) {
            return ResponseHelper::success($users['message'], $users['data']);
        } else {
            return ResponseHelper::error($users['message']);
        }
    }

    //discountPrice
    public function discountPrice(DiscountPriceRequest $request)
    {

        try {
            $validated = $request->validated();

            $course = $this->courseService->getCourseByIdAndCurrency($validated['course_id'], $validated['currency_id']);

            if (!$course['status']) {
                return ResponseHelper::error($course['message']);
            }
            $course = $course['data'];

            $course_price = $validated['course_type'] == 1 ? $course['price'] : $course['other_price'];

            //check promo code exist
            if (isset($validated['promo_code'])) {

                //if user logged in get user_id
                if (auth()->user()) {
                    $user_id = auth()->user()->id;

                    //check promo code is used before
                    $promoCode = $this->promoCodeService->checkPromoCodeIsUsed($user_id, $validated['promo_code'], $validated['currency_id']);

                    if (!$promoCode['status']) {
                        return ResponseHelper::error($promoCode['message']);
                    }
                } else {

                    $promoCode = $this->promoCodeService->checkPromoCode($validated['promo_code'], $validated['course_id'], $validated['currency_id']);
                }

                if (!$promoCode) {
                    return ResponseHelper::error('Invalid Promo Code');
                }

                if ($promoCode->discount_type == AppConstants::PERCENTAGE) {
                    $discount =  $course_price * $promoCode['discount'] / 100;
                    $total_value =  $course_price - $discount;
                } else {
                    $total_value =  $course_price - $promoCode['discount'];
                }
            } else {
                $total_value = $course_price;
            }

            $final_value = $total_value > 0 ? $total_value : 0;
            $final_value = number_format((float)$final_value, 2, '.', '');

            return ResponseHelper::success('Discount fetched successfully', ['discount_price' => $final_value]);
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ResponseHelper::error('Discount fetch failed');
        }
    }

    //resetDevice
    public function resetDevice($email)
    {
        $reset = $this->userService->resetDevice($email);
        if ($reset['status']) {
            return ResponseHelper::success($reset['message']);
        } else {
            return ResponseHelper::error($reset['message']);
        }
    }
}
