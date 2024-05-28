<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Illuminate\Support\Facades\DB;

class QuestionsService
{


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create Questions and answers
     */

    public function createQuestionsAndAnswers($data)
    {

        try {
            $user = auth()->user();
            DB::beginTransaction();
            $quiz = Quiz::find($data['quiz_id']);

            if (!$quiz) {
                return [
                    'status' => false,
                    'message' => 'Quiz not found'
                ];
            }
            //get no of questions count from questions according to quiz id
            $no_of_questions = Question::where('quiz_id', $data['quiz_id'])->count();
            $quiz_no_of_questions = $quiz->no_of_questions;

            //check if no_of_questions is greater than quiz_no_of_questions
            if ($no_of_questions >= $quiz_no_of_questions) {
                return [
                    'status' => false,
                    'message' => 'No of questions exceeded'
                ];
            }


            //image upload
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/Question', 'public');
            } else {
                $imagePath = null;
            }

            //$data['answers'] is a json string
            //loop it and add id to each answer
            $answers = json_decode($data['answers'], true);
            //check if at least one answer is correct
            $correctAnswers = collect($answers)->where('is_correct', 1);
            if ($correctAnswers->isEmpty()) {
                return [
                    'status' => false,
                    'message' => 'At least one answer must be marked as correct.'
                ];
            }
            foreach ($answers as $key => $answer) {
                $answer['id'] = $key + 1;
                $answers[$key] = $answer;
            }
            $data['answers'] = json_encode($answers);


            $quiz->questions()->create([
                'question' => $data['question'],
                'image' => $imagePath,
                'reason' => $data['reason'] ?? null,
                'answers' => $data['answers'],
            ]);
            //update questions.is_completed to 1 if no_of_questions == quiz_no_of_questions
            if ($no_of_questions + 1 == $quiz_no_of_questions) {
                $quiz->is_completed = 1;
                $quiz->save();
            }
            DB::commit();

            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                //RECORD ALL THE DATA
                ->withProperties(['quiz_id' => $data['quiz_id'], 'question' => $data['question'], 'image' => $imagePath, 'reason' => $data['reason'], 'answers' => $data['answers']])
                ->log('question_create');
            return [
                'status' => true,
                'message' => 'Question created successfully'
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return [
                'status' => false,
                'message' => 'Something went wrong'
            ];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch all questions and answers
     */

    public function fetchAllQuestionsAnswers($quiz_id)
    {
        try {
            $questions = Question::where('quiz_id', $quiz_id)->orderBy('id', 'asc')->get();
            //json_decode answers
            foreach ($questions as $key => $question) {
                $answers = json_decode($question->answers, true);
                $questions[$key]->answers = $answers;
            }
            return $questions;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete questions and answers by id
     */

    public function deleteQuestionsAndAnswers($id)
    {
        try {
            $questions = Question::find($id)->delete();
            $user = auth()->user();
            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                //RECORD ALL THE DATA
                ->withProperties(['question_id' => $id])
                ->log('question_delete');
            return $questions;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch question and answers by id
     */

    public function fetchQuestionsById($id)
    {
        try {
            $questions = Question::find($id);
            return $questions;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * update question and answers by id
     */

    public function updateQuestionsAndAnswersById($data)
    {

        try {
            DB::beginTransaction();
            $question = Question::find($data['question_id']);

            if (!$question) {
                return false;
            }
            //image upload
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/Question', 'public');
            } else {
                $imagePath = null;
            }

            //$data['answers'] is a json string
            //loop it and add id to each answer
            $answers = json_decode($data['answers'], true);
            //check if at least one answer is correct
            $correctAnswers = collect($answers)->where('is_correct', 1);
            if ($correctAnswers->isEmpty()) {
                return [
                    'status' => false,
                    'message' => 'At least one answer must be marked as correct.'
                ];
            }
            foreach ($answers as $key => $answer) {
                $answer['id'] = $key + 1;
                $answers[$key] = $answer;
            }
            $data['answers'] = json_encode($answers);

            $question->update([
                'question' => $data['question'],
                'image' => $imagePath,
                'reason' => $data['reason'] ?? null,
                'answers' => $data['answers'],
                'quiz_id' => $data['quiz_id'],
            ]);
            $user = auth()->user();
            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                ->withProperties(['quiz_id' => $data['quiz_id'], 'question' => $data['question'], 'image' => $imagePath, 'reason' => $data['reason'], 'answers' => $data['answers']])
                ->log('question_update');
            DB::commit();
            return $question;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return [
                'status' => false,
                'message' => 'Something went wrong'
            ];
        }
    }
}
