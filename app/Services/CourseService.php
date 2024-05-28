<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\CourseRating;
use App\Models\CoursesCurrency;
use App\Models\Currency;
use App\Models\PaymentDetail;
use App\Models\Quiz;
use App\Models\QuizStatus;
use App\Models\StudentsCourseStatus;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccess;
use App\Models\Webinar;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch course by id
     */
    public function getCourseById($course_id)
    {
        try {

            $course = Course::with('courseCurrencies', 'courseCurrencies.currency')
                ->where('id', $course_id)
                ->first();

            if (!$course) {
                return ['status' => false, 'message' => 'Course not found for id ' . $course_id];
            }
            //get total students for $course_id
            $totalStudents = PaymentDetail::where('course_id', $course_id)
                ->whereHas('user', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })->count();

            $course->currency = AppConstants::CANADIAN_DOLLAR;
            $course->total_enrolled = $totalStudents;

            return ['status' => true, 'message' => 'Course found', 'data' => $course];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => trans('messages.record_fetch_failed')];
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch course by id
     */
    public function getCourseByIdAndCurrency($course_id, $currency_id)
    {
        try {

            //get courses where course_id and currency_id
            //with course currencies, and cuccrencies
            $course = Course::where('id', $course_id)
                ->with(['courseCurrencies' => function ($query) use ($currency_id) {
                    $query->where('currency_id', $currency_id);
                }])
                ->first();

            if (!$course) {
                return ['status' => false, 'message' => 'Course/currency not found for id ' . $course_id];
            }

            // Access the first courseCurrencies directly
            $CourseCurrency = $course->courseCurrencies->first();

            if (!$CourseCurrency) {
                return ['status' => false, 'message' => 'currency not found for id ' . $course_id];
            }
            //get total students for $course_id
            $totalStudents = PaymentDetail::where('course_id', $course_id)
                ->whereHas('user', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })->count();
            // $course->currency = AppConstants::CANADIAN_DOLLAR;

            $course->currency = $this->getCurrencyById($CourseCurrency['currency_id']);
            $course->total_enrolled = $totalStudents;
            $course->price = $CourseCurrency['price'];
            $course->other_price = $CourseCurrency['other_price'];


            return ['status' => true, 'message' => 'Course fetch successfully found', 'data' => $course];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => trans('messages.record_fetch_failed')];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch courses
     */
    public function fetchAllCourses()
    {
        try {
            $courses = Course::where('is_active', AppConstants::ACTIVE)->get();
            return $courses;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create courses
     */

    public function createCourse($data)
    {
        try {

            DB::beginTransaction();
            $currency_ids = $data['currency_id'];
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/course', 'public');
            } else {
                $imagePath = null;
            }

            $course = Course::create([
                'name' => $data['name'],
                // 'price' => $data['price'],
                'price' => 0,
                'duration' => $data['duration'],
                'lecturer' => $data['lecturer'],
                'description' => $data['description'] ?? null,
                'course_image' => $imagePath,
                'is_active' => AppConstants::ACTIVE,
                'is_free' => $data['is_free'],
                'is_invisible' => $data['is_invisible'],
                // 'is_top_banner' => $data['is_top_banner'],
            ]);
            $course->save();

            //add to courses currencies
            foreach ($currency_ids as $key => $currency_id) {

                $currencies = CoursesCurrency::create([
                    'course_id' => $course->id,
                    'currency_id' => $currency_id,
                    'price' => $data['price'][$key],
                    'other_price' => $data['other_price'][$key],
                    // 'other_price' => 0,
                    'is_default' => $data['is_default'][$key],
                ]);
            }
            DB::commit();
            return ['status' => true, 'message' => 'Course created successfully', 'data' => $course];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['status' => false, 'message' => 'Currency cannot be duplicate for same course'];
            }
            DB::rollBack();
            dd($e->getMessage());
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course creation failed'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Activate deactivate courses
     */

    public function activateDeactivateCourse($data)
    {
        try {
            $course = Course::find($data['course_id']);
            $course->is_active = $data['status'];
            $course->save();
            return $course;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Update courses
     */

    public function updateCourseById($data)
    {
        try {
            $currency_ids = $data['currency_id'];
            $course = Course::find($data['id']);
            $course->name = $data['name'];
            // $course->price = $data['price'];
            $course->duration = $data['duration'];
            $course->lecturer = $data['lecturer'];
            $course->description = $data['description'] ?? null;
            $course->is_free = $data['is_free'];
            $course->is_invisible = $data['is_invisible'];
            // $course->is_top_banner = $data['is_top_banner'];
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/course', 'public');
                $course->course_image = $imagePath;
            }
            $course->save();

            //update courses currencies
            foreach ($currency_ids as $key => $currency_id) {
                //check if currency exists for payment detail
                // $paymentDetail = PaymentDetail::where('course_currency_id', $currency_id)
                //     ->first();

                //delete all course currencies if $data['is_free'] == 1
                if ($data['is_free'] == 1) {
                    CoursesCurrency::where('course_id', $course->id)->delete();
                    $currencies = CoursesCurrency::create([
                        'course_id' => $course->id,
                        'currency_id' => 1,
                        'price' => 0,
                        'other_price' => 0,
                        'is_default' => 1,
                    ]);
                } else {

                    //check if currency exists for CoursesCurrency
                    $courseCurrency = CoursesCurrency::where('currency_id', $currency_id)
                        ->where('course_id', $course->id)
                        ->first();

                    //if $courseCurrency update
                    //else add new

                    if ($courseCurrency) {
                        $courseCurrency->price = $data['price'][$key];
                        $courseCurrency->other_price = $data['other_price'][$key];
                        $courseCurrency->is_default = $data['is_default'][$key];
                        $courseCurrency->save();
                    } else {
                        $currencies = CoursesCurrency::create([
                            'course_id' => $course->id,
                            'currency_id' => $currency_id,
                            'price' => $data['price'][$key],
                            'other_price' => $data['other_price'][$key],
                            'is_default' => $data['is_default'][$key],
                        ]);
                    }
                }
            }

            return ['status' => true, 'message' => 'Course updated successfully', 'data' => $course];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course update failed'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Delete courses
     */

    public function deleteCourse($course_id)
    {
        try {
            $course = Course::find($course_id);
            $course->delete();
            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create course content
     */

    public function createCourseContent($request)
    {

        try {

            //begin transaction
            DB::beginTransaction();

            $dataArray = $request->all();
            if (!is_array($dataArray)) {
                return ['status' => false, 'message' => 'Invalid data'];
            }

            $course_id = $dataArray['course_id'];

            foreach ($dataArray['weeks'] as $data) {

                $week = $data['week'];

                //check course content already exists
                $course = $this->getCourseById($course_id);
                if (!$course['status']) {
                    return ['status' => false, 'message' => 'Course not found for id ' . $course_id];
                }

                foreach ($data['details'] as $details) {


                    //check $details['duration'] > 0 and must be without decimal
                    if ($details['duration'] <= 0 || strpos($details['duration'], '.') !== false) {
                        return ['status' => false, 'message' => 'Duration must be greater than 0 and without decimal'];
                    }

                    //$details['link'] must be link
                    if ($details['content_type'] == 1) {
                        if (!filter_var($details['link'], FILTER_VALIDATE_URL)) {
                            return ['status' => false, 'message' => 'Link must be valid url'];
                        }
                    }
                    if ($details['content_type'] == 2) {
                        $quiz = Quiz::find($details['link']);
                        if (!$quiz) {
                            return ['status' => false, 'message' => 'Quiz not found.'];
                        }
                    }

                    // //check week unique for course
                    // $existingWeek = CourseContent::where([
                    //     'course_id' => $course_id,
                    //     'week' => $week,
                    // ])->first();


                    // if (!is_null($existingWeek)) {

                    //     return ['status' => false, 'message' => 'Week already exists for course id ' . $course_id];
                    // }


                    // $existingContent = CourseContent::where([
                    //     'course_id' => $course_id,
                    //     'content' => $details['content'],
                    // ])->first();

                    // if (!$existingContent) {

                    $courseContent = CourseContent::create([
                        'content_type' => $details['content_type'], // 'video - 1' or 'quiz - 2'
                        'course_id' => $course_id,
                        'week' => $week,
                        'content' => $details['content'],
                        'content_link' => $details['link'],
                        'duration' => $details['duration'],
                        'day' => $details['day'],
                        'is_locked' => $details['is_locked'], // '1 - locked' or '0 - unlocked
                        'status' => AppConstants::ACTIVE,
                    ]);

                    $courseContent->save();
                    // } else {
                    //     return ['status' => false, 'message' => 'Course content already exists for course id ' . $course_id];
                    // }
                }
            }
            //commit transaction
            DB::commit();
            return ['status' => true, 'message' => 'Course content created successfully', 'data' => ['course_id' => $course_id]];
        } catch (\Exception $e) {

            DB::rollback();
            //if duplicate entry
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['status' => false, 'message' => 'Course content/week already exists'];
            }
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course content creation failed'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch course content by course id
     */

    public function fetchCourseContentByCourseId($course_id)
    {
        try {
            $courseContent = CourseContent::where('course_id', $course_id)->get();
            return $courseContent;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * detail Fetch
     */

    public function detailFetch()
    {
        try {
            //get courses with course rating and course contents
            $courses = Course::where('is_active', AppConstants::ACTIVE)
                ->where('is_invisible', AppConstants::INACTIVE)
                ->with([
                    'courseCurrencies.currency',
                    'course_contents' => function ($query) {
                        //select all
                        $query->select('*')
                            ->where('status', AppConstants::ACTIVE)
                            ->orderByRaw("CAST(SUBSTRING_INDEX(week, ' ', -1) AS UNSIGNED)")
                            ->orderBy('week', 'asc')
                            ->orderBy('day', 'asc')
                            ->orderBy('content_type', 'asc');
                    },
                    'courseRating' => function ($query) {
                        $query->where('is_approved', AppConstants::ACTIVE);
                    },
                ])
                ->get();

            //calculate average rating
            foreach ($courses as $course) {
                foreach ($course->course_contents as $content) {
                    //get link url id and get thumbnail from Video model
                    $content->thumbnail = null;
                    $videoId = explode('=', $content->content_link)[1] ?? null;

                    // Parse the URL
                    $path = parse_url($content->content_link, PHP_URL_PATH);

                    // Get the filename from the path
                    $filename = basename($path);

                    //set this filename to content
                    $content->aws_file_name = $filename;

                    if (isset($videoId)) {
                        $video = Video::find($videoId);
                        if (!$video) {
                            $content->thumbnail = null;
                        } else {
                            $content->thumbnail = $video->thumbnail;
                        }
                    }
                }

                $totalRating = 0;
                $totalRatingCount = 0;
                foreach ($course->courseRating as $rating) {
                    $totalRating += $rating->rating;
                    $totalRatingCount++;
                    //remove course rating from course object
                    unset($course->courseRating);
                }

                $course->averageRating = $totalRatingCount > 0 ? $totalRating / $totalRatingCount : 0;
                $course->totalRatingCount = $totalRatingCount;
                $course->currency = AppConstants::CANADIAN_DOLLAR;
            }


            //separate is_top_banner == 1 and other courses to 2 arrays
            $topBannerCourses = [];
            $otherCourses = [];
            foreach ($courses as $course) {
                if ($course->is_top_banner == 1) {
                    $topBannerCourses[] = $course;
                } else {
                    $otherCourses[] = $course;
                }
            }

            return ['topBannerCourses' => $topBannerCourses, 'otherCourses' => $otherCourses];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Add review for the course
     */
    public function addReview($data)
    {
        try {

            $review = CourseRating::create([
                'course_id' => $data['course_id'],
                'user_id' => auth()->user()->id,
                'rating' => $data['rating'],
                'feedback' => $data['feedback'] ?? null,
                'is_approved' => AppConstants::INACTIVE,
            ]);
            $review->save();

            return ['status' => true, 'message' => 'Review added successfully', 'data' => $review];
        } catch (\Exception $e) {
            //check if review already exists
            ErrorLogger::logError($e);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['status' => false, 'message' => 'Review already exists'];
            }

            return false;
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * detailFetchForLoggedUser
     */

    public function detailFetchForLoggedUser($course_id, $user_id)
    {
        try {
            //get total students for $course_id
            $totalStudents = PaymentDetail::where('course_id', $course_id)
                ->whereHas('user', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })->count();

            //get course id's $user registered from payment_details
            // $registeredCourses = PaymentDetail::where('user_id', $user_id)->pluck('course_id')->toArray();
            $userCourses = PaymentDetail::where('user_id', $user_id)
                ->where('status', AppConstants::ACTIVE)->get();
            $registeredCourses = $userCourses->pluck('course_id')->toArray();


            //get active latest webinar for $course_id
            $currentDateTime = Carbon::now();

            $activeWebinar = Webinar::where('course_id', $course_id)
                ->where('status', AppConstants::ACTIVE)
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('date', '>', $currentDateTime->toDateString())
                        ->orWhere(function ($query) use ($currentDateTime) {
                            $query->where('date', $currentDateTime->toDateString())
                                ->whereRaw("CONCAT(date, ' ', time, ' ', time_ext) >= ?", [$currentDateTime->toDateTimeString()]);
                        });
                })
                ->orderBy('date')
                ->orderBy('time')
                ->orderBy('time_ext')
                ->first();

            $courses = Course::where('is_active', AppConstants::ACTIVE)
                ->with([
                    // 'course_contents',
                    'course_contents' => function ($query) {
                        $query->select('*')
                            ->where('status', AppConstants::ACTIVE)
                            ->orderByRaw("CAST(SUBSTRING_INDEX(week, ' ', -1) AS UNSIGNED)")
                            ->orderBy('week', 'asc')
                            ->orderBy('day', 'asc')
                            ->orderBy('content_type', 'asc');
                    },
                    'course_contents.studentsCourseStatus' => function ($query) use ($user_id) {
                        $query->where('user_id', $user_id);
                    },
                    'courseRating' => function ($query) {
                        $query->where('is_approved', AppConstants::ACTIVE);
                    },
                    'courseRating.users' => function ($query) {
                        $query->select('id', 'first_name', 'last_name', 'profile_image')
                            ->where('is_active', AppConstants::ACTIVE);
                    },
                ])
                ->where('id', $course_id)
                ->get();

            // $courses->each(function ($course) {
            //     $course->course_contents->each(function ($content) {
            //         $content->file_name = basename(parse_url($content->content_link, PHP_URL_PATH));
            //     });
            // });

            foreach ($courses as $course) {
                foreach ($course->course_contents as $key => $content) {
                    if ($content->content_type == 1) {
                        $content->aws_file_name = basename(parse_url($content->content_link, PHP_URL_PATH));
                    } else {
                        //get quiz and status
                        $quiz_status = Quiz::where('id', $content->content_link)
                            ->with(['quizStatus' => function ($query) use ($user_id) {
                                $query->where('user_id', $user_id);
                            }])
                            ->first();

                        $maxAttempt = QuizStatus::where('quiz_id', $quiz_status['id'])->where('user_id', $user_id)->max('attempts');

                        $content->max_attempt = $maxAttempt;

                        if ($quiz_status->quizStatus->isEmpty()) {
                            unset($maxAttempt->quizStatus);
                            $content->max_attempt = 0;
                        }

                        $content->number_of_questions = $quiz_status['no_of_questions'];

                        $content->no_of_attempts = $quiz_status['no_of_attempts'];

                        //if quiz status == 0 then remove $key from course contents
                        if ($quiz_status['status'] == 0) {
                            unset($course->course_contents[$key]);
                        }
                    }
                }

                $totalRating = 0;
                $totalRatingCount = 0;
                $rating1Count = 0;
                $rating2Count = 0;
                $rating3Count = 0;
                $rating4Count = 0;
                $rating5Count = 0;
                foreach ($course->courseRating as $rating) {
                    $totalRating += $rating->rating;
                    $totalRatingCount++;
                    if ($rating->rating == 1) {
                        $rating1Count++;
                    } else if ($rating->rating == 2) {
                        $rating2Count++;
                    } else if ($rating->rating == 3) {
                        $rating3Count++;
                    } else if ($rating->rating == 4) {
                        $rating4Count++;
                    } else if ($rating->rating == 5) {
                        $rating5Count++;
                    }
                }

                $avg =  $totalRatingCount > 0 ? $totalRating / $totalRatingCount : 0;
                $course->averageRating = number_format((float)$avg, 1, '.', '');
                $course->totalRatingCount = $totalRatingCount;
                $course->rating_1 = $rating1Count;
                $course->rating_2 = $rating2Count;
                $course->rating_3 = $rating3Count;
                $course->rating_4 = $rating4Count;
                $course->rating_5 = $rating5Count;

                if (in_array($course->id, $registeredCourses)) {
                    $course->is_registered = 1;
                    $regCourse = $this->getCurrencyAndCoursePrice($course->id, $user_id);

                    // $course->price = $regCourse['price'];
                    // $course->other_price = $regCourse['other_price'];
                    // $course->currency = $regCourse['currency'];
                    $course->prices = $regCourse;
                } else {
                    $course->is_registered = 0;

                    //get currencies for course
                    $courseCurrencies = CoursesCurrency::with('currency')
                        ->where('course_id', $course->id)
                        ->get();
                    $course->prices = $courseCurrencies;
                }
            }

            //get currency and set to course
            // $courses[0]->currency = AppConstants::CANADIAN_DOLLAR;
            // return $courses;
            return ['totalStudents' => $totalStudents, 'activeWebinar' => $activeWebinar, 'courses' => $courses];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * insert Students Course Content
     */

    public function insertStudentsCourseContent($course_id, $user_id)
    {
        try {
            $courseContent = CourseContent::where('course_id', $course_id)->get();
            foreach ($courseContent as $content) {
                $courseContent = StudentsCourseStatus::create([
                    'user_id' => $user_id,
                    'course_contents_id' => $content->id,
                    'status' => AppConstants::VIDEO_INCOMPLETE,
                ]);
                $courseContent->save();
            }
            return $courseContent;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Fetch Course Videos
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Fetch Course Videos
     */

    public function fetchCourseVideos($id)
    {
        try {
            //get logged in user
            $user = auth()->user();
            $course_id = Course::find($id)->id;
            if (!$course_id) {
                return false;
            }
            // $payment =  PaymentDetail::where('user_id', $user->id)->first();
            if ($course_id) {
                // $course_id = $payment->course_id;
                $courseContent = CourseContent::where('course_id', $course_id)
                    ->where('status', AppConstants::ACTIVE)
                    ->orderByRaw("CAST(SUBSTRING_INDEX(week, ' ', -1) AS UNSIGNED)")
                    ->orderBy('week', 'asc')
                    ->orderBy('day', 'asc')
                    ->where('content_type', 1)
                    ->get(['id', 'week', 'day', 'content', 'content_link', 'duration', 'is_locked']);


                foreach ($courseContent as $content) {
                    // dd($content);

                    // if ($content->content_type = 1) {
                    //     //remove id from link
                    //     $content->content_link = explode('?id=', $content->content_link)[0];
                    //     //generate one time signed url
                    //     $s3Client = app('s3');
                    //     $objectKey = 'videos/1702542125.mp4';


                    //     $cmd = $s3Client->getCommand('GetObject', [
                    //         'Bucket' => env('AWS_BUCKET'),
                    //         'Key' => $objectKey,
                    //     ]);


                    //     $request = $s3Client->createPresignedRequest($cmd, '+1 minutes');

                    //     // Get the actual presigned-url
                    //     $presignedUrl = (string)$request->getUri();
                    //     dd($presignedUrl);


                    //     $expiration = Carbon::now()->addSeconds(10);
                    //     $objectKey = 'videos/1702542125.mp4';

                    //     $url = $s3Client->getObjectUrl(env('AWS_BUCKET'), $objectKey, $expiration);
                    //     dd($url);
                    // }

                    //check user and course content id exists in students course status
                    $courseCont = StudentsCourseStatus::where('user_id', $user->id)
                        ->where('course_contents_id', $content->id)
                        ->first();

                    //if exists then set status to course content
                    if ($courseCont) {
                        $content->completed_status = AppConstants::VIDEO_COMPLETED;
                    } else {
                        $content->completed_status = AppConstants::VIDEO_INCOMPLETE;
                    }

                    $content->thumbnail = null;
                    $videoId = explode('=', $content->content_link)[1] ?? null;
                    // Parse the URL
                    $path = parse_url($content->content_link, PHP_URL_PATH);

                    // Get the filename from the path
                    $filename = basename($path);

                    //set this filename to content
                    $content->aws_file_name = $filename;
                    if (isset($videoId)) {
                        $video = Video::find($videoId);
                        if (!$video) {
                            $content->thumbnail = null;
                        } else {
                            $content->thumbnail = $video->thumbnail;
                        }
                    }
                }

                return $courseContent;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * markAsCompleted
     */

    public function markAsCompleted($data)
    {
        try {

            $user = auth()->user();

            //check course content id exists
            $courseContent = CourseContent::find($data['course_content_id']);
            if (!$courseContent) {
                return ['status' => false, 'message' => 'Course content not found.'];
            }


            $courseContent = StudentsCourseStatus::where('user_id', $user->id)
                ->where('course_contents_id', $data['course_content_id'])
                ->first();

            if ($courseContent) {
                return ['status' => false, 'message' => 'Course content already marked as completed.'];
            }
            //insert into students course status
            $courseContent = StudentsCourseStatus::create([
                'user_id' => $user->id,
                'course_contents_id' => $data['course_content_id'],
                'status' => AppConstants::VIDEO_COMPLETED,
            ]);

            // $courseContent->status = AppConstants::VIDEO_COMPLETED;
            // $courseContent->save();

            return ['status' => true, 'message' => 'Course content marked as completed.', 'data' => $courseContent];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course content update failed.'];
        }
    }

    /**
     * fetchCourseContentByCourseId
     */

    public function quizzesByCourseId($course_id)
    {
        try {
            $quiz = Quiz::where('course_id', $course_id)
                ->where('status', AppConstants::ACTIVE)
                ->get();
            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Fetch Course content by course id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchCourseContent($course_id)
    {
        try {
            $courseContent = CourseContent::where('course_id', $course_id)
                ->where('status', AppConstants::ACTIVE)
                ->orderBy('week', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            return $courseContent;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * UpdatecCourse Content
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function updateCourseContent($request)
    {
        try {

            //begin transaction
            DB::beginTransaction();

            $dataArray = $request->all();
            if (!is_array($dataArray)) {
                return ['status' => false, 'message' => 'Invalid data'];
            }

            //foreach data array and update by id
            foreach ($dataArray as $data) {

                $courseContent = CourseContent::find($data['id']);
                if (!$courseContent) {
                    return ['status' => false, 'message' => 'Course content not found for id ' . $data['id']];
                }

                $courseContent->content_type = $data['content_type'];
                $courseContent->content = $data['content'];
                $courseContent->content_link = $data['link'];
                $courseContent->duration = $data['duration'];
                $courseContent->is_locked = $data['is_locked'];
                $courseContent->day = $data['day'];
                $courseContent->save();
            }
            //commit transaction
            DB::commit();
            $content_id = $dataArray[0]['id'];
            $courseContent = CourseContent::where('id', $content_id)->first();
            return ['status' => true, 'message' => 'Course content created successfully', 'data' => ['course_id' => $courseContent->course_id]];
        } catch (\Exception $e) {

            DB::rollback();
            //if duplicate entry
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['status' => false, 'message' => 'Course content week already exists'];
            }
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course content creation failed'];
        }
    }

    /**
     * Course Progress
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function courseProgress()
    {
        try {
            $user = auth()->user();
            // $registeredDate = $user->created_at;
            //get user's course id
            // $payment = PaymentDetail::where('user_id', $user->id)->get();

            $courses = PaymentDetail::where('user_id', $user->id)
                ->whereHas('course', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })
                ->where('status', AppConstants::ACTIVE)
                ->get();



            //get course name using course id and set to course object
            foreach ($courses as $course) {
                $created_at = $course->created_at;
                $created_at->setTimezone(env('APP_TIMEZONE'));
                $course->registeredDate = $created_at->format('Y-m-d');
                $co = Course::find($course->course_id);
                $course->course_name = $co->name;
                $course->prices = $this->getCurrencyAndCoursePrice($co->id, $user->id);
                // $course->course_id = $detail->id;

                //get course contents for course id
                $courseContents = CourseContent::where('course_id', $co->id)
                    ->where('status', AppConstants::ACTIVE)
                    ->where('content_type', 1)
                    ->get();

                //get completed course contents count
                //must be those contents which are in $courseContents
                $completedCourseContents = StudentsCourseStatus::where('user_id', $user->id)
                    ->where('status', AppConstants::VIDEO_COMPLETED)
                    ->whereIn('course_contents_id', $courseContents->pluck('id'))
                    ->count();

                //get total course contents
                $totalCourseContents = count($courseContents);
                // //get completed course contents
                // $completedCourseContents = StudentsCourseStatus::where('user_id', $user->id)
                //     ->where('status', AppConstants::VIDEO_COMPLETED)
                //     ->count();
                //calculate progress

                $progress = $completedCourseContents > 0 ? ($completedCourseContents / $totalCourseContents) * 100 : 0;

                //roud off progress
                $progress = round($progress, 2);

                $course->progress = $progress;
            }

            return $courses;
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Fetch all courses for admin
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchAllStatus()
    {

        try {
            //order by desc
            $courses = Course::with('courseCurrencies')
                ->orderBy('id', 'desc')
                ->get();
            return $courses;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }


    /**
     * Fetch week wise Course content by course id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchCourseContentByWeek($course_id)
    {
        try {
            //get week wise course contents for course id
            $courseContent = CourseContent::where('course_id', $course_id)
                ->where('status', AppConstants::ACTIVE)
                ->orderByRaw("CAST(SUBSTRING_INDEX(week, ' ', -1) AS UNSIGNED)")
                ->orderBy('week', 'asc')
                ->orderBy('day', 'asc')
                ->get();

            $filteredCourseContent = [];
            foreach ($courseContent as $content) {
                // if content_type == 2
                // check quiz status
                if ($content->content_type == 2) {
                    $quiz = Quiz::find($content->content_link);
                    if ($quiz && $quiz->status == 1) {
                        // Only add content with valid quizzes and active status
                        $filteredCourseContent[] = $content;
                    }
                } else {
                    // Add content other than quizzes directly
                    $filteredCourseContent[] = $content;
                }
            }

            $organizedContent = [];

            foreach ($filteredCourseContent as $content) {
                $week = $content->week;
                $contentItem = [
                    "id" => $content->id,
                    "course_id" => $content->course_id,
                    "week" => $content->week,
                    "day" => $content->day,
                    "content" => $content->content,
                    "content_link" => $content->content_link,
                    "duration" => $content->duration,
                    "is_locked" => $content->is_locked,
                    "status" => $content->status,
                    "content_type" => $content->content_type,
                    "thumbnail" => $content->thumbnail,
                ];

                if (!isset($organizedContent[$week])) {
                    $organizedContent[$week] = [];
                }

                $organizedContent[$week][] = $contentItem;
            }

            $finalResult = [];

            foreach ($organizedContent as $week => $content) {
                $finalResult[] = [
                    "week" => $week,
                    "content" => $content,
                ];
            }


            return ['status' => true, 'message' => 'Course content fetched successfully', 'data' => $finalResult];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course content fetch failed'];
        }
    }

    /**
     * Lock Course Content
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Lock Course Content
     */

    public function lockCourseContent($data)
    {
        try {
            $courseContent = CourseContent::find($data['content_id']);
            if (!$courseContent) {
                return false;
            }
            $courseContent->is_locked = $data['is_locked'];
            $courseContent->save();
            return $courseContent;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Delete Course Content
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function deleteCourseContent($content_id)
    {
        try {
            $courseContent = CourseContent::find($content_id);
            if (!$courseContent) {
                return false;
            }
            $courseContent->delete();
            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * checkReview
     */

    public function checkReview($data)
    {

        try {
            $user = $data['user_id'];
            $review = CourseRating::where('course_id', $data['course_id'])
                ->where('user_id', $user)
                ->first();
            if ($review) {
                return ['status' => true, 'message' => 'Review already exists', 'data' => 1];
            } else {
                return ['status' => true, 'message' => 'Review not found', 'data' => 0];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'something went wrong'];
        }
    }

    /**
     * Update Section
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function updateSection($data)
    {
        try {
            //start transaction
            DB::beginTransaction();
            $courseContent = CourseContent::find($data['content_id']);
            if (!$courseContent) {
                return false;
            }

            $courseContents = CourseContent::where('course_id', $courseContent->course_id)
                ->where('week', $courseContent->week)
                ->get();

            foreach ($courseContents as $content) {
                $content->week = $data['week'];
                $content->save();
            }

            $quiz = Quiz::where('week', $courseContent->week)
                ->get();

            foreach ($quiz as $q) {
                $q->week = $data['week'];
                $q->save();
            }
            //commit transaction
            DB::commit();
            return $courseContent->course_id;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            DB::rollback();
            return false;
        }
    }

    /**
     * Fetch All Courses For Student
     */

    public function fetchAllCoursesForStudent()
    {
        //get logged in user's registered courses from payment details and course table
        try {
            $user = auth()->user();
            $courses = PaymentDetail::where('user_id', $user->id)
                ->whereHas('course', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })
                ->where('status', AppConstants::ACTIVE)
                ->get();

            //get course name using course id and set to course object
            foreach ($courses as $course) {
                $course->course_name = Course::find($course->course_id)->name;
            }


            return $courses;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //fetchCourseCatalog
    public function fetchCourseCatalog()
    {
        try {
            //get logged in user's registered courses from payment_details
            $user = auth()->user();

            //get all courses
            // $courses = Course::where('is_active', AppConstants::ACTIVE)->get();
            //get course id's $user registered from payment_details
            $registeredCourses = PaymentDetail::where('user_id', $user->id)
                // ->where('status', AppConstants::ACTIVE)
                // ->pluck('course_id')->toArray();
                ->get();

            //get course id's from $registeredCourses
            $registeredCourses = $registeredCourses->pluck('course_id')->toArray();

            // Get the course IDs with status == 1 from payment_details
            $inValidCourseIds = PaymentDetail::where('user_id', $user->id)
                ->where('status', AppConstants::INACTIVE)
                ->pluck('course_id')->toArray();

            //remove courses from $courses which are not in $inValidCourseIds
            //where course is_active == 1
            $courses = Course::where('is_active', AppConstants::ACTIVE)->whereNotIn('id', $inValidCourseIds)->get();

            //add flag to courses if registered
            foreach ($courses as $key => $course) {

                if (in_array($course->id, $registeredCourses)) {

                    $course->is_registered = 1;
                    $course->prices = $this->getCurrencyAndCoursePrice($course->id, $user->id);
                    //convert to array
                    // $course->prices = $coursePrices->toArray();
                    //$course->prices =
                } else {

                    if ($course->is_invisible == 1) {
                        //unset from  $courses
                        unset($courses[$key]);
                    } else {
                        $course->is_registered = 0;
                        $course->prices = CoursesCurrency::with('currency')
                            ->where('course_id', $course->id)
                            ->get();
                    }
                }

                //get course rating
                $courseRating = CourseRating::where('course_id', $course->id)
                    ->where('is_approved', AppConstants::ACTIVE)
                    ->with('users')
                    ->get();
                //calculate average rating
                $totalRating = 0;
                $totalRatingCount = 0;
                foreach ($courseRating as $rating) {
                    $totalRating += $rating->rating;
                    $totalRatingCount++;
                }

                $course->averageRating = $totalRatingCount > 0 ? $totalRating / $totalRatingCount : 0;
                $course->totalRatingCount = $totalRatingCount;
            }
            return ['status' => true, 'message' => 'Course catalog fetched successfully', 'data' => $courses];
        } catch (\Exception $e) {
            dd($e->getMessage());
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course catalog fetch failed'];
        }
    }

    //checkEnroll
    public function checkEnroll($course_id)
    {
        try {
            //get logged in user's registered courses from payment_details
            $user = auth()->user();

            //get all courses
            $courses = PaymentDetail::where('user_id', $user->id)
                ->where('course_id', $course_id)
                ->first();

            if ($courses) {
                return ['status' => true, 'message' => 'User already enrolled', 'data' => 1];
            } else {
                return ['status' => false, 'message' => 'User not enrolled', 'data' => 0];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Course enroll check failed'];
        }
    }

    //getSignedUrl
    public function getSignedUrl($id)
    {
        try {

            //check course content exists
            $courseContent = CourseContent::where('id', $id)->where('status', AppConstants::ACTIVE)->first();
            if (!$courseContent) {
                return ['status' => false, 'message' => 'Course content not found'];
            }

            // Parse the URL
            $urlParts = parse_url($courseContent->content_link);

            // Get the path from the parsed URL
            $path = ltrim($urlParts['path'], '/');

            // // Generate a unique token
            // $token = bin2hex(random_bytes(16));

            // Store the token in the database
            // $token = VideoAccess::create(['token' => $token, 'video_url' => $path, 'duration' => $courseContent->duration]);

            $s3Client = app('s3');
            $objectKey = $path;
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $objectKey,
            ]);
            $request = $s3Client->createPresignedRequest($cmd, '+1 minutes');
            // Get the actual presigned-url
            $presignedUrl = (string)$request->getUri();
            return ['status' => true, 'message' => 'Signed URL fetched successfully', 'data' => $presignedUrl];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Signed URL fetchtig failed'];
        }
    }

    //get currency for currency id
    public function getCurrencyById($id)
    {
        try {
            $currency = Currency::find($id);
            if (!$currency) {
                return false;
            }
            return $currency;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //get currency and course price for course id and user id
    public function getCurrencyAndCoursePrice($course_id, $user_id)
    {
        try {
            //get course amount for $course_id and user_id
            $paidCourse = PaymentDetail::where('course_id', $course_id)
                ->where('user_id', $user_id)
                ->where('status', AppConstants::ACTIVE)
                ->first();

            if ($paidCourse) {

                //get course currency
                $courseCurrency = CoursesCurrency::with('currency')
                    ->where('course_id', $course_id)
                    ->where('currency_id', $paidCourse['course_currency_id'])
                    ->get();

                return $courseCurrency;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //notRegisteredForCourse
    public function notRegisteredForCourse($course_id)
    {
        try {
            $users = User::where('is_active', AppConstants::ACTIVE)
                ->whereDoesntHave('payment_details', function ($query) use ($course_id) {
                    $query->where('course_id', $course_id);
                })
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'student');
                })
                ->get();

            if ($users) {
                return ['status' => true, 'message' => 'Users fetched successfully', 'data' => $users];
            } else {
                return ['status' => false, 'message' => 'Users not found'];
            }
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Users fetch failed'];
        }
    }

    //userAddToCourse
    public function userAddToCourse($data, $course)
    {
        try {

            $users = $data['users'];
            $price = $course['courseCurrencies'][0]['price'];

            foreach ($users as $user) {

                //check if user already registered
                $userRegistered = PaymentDetail::where('user_id', $user)
                    ->where('course_id', $data['course_id'])
                    ->first();

                if ($userRegistered) {
                    return ['status' => false, 'message' => 'User already registered for user id ' . $user];
                }

                $userRegistration = PaymentDetail::create([
                    'user_id' => $user,
                    'course_id' => $data['course_id'],
                    'price' => $price,
                    'is_manual_payment' => AppConstants::MANUAL_PAYMENT,
                    'payment_trans_id' => 0,
                    'course_currency_id' => $data['currency_id'],
                    'promo_code_id' => 0,
                ]);
                $userRegistration->save();
            }

            return ['status' => true, 'message' => 'User add to course successfully'];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'User add to course failed'];
        }
    }
}
