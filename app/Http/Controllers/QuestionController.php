<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CreateQuestionsAnswers;
use App\Http\Requests\UpdateQuestionsAnswers;
use App\Services\CourseService;
use App\Services\QuestionsService;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    protected $quizService;
    protected $questionsAnswers;

    public function __construct(QuizService $quizService, QuestionsService $questionsAnswers)
    {
        $this->middleware('auth');
        $this->quizService = $quizService;
        $this->questionsAnswers = $questionsAnswers;
    }


    /**
     * questionsAnswers
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * questionsAnswers
     */

    public function questionsAnswers(CreateQuestionsAnswers $request)
    {

        $validated = $request->validated();
        $QandA = $this->questionsAnswers->createQuestionsAndAnswers($validated);
        if ($QandA['status']) {
            return ResponseHelper::success($QandA['message']);
        } else {
            return ResponseHelper::error($QandA['message']);
        }
    }

    /**
     * fetchAllQuestionsAnswers
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchAllQuestionsAnswers
     */

    public function fetchAllQuestionsAnswers($quiz_id)
    {
        $quiz = $this->quizService->fetchQuizById($quiz_id);
        if (!$quiz) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }

        $questions = $this->questionsAnswers->fetchAllQuestionsAnswers($quiz_id);
        if ($questions) {
            return ResponseHelper::success(trans('messages.record_fetched'), $questions);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * deleteQuestionsAndAnswers
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * deleteQuestionsAndAnswers
     */

    public function deleteQuestionsAndAnswers($id)
    {
        $quiz = $this->questionsAnswers->fetchQuestionsById($id);
        if (!$quiz) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        $questions = $this->questionsAnswers->deleteQuestionsAndAnswers($id);

        if ($questions) {
            return ResponseHelper::success(trans('messages.delete_success'));
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * fetchQuestionsById
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchQuestionsById
     */

    public function fetchQuestionById($id)
    {
        $questions = $this->questionsAnswers->fetchQuestionsById($id);

        if ($questions) {
            return ResponseHelper::success(trans('messages.record_fetched'), $questions);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * updateQuestionsAndAnswersById
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * updateQuestionsAndAnswersById
     */

    public function updateQuestionsAndAnswersById(UpdateQuestionsAnswers $request)
    {

        $validated = $request->validated();

        $quiz = $this->questionsAnswers->updateQuestionsAndAnswersById($validated);
        if ($quiz) {
            return ResponseHelper::success('Question updated successfully', $quiz);
        } else {
            return ResponseHelper::error('Question update failed');
        }
    }
}
