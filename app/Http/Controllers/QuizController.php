<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\AssessmentFormRequest;
use App\Http\Requests\AssessmentRequest;
use App\Http\Requests\CompleteQuizRequest;
use App\Http\Requests\CreateQuestionsAnswers;
use App\Http\Requests\QuizAnswerRequest;
use App\Http\Requests\QuizAttemptDeleteRequest;
use App\Http\Requests\QuizCreateRequest;
use App\Http\Requests\QuizStatusRequest;
use App\Http\Requests\QuizSummaryRequest;
use App\Http\Requests\QuizUpdateRequest;
use App\Http\Requests\StartQuizRequest;
use App\Http\Requests\StudentAssessmentRequest;
use App\Services\CourseService;
use App\Services\QuizService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    protected $quizService;
    protected $courseService;

    public function __construct(QuizService $quizService, CourseService $courseService)
    {
        // $this->middleware('auth');
        $this->quizService = $quizService;
        $this->courseService = $courseService;
    }

    /**
     * weekDropdown list
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * weekDropdown
     */

    public function weekDropdown($course_id)
    {
        $course = $this->courseService->fetchCourseContentByCourseId($course_id);
        if (!$course) {
            return ResponseHelper::success(trans('messages.data_not_found'));
        }

        $weeks = $this->quizService->weekDropdown($course_id);
        if ($weeks) {
            return ResponseHelper::success(trans('messages.record_fetched'), $weeks);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * createQuiz
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * createQuiz
     */

    public function createQuiz(QuizCreateRequest $request)
    {
        $validated = $request->validated();
        $quiz = $this->quizService->createQuiz($validated);
        if ($quiz) {
            return ResponseHelper::success('Quiz created successfully', $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * fetch all quizez
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetch all quizez
     */

    public function fetchAllQuizez()
    {
        $quiz = $this->quizService->fetchAllQuizez();
        if ($quiz) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * fetchQuizById
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchQuizById
     */

    public function fetchQuizById($id)
    {
        $quiz = $this->quizService->fetchQuizById($id);
        if ($quiz) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * activateDeactivateQuiz
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * activateDeactivateQuiz
     */

    public function activateDeactivateQuiz(QuizStatusRequest $request)
    {
        $validated = $request->validated();
        $quiz = $this->quizService->activateDeactivateQuiz($validated);
        if ($quiz) {
            return ResponseHelper::success('Quiz status updated successfully', $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * deleteQuiz
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Delete quiz
     */

    public function deleteQuiz($id)
    {
        $quiz = $this->quizService->fetchQuizById($id);
        if (!$quiz) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }

        $quiz = $this->quizService->deleteQuiz($id);
        if ($quiz) {
            return ResponseHelper::success(trans('messages.delete_with_course_contents'));
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * Fetch Quizez for student
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */
    public function fetchQuizez($id)
    {
        $quiz = $this->quizService->fetchQuizezForStudent($id);
        if ($quiz) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Fetch Quizez with questions for student
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchQuizezWithQuestions($id)
    {
        $quiz = $this->quizService->fetchQuizezWithStatus($id);
        if ($quiz) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quiz);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * startQuiz
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function startQuiz(StartQuizRequest $request)
    {
        $validated = $request->validated();
        $quiz = $this->quizService->fetchQuizById($validated['quiz_id']);
        if ($quiz) {
            $quizStart = $this->quizService->startQuiz($validated);
            if ($quizStart['status']) {
                return ResponseHelper::success('Quiz started.', $quizStart['data']);
            } else {
                return ResponseHelper::error($quizStart['message']);
            }
        } else {
            return ResponseHelper::error('Quiz not found');
        }
    }

    /**
     * Quiz Answer
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function quizAnswer(QuizAnswerRequest $request)
    {
        $validated = $request->validated();
        $answer = $this->quizService->insertQuizAnswer($validated);
        if ($answer['status']) {
            return ResponseHelper::success(trans('messages.record_created'), $answer['data']);
        } else {
            return ResponseHelper::error($answer['message']);
        }
    }

    /**
     * complete Quiz
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function completeQuiz(CompleteQuizRequest $request)
    {
        $validated = $request->validated();

        $quiz = $this->quizService->completeQuiz($validated['quiz_id'], $validated['attempt']);
        if ($quiz['status']) {
            return ResponseHelper::success('Quiz completed.', $quiz['data']);
        } else {
            return ResponseHelper::error($quiz['message']);
        }
    }

    /**
     * fetch Quiz Result
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function viewMarks($id)
    {
        $quizStatus = $this->quizService->fetchQuizStatusByQuizId($id);
        if ($quizStatus) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quizStatus);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Assessment Form
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function assessmentForm(AssessmentFormRequest $request)
    {
        $validated = $request->validated();
        $quizStatus = $this->quizService->assessmentFormData($validated);
        if ($quizStatus) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quizStatus);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Question Wise Assessment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function questionWiseAssessment(AssessmentRequest $request)
    {
        $validated = $request->validated();
        $quizStatus = $this->quizService->questionWiseAssessment($validated);
        if ($quizStatus) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quizStatus);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Submission List
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function submissionList()
    {
        $submissionList = $this->quizService->submissionList();
        if ($submissionList) {
            return ResponseHelper::success(trans('messages.record_fetched'), $submissionList);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * addFeedback
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function addFeedback(QuizSummaryRequest $request)
    {
        $validated = $request->validated();
        $feedback = $this->quizService->addFeedback($validated);
        if ($feedback) {
            return ResponseHelper::success('Feedback added successfully', $feedback);
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * Quizzes By Course Id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function quizzesByCourseId($id)
    {
        $course = $this->courseService->getCourseById($id);
        if ($course['status']) {
            $quiz = $this->quizService->quizzesByCourseId($id);
            if ($quiz) {
                return ResponseHelper::success(trans('messages.record_fetched'), $quiz);
            } else {
                return ResponseHelper::error(trans('messages.record_fetch_failed'));
            }
        } else {
            return ResponseHelper::error($course['message']);
        }
    }

    /**
     * studentQuestionWiseAssessment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function studentQuestionWiseAssessment(StudentAssessmentRequest $request)
    {
        $validated = $request->validated();
        $quizStatus = $this->quizService->studentQuestionWiseAssessment($validated);
        if ($quizStatus) {
            return ResponseHelper::success(trans('messages.record_fetched'), $quizStatus);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * deleteAttempt
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function deleteAttempt(QuizAttemptDeleteRequest $request)
    {
        $validated = $request->validated();

        $quizStatus = $this->quizService->deleteAttempt($validated);
        if ($quizStatus) {
            return ResponseHelper::success('Attempt deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * updateQuiz
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function updateQuiz(QuizUpdateRequest $request)
    {

        $validated = $request->validated();
        $quiz_id = $validated['quiz_id'];
        //check if quiz exists
        $quiz = $this->quizService->fetchQuizById($quiz_id);
        if ($quiz) {
            $quiz = $this->quizService->updateQuiz($validated);
            if ($quiz['status']) {
                return ResponseHelper::success($quiz['message'], $quiz['data']);
            } else {
                return ResponseHelper::error($quiz['message']);
            }
        } else {
            return ResponseHelper::error('Quiz not found');
        }
    }

    //updateMarks
    public function updateMarks()
    {
        $quiz = $this->quizService->updateMarks();
        if ($quiz) {
            return ResponseHelper::success('Marks updated successfully', $quiz);
        } else {
            return ResponseHelper::error('Marks update failed');
        }
    }
}
