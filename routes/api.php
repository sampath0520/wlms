<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\OrientationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SystemParametersController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\WebinarController;
use App\Http\Controllers\ZoomController;
use App\Models\CourseContent;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


//Student routes
Route::group(['prefix' => 'student'], function () {
    Route::post('/register', [UserController::class, 'register'])->withoutMiddleware(['auth:api']);
    Route::post('/discountPrice', [UserController::class, 'discountPrice'])->withoutMiddleware(['auth:api']);

    Route::post('/login', [AuthController::class, 'login']);

    //check authentication
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('/fetch_students', [UserController::class, 'fetch_students']);

        //forum routes
        Route::group(['prefix' => 'forum'], function () {
            Route::post('/create', [ForumController::class, 'createForum'])->name('forum.create');
            Route::post('/addReply', [ForumController::class, 'addReply'])->name('forum.addReply');
            Route::delete('/deleteReply/{id}', [ForumController::class, 'deleteReply'])->name('forum.deleteReply');
            Route::delete('/delete/{id}', [ForumController::class, 'deleteForum'])->name('forum.delete');
            Route::post('/register', [ForumController::class, 'registerForum'])->name('forum.register');
        });

        //course routes
        Route::group(['prefix' => 'course'], function () {

            Route::post('/addReview', [CourseController::class, 'addReview'])->name('courses.addReview');
            Route::get('/fetchCatalog', [CourseController::class, 'fetchCourseCatalog'])->name('courses.fetchCatalog');
            Route::get('/detailForLoggedUser/{id}', [CourseController::class, 'detailFetchForLoggedUser'])->name('courses.detailFetchForLoggedUser');
            Route::post('/checkReview', [CourseController::class, 'checkReview'])->name('courses.checkReview');
            Route::post('/enrollAndPayment', [CourseController::class, 'enrollAndPayment'])->name('courses.enrollAndPayment');
            Route::get('/getSignedUrl/{id}', [CourseController::class, 'getSignedUrl'])->name('courses.getSignedUrl');
            Route::group(['middleware' => 'checkVideoAccess'], function () {
                Route::get('/getVideoUrl/{token}', [CourseController::class, 'getVideoUrl'])->name('courses.getVideoUrl');
            });
        });

        //announcement routes
        Route::group(['prefix' => 'announcement'], function () {
            Route::get('/fetch', [AnnouncementController::class, 'fetchAnnouncements'])->name('announcement.fetch');
        });

        //quiz routes
        Route::group(['prefix' => 'quiz'], function () {
            //fetch quiz with attempt status
            Route::get('/list/{id}', [QuizController::class, 'fetchQuizez'])->name('quiz.fetch');
            Route::get('/listWithQuestions/{id}', [QuizController::class, 'fetchQuizezWithQuestions'])->name('quiz.fetchWithQuestions');
            Route::post('/start', [QuizController::class, 'startQuiz'])->name('quiz.start');
            Route::post('/answer', [QuizController::class, 'quizAnswer'])->name('quiz.answer');
            Route::post('/complete', [QuizController::class, 'completeQuiz'])->name('quiz.complete');
            Route::get('/viewMarks/{id}', [QuizController::class, 'viewMarks'])->name('quiz.viewMarks');

            Route::post('/deleteAttempt', [QuizController::class, 'deleteAttempt'])->name('quiz.deleteAttempt');

            Route::post('/questionWiseAssessment', [QuizController::class, 'questionWiseAssessment'])->name('quiz.questionWiseAssessment');
        });

        //video
        Route::group(['prefix' => 'video'], function () {

            Route::get('/fetchCourseVideos/{id}', [CourseController::class, 'fetchCourseVideos'])->name('video.fetchCourseVideos');
            Route::patch('/markAsCompleted', [CourseController::class, 'markAsCompleted'])->name('video.markAsCompleted');
        });

        //dashboard
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/courseProgress', [CourseController::class, 'courseProgress'])->name('dashboard.courseProgress');
            Route::get('/webinarList', [WebinarController::class, 'getAllWebinarsForUser'])->name('dashboard.webinarList');
        });
    });

    //course routes
    Route::group(['prefix' => 'course'], function () {
        Route::get('/detailFetch', [CourseController::class, 'detailFetch'])->name('courses.detailFetch');
        Route::get('/fetchAll', [CourseController::class, 'fetchAllCoursesStudent'])->name('courses.fetchAllStudent');
    });

    //orientation routes
    Route::group(['prefix' => 'orientation'], function () {
        Route::post('/create', [OrientationController::class, 'createOrientation'])->name('orientation.create');
    });
});



//Admin Routes without authentication
Route::group(['prefix' => 'admin'], function () {
    //check authentication
    Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['prefix' => 'message'], function () {
            Route::get('/chatView/{id}', [MessageController::class, 'chatView'])->name('message.chatView');
        });
    });
});

//Admin routes with authentication and role
Route::group(['middleware' => ['auth:api', 'role:admin']], function () {
    Route::group(['prefix' => 'admin'], function () {

        //user routes
        Route::group(['prefix' => 'users'], function () {
            Route::post('/create', [UserController::class, 'createUser'])->name('users.create');
            Route::post('/fetch', [UserController::class, 'fetchAllUsers'])->name('users.fetch');
            Route::put('/activate_deactivate', [UserController::class, 'activateDeactivate'])->name('users.activate_deactivate');

            Route::delete('/delete/{user_id}', [UserController::class, 'deleteUser'])->name('users.delete');
        });

        //student routes
        Route::group(['prefix' => 'students'], function () {
            Route::post('/fetch', [UserController::class, 'fetchAllStudents'])->name('students.fetch');
            Route::post('/create', [UserController::class, 'createStudent'])->name('students.create');
            Route::get('/latest_registrations', [UserController::class, 'fetchLatestRegistrations'])->name('students.latest_registrations');
            Route::put('/course-disable', [UserController::class, 'courseDisable'])->name('students.course_disable');
            Route::post('/addToCourse', [CourseController::class, 'addToCourse'])->name('students.addToCourse');
            Route::get('/notRegisteredForCourse/{id}', [CourseController::class, 'notRegisteredForCourse'])->name('students.notRegisteredForCourse');
        });

        //course routes
        Route::group(['prefix' => 'courses'], function () {
            Route::post('/create', [CourseController::class, 'createCourse'])->name('courses.create');
            Route::put('/activate_deactivate', [CourseController::class, 'activateDeactivateCourse'])->name('courses.activate_deactivate');
            Route::get('/fetch_by_id/{id}', [CourseController::class, 'fetchCourseById'])->name('courses.fetchAdmin');
            Route::post('/update', [CourseController::class, 'updateCourseById'])->name('courses.update');
            Route::delete('/delete/{id}', [CourseController::class, 'deleteCourse'])->name('courses.delete');
            Route::get('/quizzesByCourseId/{id}', [CourseController::class, 'quizzesByCourseId'])->name('courses.quizzesByCourseId');
            Route::get('/fetchAllStatus', [CourseController::class, 'fetchAllStatus'])->name('courses.fetchAllStatus');


            //course content routes
            Route::post('/content/create', [CourseController::class, 'createCourseContent'])->name('courses.content.create');
            Route::get('/content/fetch/{id}', [CourseController::class, 'fetchCourseContent'])->name('courses.content.fetch');
            Route::get('/content/byWeek/{id}', [CourseController::class, 'fetchCourseContentByWeek'])->name('courses.content.byWeek');
            Route::put('/content/update', [CourseController::class, 'updateCourseContent'])->name('courses.content.update');
            Route::put('/content/lock', [CourseController::class, 'lockCourseContent'])->name('courses.content.lock');
            Route::delete('/content/delete/{id}', [CourseController::class, 'deleteCourseContent'])->name('courses.content.delete');
            Route::patch('/content/updateSection', [CourseController::class, 'updateSection'])->name('courses.content.updateSection');
        });

        //Quiz routes
        Route::group(['prefix' => 'quiz'], function () {

            Route::get('/weekDropdown/{id}', [QuizController::class, 'weekDropdown'])->name('quiz.weekDropdown');
            Route::post('/create', [QuizController::class, 'createQuiz'])->name('quiz.create');

            Route::get('/fetch', [QuizController::class, 'fetchAllQuizez'])->name('quiz.fetchAdmin');
            Route::get('/fetchById/{id}', [QuizController::class, 'fetchQuizById'])->name('quiz.fetch_by_id');
            Route::put('/activateDeactivate', [QuizController::class, 'activateDeactivateQuiz'])->name('quiz.activate_deactivate');
            Route::delete('/delete/{id}', [QuizController::class, 'deleteQuiz'])->name('quiz.delete');
            Route::get('/submissionList', [QuizController::class, 'submissionList'])->name('quiz.submissionList');
            Route::put('/addFeedback', [QuizController::class, 'addFeedback'])->name('quiz.addFeedback');
            Route::get('/fetchByCourseId/{id}', [QuizController::class, 'quizzesByCourseId'])->name('quiz.byCourseId');

            Route::post('/questionWiseAssessment', [QuizController::class, 'studentQuestionWiseAssessment'])->name('quiz.studentQuestionWiseAssessment');
            Route::post('/deleteAttempt', [QuizController::class, 'deleteAttempt'])->name('quiz.deleteAttempts');
            Route::post('/update', [QuizController::class, 'updateQuiz'])->name('quiz.update');
        });

        //Question routes
        Route::group(['prefix' => 'question'], function () {
            Route::post('/questionsAnswers', [QuestionController::class, 'questionsAnswers'])->name('quiz.questionsAnswers');
            Route::get('/fetchQnA/{quiz_id}', [QuestionController::class, 'fetchAllQuestionsAnswers'])->name('quiz.question.fetch');
            Route::delete('/delete/{id}', [QuestionController::class, 'deleteQuestionsAndAnswers'])->name('quiz.question.delete');
            Route::get('/fetch/{id}', [QuestionController::class, 'fetchQuestionById'])->name('quiz.question.fetch_by_id');
            Route::post('/update', [QuestionController::class, 'updateQuestionsAndAnswersById'])->name('quiz.question.update');
        });

        //forum routes
        Route::group(['prefix' => 'forum'], function () {
            Route::post('/create', [ForumController::class, 'createForum'])->name('forum.createForAdmin');
            Route::put('/update', [ForumController::class, 'updateForum'])->name('forum.update');
            Route::put('/changeStatus', [ForumController::class, 'changeStatus'])->name('forum.changeStatus');
            Route::get('/fetch/{id}', [ForumController::class, 'fetchForumById'])->name('forum.fetch_by_id');
            Route::get('/participants/{id}', [ForumController::class, 'fetchForumParticipants'])->name('forum.participants');
            Route::delete('/deleteParticipants/{id}', [ForumController::class, 'deleteParticipants'])->name('forum.deleteParticipants');
            Route::delete('/deleteReply/{id}', [ForumController::class, 'deleteReply'])->name('forum.deleteReplyAdmin');
        });

        //announcement routes
        Route::group(['prefix' => 'announcement'], function () {
            Route::post('/create', [AnnouncementController::class, 'createAnnouncement'])->name('announcement.create');
            Route::get('/fetchAll', [AnnouncementController::class, 'fetchAllAnnouncements'])->name('announcement.fetchAll');
            Route::delete('/delete/{id}', [AnnouncementController::class, 'deleteAnnouncement'])->name('announcement.delete');
            Route::get('/fetch_by_id/{id}', [AnnouncementController::class, 'fetchAnnouncementById'])->name('announcement.fetch_by_id');
            Route::post('/update', [AnnouncementController::class, 'updateAnnouncementById'])->name('announcement.update');
        });

        //message routes
        Route::group(['prefix' => 'message'], function () {
            Route::get('/fetch', [MessageController::class, 'fetchMessages'])->name('message.fetch');
        });

        //review routes
        Route::group(['prefix' => 'review'], function () {
            Route::get('/fetch/{id}', [ReviewController::class, 'fetchReviews'])->name('review.fetch');
            Route::get('/unapprovedList', [ReviewController::class, 'unapprovedList'])->name('review.unapprovedList');
            Route::post('/addReviewAdmin', [ReviewController::class, 'addReviewByAdmin'])->name('review.addReviewByAdmin');
            Route::put('/approve', [ReviewController::class, 'approveReview'])->name('review.approve');
            Route::get('/fullList', [ReviewController::class, 'fullList'])->name('review.fullList');
            Route::delete('/delete/{id}', [ReviewController::class, 'deleteReview'])->name('review.delete');
        });

        //reports
        Route::group(['prefix' => 'reports'], function () {
            Route::post('/studentReport', [UserController::class, 'studentReport'])->name('reports.studentReport');
            Route::post('/paymentReport', [UserController::class, 'paymentReport'])->name('reports.paymentReport');

            Route::post('/studentExport', [UserController::class, 'studentReportExport'])->name('reports.studentExport');
            Route::post('/paymentExport', [UserController::class, 'paymentReportExport'])->name('reports.paymentExport');
        });

        Route::group(['prefix' => 'orientation'], function () {
            Route::get('/fetch', [OrientationController::class, 'fetchOrientations'])->name('orientation.fetch');
        });

        //webinar routes
        Route::group(['prefix' => 'webinar'], function () {
            Route::post('/create', [WebinarController::class, 'createWebinar'])->name('webinar.create');
            Route::get('/fetch', [WebinarController::class, 'getAllWebinars'])->name('webinar.fetch');
            Route::put('/activate_deactivate', [WebinarController::class, 'activateDeactivateWebinar'])->name('webinar.activate_deactivate');
            Route::delete('/delete/{id}', [WebinarController::class, 'deleteWebinar'])->name('webinar.delete');
            Route::get('/fetch_by_id/{id}', [WebinarController::class, 'fetchWebinarById'])->name('webinar.fetch_by_id');
            Route::put('/update', [WebinarController::class, 'updateWebinarById'])->name('webinar.update');
            Route::put('/complete', [WebinarController::class, 'completeWebinar'])->name('webinar.complete');
        });

        //video routes
        Route::group(['prefix' => 'video'], function () {
            Route::get('/fetchAll', [VideoController::class, 'fetchAllVideos'])->name('video.fetchAll');
            Route::post('/upload', [VideoController::class, 'createVideo'])->name('video.create');
            Route::post('/sample_class', [VideoController::class, 'SampleClass'])->name('video.SampleClass');
            Route::get('/fetchSampleClass', [VideoController::class, 'fetchSampleClass'])->name('video.fetchSampleClass');
            Route::delete('/delete/{id}', [VideoController::class, 'deleteVideo'])->name('video.delete');
            Route::put('/update', [VideoController::class, 'updateVideo'])->name('video.update');
        });

        //promo code
        Route::group(['prefix' => 'promoCode'], function () {
            Route::post('/create', [PromoCodeController::class, 'createPromoCode'])->name('promoCode.create');
            Route::get('/fetch', [PromoCodeController::class, 'fetchAllPromoCodes'])->name('promoCode.fetch');
            Route::put('/update', [PromoCodeController::class, 'updatePromoCode'])->name('promoCode.update');
            Route::put('/activate_deactivate', [PromoCodeController::class, 'activateDeactivatePromoCode'])->name('promoCode.activate_deactivate');
        });
    });
});


//Common routes
//Auth routes
Route::group(['prefix' => 'common'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('send.link');
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');

    Route::post('/emailVerify', [AuthController::class, 'emailVerify'])->name('emailVerify');


    //course routes
    Route::group(['prefix' => 'courses'], function () {
        Route::get('/fetch', [CourseController::class, 'fetchAllCourses'])->name('courses.fetch');
    });

    //logout
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('/logout', [AuthController::class, 'logout']);
        //user
        Route::group(['prefix' => 'user'], function () {
            Route::get('/dropdown/{type}', [UserController::class, 'usersDropdown'])->name('user.dropdown');
            Route::get('/fetch_by_id/{user_id}', [UserController::class, 'fetchUserById'])->name('users.fetch_by_id');
            Route::post('/update', [UserController::class, 'updateUserById'])->name('users.update');
        });

        //forum
        Route::group(['prefix' => 'forum'], function () {
            Route::post('/details', [ForumController::class, 'forumDetails'])->name('forum.details');
            Route::get('/threads/{id}', [ForumController::class, 'forumThreads'])->name('forum.threads');
        });

        //quiz
        Route::group(['prefix' => 'quiz'], function () {
            Route::post('/assessmentForm', [QuizController::class, 'assessmentForm'])->name('quiz.assessmentForm');
        });
        //change password
        Route::post('/change-password', [UserController::class, 'change_password']);

        // message
        Route::group(['prefix' => 'message'], function () {
            Route::post('/create', [MessageController::class, 'createMessage'])->name('message.create');
            Route::delete('/delete/{id}', [MessageController::class, 'deleteMessage'])->name('message.delete');
        });

        //token
        Route::group(['prefix' => 'token'], function () {
            Route::get('/userDetails', [UserController::class, 'userDetails'])->name('token.userDetails');
        });
    });

    //video routes
    Route::group(['prefix' => 'video'], function () {
        Route::get('/fetchSampleClass/{id}', [VideoController::class, 'fetchSampleClass'])->name('video.fetchSampleClassCommon');
    });

    //systemParameters
    Route::get('/systemParameters', [SystemParametersController::class, 'getSystemParameters']);

    //get system currencies
    Route::get('/systemCurrencies', [SystemParametersController::class, 'getCurrencies']);

    //hide show currencies
    Route::get('/showHideCurrencies', [SystemParametersController::class, 'showHideCurrencies']);

    //delete devices
    Route::get('/resetDevice/{email}', [UserController::class, 'resetDevice'])->name('resetDevice');

    //course currencies
    Route::get('/CourseCurrencies/{id}', [SystemParametersController::class, 'getCourseCurrencies']);
});

// //zoom meeting create
// Route::group(['prefix' => 'zoom'], function () {
//   Route::post('/create_meeting', [ZoomController::class, 'createWebinar'])->name('zoom.create_meeting');
//  Route::post('/callback', [ZoomController::class, 'callback'])->name('zoom.callback');
// });

// Route::post('/webinar/create', [ZoomController::class, 'createWebinar']);
Route::get('/webinar/callback', [WebinarController::class, 'handleZoomCallback'])->name('webinar.callback');
Route::get('/test_script', [SystemParametersController::class, 'getSystemParameters']);
Route::get('/test_posts', [VideoController::class, 'fetchAllVideos'])->name('video.posts');
//update marks
Route::get('/updateMarks', [QuizController::class, 'updateMarks'])->name('quiz.updateMarks');
