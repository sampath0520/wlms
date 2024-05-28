<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Http\Requests\AddReviewRequest;
use App\Http\Requests\CheckReviewRequest;
use App\Http\Requests\ContentLockRequest;
use App\Http\Requests\CourseContentRequest;
use App\Http\Requests\CourseCreateRequest;
use App\Http\Requests\CourseStatusRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Requests\EnrollRequest;
use App\Http\Requests\UpdatesectionRequest;
use App\Http\Requests\UserAddToCourseRequest;
use App\Models\Course;
use App\Models\CourseRating;
use App\Services\CourseService;
use App\Services\PaymentDetailService;
use App\Services\PaymentService;
use App\Services\PromoCodeService;
use App\Services\WebinarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    protected $courseService;
    protected $webinarService;
    protected $paymentService;
    protected $paymentDetailService;
    protected $promoCodeService;
    public function __construct(CourseService $courseService, WebinarService $webinarService, PaymentService $paymentService, PaymentDetailService $paymentDetailService, PromoCodeService $promoCodeService)
    {
        // $this->middleware('auth');
        $this->courseService = $courseService;
        $this->webinarService = $webinarService;
        $this->paymentService = $paymentService;
        $this->paymentDetailService = $paymentDetailService;
        $this->promoCodeService = $promoCodeService;
    }

    /**
     * Fetch course list
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course list
     */

    public function fetchAllCourses()
    {
        $courses = $this->courseService->fetchAllCourses();
        if ($courses) {
            return ResponseHelper::success(trans('messages.record_fetched'), $courses);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * create Course
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * create Course
     */

    public function createCourse(CourseCreateRequest $request)
    {
        //validate request
        $validated = $request->validated();

        $courses = $this->courseService->createCourse($validated);
        if ($courses['status']) {
            return ResponseHelper::success($courses['message'], $courses['data']);
        } else {
            return ResponseHelper::error($courses['message']);
        }
    }

    /**
     * activate and deactivate course
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * activate and deactivate course
     */

    public function activateDeactivateCourse(CourseStatusRequest $request)
    {
        $validated = $request->validated();
        $courses = $this->courseService->activateDeactivateCourse($validated);

        if ($courses) {
            return ResponseHelper::success('Course status updated successfully');
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * Fetch course by id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course by id
     */

    public function fetchCourseById($id)
    {

        $course = $this->courseService->getCourseById($id);
        if ($course['status']) {
            return ResponseHelper::success(trans('messages.record_fetched'), $course['data']);
        } else {
            return ResponseHelper::error($course['message']);
        }
    }

    /**
     * update course by id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * update course by id
     */

    public function updateCourseById(CourseUpdateRequest $request)
    {
        $validated = $request->validated();

        $course = $this->courseService->updateCourseById($validated);
        if ($course['status']) {
            return ResponseHelper::success($course['message'], $course['data']);
        } else {
            return ResponseHelper::error($course['message']);
        }
    }

    /**
     * delete course by id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * delete course by id
     */

    public function deleteCourse($id)
    {
        $user = $this->courseService->getCourseById($id);
        if ($user['status']) {
            $adminDelete = $this->courseService->deleteCourse($id);
            if ($adminDelete) {
                return ResponseHelper::success('Course deleted successfully');
            } else {
                return ResponseHelper::error(trans('messages.delete_failed'));
            }
        } else {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
    }
    /**
     * create course content
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * create course content
     */

    public function createCourseContent(Request $request)
    {

        $course_content = $this->courseService->createCourseContent($request);
        if ($course_content['status']) {
            return ResponseHelper::success('Course content created successfully',  $course_content['data']);
        } else {
            return ResponseHelper::error($course_content['message']);
        }
    }

    /**
     * Fetch course details
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course details
     */

    public function detailFetch()
    {
        $courses = $this->courseService->detailFetch();
        if ($courses) {
            return ResponseHelper::success(trans('messages.record_fetched'), $courses);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }


    /**
     * add review to course
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * add review to course
     */

    public function addReview(AddReviewRequest $request)
    {
        $validated = $request->validated();

        $rating = $this->courseService->addReview($validated);

        if ($rating['status']) {
            return ResponseHelper::success('Review added successfully', $rating['data']);
        } else {
            return ResponseHelper::error($rating['message']);
        }
    }

    /**
     * Fetch course catalog
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course catalog
     */

    public function fetchCourseCatalog()
    {
        $course = $this->courseService->fetchCourseCatalog();

        if ($course['status']) {
            return ResponseHelper::success(trans('messages.record_fetched'), $course['data']);
        } else {
            return ResponseHelper::error($course['message']);
        }
    }
    #################################OLD CODE#############################################
    // //get logged user's course_id
    // $logged_user = Auth::user();
    // if (!$logged_user) {
    //     return ResponseHelper::error(trans('messages.data_not_found'));
    // }

    // $course_id = $logged_user->payment_details->pluck('course_id')->first();

    // if (!$course_id) {
    //     return ResponseHelper::error('Course not found');
    // }

    // $course = $this->courseService->getCourseById($course_id);
    // if (!$course['status']) {
    //     return ResponseHelper::error($course['message']);
    // }

    // //get course rating
    // $courseRating = CourseRating::where('course_id', $course_id)
    //     ->where('is_approved', AppConstants::ACTIVE)
    //     ->with('users')
    //     ->get();



    // //calculate average rating
    // $totalRating = 0;
    // $totalRatingCount = 0;
    // foreach ($courseRating as $rating) {
    //     $totalRating += $rating->rating;
    //     $totalRatingCount++;
    // }

    // $course['data']->averageRating = $totalRatingCount > 0 ? $totalRating / $totalRatingCount : 0;
    // $course['data']->totalRatingCount = $totalRatingCount;

    // if ($course['status']) {
    //     return ResponseHelper::success(trans('messages.record_fetched'), $course);
    // } else {
    //     return ResponseHelper::error(trans('messages.record_fetch_failed'));
    // }

    #################################OLD CODE#############################################


    /**
     * Fetch course details for logged user
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course details for logged user
     */

    public function detailFetchForLoggedUser($course_id)
    {
        $logged_user = Auth::user();

        //check course

        if (!$logged_user) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        // $course_id = $logged_user->payment_details->pluck('course_id')->toArray();

        $courses = $this->courseService->detailFetchForLoggedUser($course_id, $logged_user->id);
        if ($courses) {
            return ResponseHelper::success(trans('messages.record_fetched'), $courses);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Fetch course content videos for logged user
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course content videos for logged user
     */

    public function fetchCourseVideos($id)
    {
        $coursesVideos = $this->courseService->fetchCourseVideos($id);
        if ($coursesVideos) {
            return ResponseHelper::success(trans('messages.record_fetched'), $coursesVideos);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * markAsCompleted
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * markAsCompleted
     */

    public function markAsCompleted(Request $request)
    {
        $coursesVideos = $this->courseService->markAsCompleted($request);
        if ($coursesVideos['status']) {
            return ResponseHelper::success($coursesVideos['message'], $coursesVideos['data']);
        } else {
            return ResponseHelper::error($coursesVideos['message']);
        }
    }


    /**
     * Quizzes By Course Id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function quizzesByCourseId($course_id)
    {

        $course = $this->courseService->getCourseById($course_id);
        if (!$course['status']) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        $quiz_drop = $this->courseService->quizzesByCourseId($course_id);
        if ($quiz_drop) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quiz_drop);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Fetch course content
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course content
     */

    public function fetchCourseContent($course_id)
    {
        $course = $this->courseService->getCourseById($course_id);
        if (!$course['status']) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        $course_content = $this->courseService->fetchCourseContent($course_id);
        if ($course_content) {
            return ResponseHelper::success(trans('messages.record_fetched'), $course_content);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * updateCourseContent
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * updateCourseContent
     */

    public function updateCourseContent(Request $request)
    {
        $course_content = $this->courseService->updateCourseContent($request);
        if ($course_content['status']) {
            return ResponseHelper::success('Course content updated successfully', $course_content['data']);
        } else {
            return ResponseHelper::error($course_content['message']);
        }
    }

    /**
     * courseProgress
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * courseProgress
     */

    public function courseProgress()
    {
        $course_content = $this->courseService->courseProgress();
        if ($course_content) {
            return ResponseHelper::success(trans('messages.record_fetched'), $course_content);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchAllStatus()
    {
        $courses = $this->courseService->fetchAllStatus();
        if ($courses) {
            return ResponseHelper::success(trans('messages.record_fetched'), $courses);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch course content by week
     */

    public function fetchCourseContentByWeek($course_id)
    {
        $course = $this->courseService->getCourseById($course_id);
        if (!$course['status']) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        $course_content = $this->courseService->fetchCourseContentByWeek($course_id);
        if ($course_content['status']) {
            return ResponseHelper::success(trans('messages.record_fetched'), $course_content['data']);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Lock Course Content
     */
    public function lockCourseContent(ContentLockRequest $request)
    {
        $validated = $request->validated();
        $course_content = $this->courseService->lockCourseContent($validated);

        if ($course_content) {
            return ResponseHelper::success('Course content locked successfully');
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Delete Course Content
     */

    public function deleteCourseContent($id)
    {
        $course_content = $this->courseService->deleteCourseContent($id);
        if ($course_content) {
            return ResponseHelper::success('Course content deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function checkReview(CheckReviewRequest $request)
    {
        $validated = $request->validated();
        $review = $this->courseService->checkReview($validated);
        if ($review['status']) {
            return ResponseHelper::success(trans('messages.record_fetched'), $review['data']);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed', $review['message']));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function updateSection(UpdatesectionRequest $request)
    {
        $validated = $request->validated();
        $section = $this->courseService->updateSection($validated);
        if ($section) {
            return ResponseHelper::success('Section updated successfully', ['course_id' => $section]);
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * fetch all courses
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function fetchAllCoursesStudent()
    {
        $courses = $this->courseService->fetchAllCoursesForStudent();
        if ($courses) {
            return ResponseHelper::success(trans('messages.record_fetched'), $courses);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * Enroll And Payment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function enrollAndPayment(EnrollRequest $request)
    {
        try {
            //transaction start
            DB::beginTransaction();

            //get logged user id
            $user = Auth::user();

            //check user already enrolled
            $checkEnroll = $this->courseService->checkEnroll($request->course_id);

            if ($checkEnroll['status']) {
                return ResponseHelper::error($checkEnroll['message']);
            }

            $validated = $request->validated();



            // //get course price
            // $course =  Course::where('id', $validated['course_id'])->first();

            if ($validated['is_free'] == 1) {
                $payment_type =  AppConstants::FREE_COURSE;
                $transaction_id = 0;
                $processPayment['status'] = 1;
                $total_value = 0;
                $currency = AppConstants::CANADIAN_DOLLAR;
            } else {
                $course = $this->courseService->getCourseByIdAndCurrency($validated['course_id'], $validated['currency_id'] ?? 0);
                if (!$course['status']) {
                    return ResponseHelper::error($course['message']);
                }
                $course = $course['data'];
                $course_price = $request->type == 1 ? $course['price'] : $course['other_price'];

                if (isset($validated['promo_code']) && $validated['promo_code'] != null) {
                    //check promo code is used before
                    $checkPromoCode = $this->promoCodeService->checkPromoCodeIsUsed($user->id, $validated['promo_code'], $validated['currency_id']);

                    if (!$checkPromoCode['status']) {
                        return ResponseHelper::error($checkPromoCode['message']);
                    }
                    $promo_code_id = $checkPromoCode['promocode_id'];
                    //get promo code discount
                    if ($checkPromoCode['discount_type'] == AppConstants::PERCENTAGE) {

                        $discount =  $course_price * $checkPromoCode['discount'] / 100;
                        $total_value =  $course_price - $discount;
                        //if discount is greater than course price

                    } else {
                        $total_value =  $course_price - $checkPromoCode['discount'];
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
                    $paymentRequest = [
                        'card_no' => $validated['card_no'],
                        'exp_month' => $validated['exp_month'],
                        'exp_year' => $validated['exp_year'],
                        'cvc' => $validated['cvc'],
                        'total_value' => $total_value,
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
            }

            $paymentDetails = [
                'user_id' => $user->id,
                'course_id' => $validated['course_id'],
                'payment_trans_id' => $transaction_id ?? 0,
                'price' => $total_value,
                'is_manual_payment' => $payment_type,
                'course_currency_id' => $request->currency_id ?? 1,
                'promo_code_id' => $promo_code_id ?? 0,
            ];
            $paymentDetail = $this->paymentDetailService->addPaymentDetails($paymentDetails);

            $forLog = [
                'course_id' => $request->course_id,
                'currency_id' =>  $request->currency_id ?? 1,
                'price' => $total_value,
                'promo_code_id' => $promo_code_id ?? 0,
                'user_id' => $user->id,
                'promo_discount_type' => $checkPromoCode['dicount_type'] ?? 0,
                'promo_discount' => $checkPromoCode['discount'] ?? 0,
                'currency' => $currency,
                'is_one_time_promo' => $checkPromoCode['is_one_time'] ?? 0,
                'promo_code' => $checkPromoCode['promo_code'] ?? 0,
            ];
            //add to payment log
            $paymentLog = $this->paymentDetailService->addToPaymentLog($forLog);

            if (
                !$processPayment || $processPayment['status'] != 1 ||
                !$paymentDetail
                //  ||!$courseContent
            ) {

                DB::rollBack();
                return ResponseHelper::error(trans('messages.course_enrollment_failed'));
            }
            //transaction commit
            DB::commit();
            return ResponseHelper::success(trans('messages.course_enrollment_success'));
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            DB::rollBack();
            // Return an error response to the user
            return ResponseHelper::error(trans('messages.course_enrollment_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * get Signed Url
     */

    public function getSignedUrl($id)
    {
        $signedUrl = $this->courseService->getSignedUrl($id);
        if ($signedUrl['status']) {
            return ResponseHelper::success($signedUrl['message'], $signedUrl['data']);
        } else {
            return ResponseHelper::error('Signed Url fetch failed');
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * getVideoUrl
     */

    public function getVideoUrl($token)
    {
        $videoUrl = $this->courseService->getVideoUrl($token);
        if ($videoUrl['status']) {
            return ResponseHelper::success($videoUrl['message'], $videoUrl['data']);
        } else {
            return ResponseHelper::error('Video Url fetch failed');
        }
    }

    //notRegisteredForCourse
    public function notRegisteredForCourse($id)
    {

        $users = $this->courseService->notRegisteredForCourse($id);

        if ($users['status']) {
            return ResponseHelper::success($users['message'], $users['data']);
        } else {
            return ResponseHelper::error($users['message']);
        }
    }

    //user add to course
    public function addToCourse(UserAddToCourseRequest $request)
    {
        $validated = $request->validated();
        // $course = $this->courseService->getCourseById($validated['course_id']);

        $course = $this->courseService->getCourseByIdAndCurrency($validated['course_id'], $validated['currency_id']);
        if (!$course['status']) {
            return ResponseHelper::error($course['message']);
        }

        $add = $this->courseService->userAddToCourse($validated, $course['data']);
        if ($add['status']) {
            return ResponseHelper::success($add['message']);
        } else {
            return ResponseHelper::error($add['message']);
        }
    }
}
