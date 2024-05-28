<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\QuizStatus;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Illuminate\Support\Facades\DB;

class QuizService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * week dropdown
     */

    public function weekDropdown($id)
    {
        try {
            $weeks = CourseContent::select('week')
                ->where('course_id', $id)
                ->groupBy('week')
                ->get();
            return $weeks;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create quiz
     */

    public function createQuiz($data)
    {
        try {
            $user = auth()->user();
            $quiz = Quiz::create(
                [
                    'course_id' => $data['course_id'],
                    'name' => $data['name'],
                    'no_of_questions' => $data['no_of_questions'],
                    'no_of_attempts' => $data['no_of_attempts'],
                    'duration' => $data['duration'],
                    'week' => $data['week'],
                    'status' => 1,
                    'description' => $data['description'],
                ]
            );

            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                ->withProperties(['quiz_id' => $quiz->id, 'name' => $quiz->name, 'no_of_questions' => $quiz->no_of_questions, 'no_of_attempts' => $quiz->no_of_attempts, 'duration' => $quiz->duration, 'week' => $quiz->week, 'status' => $quiz->status])
                ->log('quiz_create');

            return $quiz;
        } catch (\Exception $e) {
            dd($e->getMessage());
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch quizez
     */

    public function fetchAllQuizez()
    {
        try {
            $quizez = Quiz::orderBy('id', 'desc')->get();

            return $quizez;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch quizez by id
     */

    public function fetchQuizById($id)
    {
        try {
            $quiz = Quiz::with('course')->find($id);
            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * activate and deactivate quiz
     */

    public function activateDeactivateQuiz($data)
    {
        try {
            $user = auth()->user();
            $quiz = Quiz::find($data['quiz_id']);
            $quiz->status = $data['status'];
            $quiz->save();
            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                ->withProperties(['quiz_id' => $quiz->id, 'status' => $quiz->status])
                ->log('quiz_status_update');
            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete quiz
     */

    public function deleteQuiz($id)
    {
        try {
            DB::beginTransaction();
            $quiz = Quiz::find($id);
            $quiz->delete();


            // Retrieve all CourseContent entries where content_link contains the video ID query parameter
            $courseContents = CourseContent::where('content_link', $id)->where('content_type', '<>', 1)->get();

            // Delete the associated CourseContent entries
            foreach ($courseContents as $courseContent) {
                //update course content as null
                $courseContent->content_link = null;
                $courseContent->save();
            }
            DB::commit();
            return $quiz;
        } catch (\Exception $e) {
            DB::rollBack();
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch quizez
     */

    public function fetchQuizezForStudent($id)
    {
        try {
            $user = auth()->user();
            //find course id from courses
            $course_id = Course::find($id)->id;
            if (!$course_id) {
                return false;
            }
            // $course_id = $user->payment_details->pluck('course_id')->first();
            // dd($user->id, $course_id);
            $quizez = Quiz::where('course_id', $course_id)
                ->with(['quizStatus' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                    //get highest attempts number for each quiz

                    //get query count and set attempts to 0 if it's empty
                    $query->select('quiz_id', DB::raw('count(*) as attempts'));
                    $query->groupBy('quiz_id');
                }])
                ->orderByRaw("CAST(SUBSTRING_INDEX(week, ' ', -1) AS UNSIGNED)")
                ->orderBy('week', 'asc')
                ->where('is_completed', 1)
                ->where('status', AppConstants::ACTIVE)
                ->where('week', '!=', null)
                ->get();

            // Get the highest attempts from the result
            // $maxAttempts = $quizez->pluck('quizStatus.attempts')->max();
            // dd($maxAttempts);

            // Check each quiz's quiz_status and set attempts to 0 if it's empty
            foreach ($quizez as $quiz) {
                //get highest attempt for quiz
                $maxAttempt = QuizStatus::where('quiz_id', $quiz->id)->where('user_id', $user->id)->max('attempts');

                $quiz->max_attempt = $maxAttempt;

                if ($quiz->quizStatus->isEmpty()) {
                    unset($quiz->quizStatus);
                    $quiz->quiz_status = [['attempts' => 0]];
                }
            }
            return $quizez;

            //get highest attempt for quiz
            // $maxAttempts = $quizez->pluck('quizStatus.attempts')->max();
            // dd($maxAttempts);

            // $user = auth()->user();
            // $course_id = $user->payment_details->pluck('course_id')->first();

            // $quizzes = Quiz::where('course_id', $course_id)
            //     ->with(['questions' => function ($query) {
            //         $query->select('quiz_id', DB::raw('count(*) as question_count'))
            //             ->groupBy('quiz_id');
            //     }])
            //     ->with(['quizStatus' => function ($query) use ($user) {
            //         $query->where('user_id', $user->id);
            //         $query->select('quiz_id', DB::raw('count(*) as attempts'))
            //             ->groupBy('quiz_id');
            //     }])
            //     ->get();

            // $filteredQuizzes = $quizzes->filter(function ($quiz) {
            //     if ($quiz->questions->isNotEmpty() && $quiz->quizStatus->isNotEmpty()) {
            //         $questionCount = $quiz->questions->first()->question_count;
            //         $attempts = $quiz->quizStatus->first()->attempts;

            //         return $quiz->no_of_questions == $questionCount && $attempts > 0;
            //     }
            //     return false;
            // });

            // return $filteredQuizzes;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }


    /**
     * startQuiz
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function startQuiz($data)
    {
        try {
            $id = $data['quiz_id'];
            $attempt = $data['attempt'];

            $user = auth()->user();
            $maxAttempts = Quiz::findOrFail($id)->no_of_attempts;
            $existingAttempts = QuizStatus::where('quiz_id', $id)->where('user_id', $user->id)->count();
            if ($existingAttempts + 1 > $maxAttempts) {
                return ['status' => false, 'message' => 'You have exceeded the maximum number of attempts for this quiz.', 'data' => null];
            }

            //save to quiz status table
            $quiz_status = QuizStatus::create(
                [
                    'user_id' => $user->id,
                    'quiz_id' => $id,
                    // 'attempts' => $existingAttempts + 1
                    'attempts' => $attempt + 1,
                    'is_started' => 1,
                    'started_at' => now(),
                    'is_finished' => 0,
                    'finished_at' => null,
                    'marks' => null,

                ]
            );

            return ['status' => true, 'message' => 'Quiz started', 'data' => $quiz_status];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            //duplicate entry
            if ($e->getCode() == 23000) {
                return ['status' => false, 'message' => 'You have already started this quiz.', 'data' => null];
            }
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch quizez with questions
     */

    public function fetchQuizezWithQuestionsForStudent($id)
    {
        try {
            $quiz = Quiz::with('questions')->find($id);
            if (!$quiz) {
                return false;
            }
            foreach ($quiz->questions as $question) {
                unset($question->reason);
                $answers = json_decode($question->answers);
                //remove is_correct key from answers
                foreach ($answers as $answer) {
                    unset($answer->is_correct);
                }
                //shuffle($answers);
                $question->answers = $answers;
            }
            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }


    /**
     * fetch quizez,questions, answers and submitted data for student
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    // public function fetchQuizezWithStatus($id)
    // {
    //     try {

    //         $user = auth()->user();

    //         $quiz = Quiz::with(['questions' => function ($query) use ($user) {
    //             $query->with(['quiz_results' => function ($query) use ($user) {
    //                 $query->where('user_id', $user->id);
    //             }]);
    //         }])->find($id);
    //         if (!$quiz) {
    //             return false;
    //         }
    //         foreach ($quiz->questions as $question) {
    //             unset($question->reason);
    //             $answers = json_decode($question->answers);
    //             //remove is_correct key from answers
    //             foreach ($answers as $answer) {
    //                 unset($answer->is_correct);
    //             }
    //             //shuffle($answers);
    //             $question->answers = $answers;
    //         }
    //         return $quiz;
    //     } catch (\Exception $e) {
    //         ErrorLogger::logError($e);
    //         return false;
    //     }
    // }

    public function fetchQuizezWithStatus($id)
    {
        try {

            // $user = auth()->user();
            //get questions for quiz id = $id
            $questions = Question::where('quiz_id', $id)->get();


            foreach ($questions as $question) {
                $answers = json_decode($question->answers);
                $correctAnswers = array_filter($answers, function ($answer) {
                    return $answer->is_correct == 1;
                });
                $question->type = count($correctAnswers) > 1 ? APPConstants::QUESTION_TYPE_CHECKBOX : APPConstants::QUESTION_TYPE_RADIO;
                $question->answers = $answers;

                //unset is_correct key from answers
                foreach ($question->answers as $answer) {
                    unset($answer->is_correct);
                }
            }



            return $questions;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Quiz Answer
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function insertQuizAnswer($data)
    {
        try {
            $user = auth()->user();

            //get user's current attempt
            // $quiz_status = QuizStatus::where('user_id', $user->id)
            //     ->where('quiz_id', $data['quiz_id'])
            //     ->orderBy('attempts', 'desc')
            //     ->first();
            $question = Quiz::find($data['quiz_id'])->questions()->find($data['question_id']);
            if (!$question) {
                return ['status' => false, 'message' => 'Question not found.'];
            }
            $answers = json_decode($question->answers, true);

            if ($answers == null) {
                return ['status' => false, 'message' => 'No answers found for this question.'];
            }
            $correctAnswerIds = array_column(array_filter($answers, function ($answer) {
                return $answer['is_correct'] == 1;
            }), 'id');

            $givenAnswers = $data['answer'];

            $extraAnswers = array_diff($givenAnswers, $correctAnswerIds);
            $missingAnswers = array_diff($correctAnswerIds, $givenAnswers);

            if (empty($extraAnswers) && empty($missingAnswers)) {
                // echo "All correct answer IDs match with the given answers.";
                $data['is_correct'] = 1;
            } else {
                // echo "Not all correct answer IDs match with the given answers.";
                $data['is_correct'] = 0;
            }

            $quiz_result = QuizResult::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'quiz_id' => $data['quiz_id'],
                    'question_id' => $data['question_id'],
                    'attempt' => $data['attempt'],
                ],
                [
                    'answer' => $givenAnswers,
                    'is_correct' => $data['is_correct'],
                ]
            );

            return ['status' => true, 'message' => 'Answer submitted', 'data' => $quiz_result];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }

    /**
     * Complete Quiz
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function completeQuiz($id, $attempt)
    {
        try {

            $user = auth()->user();
            $marks =  $this->calculateMarks($user->id, $id, $attempt);

            $quiz_status = QuizStatus::where('user_id', $user->id)
                ->where('quiz_id', $id)
                ->where('attempts', $attempt)
                ->orderBy('attempts', 'desc')
                ->first();

            if (!$quiz_status) {
                return ['status' => false, 'message' => 'Quiz status not found for the given parameters'];
            }

            $quiz_status->is_finished = 1;
            $quiz_status->marks = $marks;
            $quiz_status->finished_at = now();
            $quiz_status->save();
            //get quiz name
            $quiz = Quiz::find($id);
            $quiz_status->quiz_name = $quiz->name;

            return ['status' => true, 'message' => 'Quiz completed', 'data' => $quiz_status];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch Quiz Results
     */

    public function fetchQuizResult($id)
    {
        try {
            $user = auth()->user();
            $quiz = Quiz::with(['quizResults' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])->find($id);

            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Calculate Marks
     */

    public function calculateMarks($user, $id, $attempt)
    {

        try {
            //where status is 1
            $quiz = Quiz::where('status', 1)
                //number of questions should not be 0 or null
                ->where('no_of_questions', '>', 0)
                ->with(['quizResults' => function ($query) use ($user) {
                    $query->where('user_id', $user);
                }])
                ->find($id);

            $totalQuestions = $quiz->no_of_questions;

            if ($totalQuestions == 0) {
                return false;
            }

            $correctAnswers = $quiz->quizResults->where('is_correct', 1)
                ->where('quiz_id', $id)
                ->where('attempt', $attempt)
                ->count();

            $marks = ($correctAnswers / $totalQuestions) * 100;
            return $marks;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch Quiz Results
     */

    public function fetchQuizStatusByQuizId($id)
    {
        try {
            $user = auth()->user();
            $quiz = QuizStatus::where('user_id', $user->id)
                ->where('quiz_id', $id)
                ->where('is_finished', 1)
                ->orderBy('attempts', 'desc')
                ->get();
            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Assessment Form Data
     */

    public function assessmentFormData($data)
    {
        try {

            $quiz_status_id = $data['status_id'];
            $user_id = $data['user_id'];
            $quiz = QuizStatus::where('id', $quiz_status_id)
                ->where('user_id', $user_id)
                ->where('is_finished', 1)
                ->first();

            if (!$quiz) {
                return false;
            }

            $quiz_id = $quiz->quiz_id;
            $quiz_status_attempts = $quiz->attempts;


            // $questions = Question::select('id', 'question', 'reason')->with(['quiz_results' => function ($query) use ($quiz_status_attempts) {
            //     $query->where('attempt', $quiz_status_attempts);
            //     $query->where('user_id', $user_id);
            // }])->where('quiz_id', $quiz_id)->get();

            $questions = Question::select('id', 'question', 'reason')
                ->with(['quiz_results' => function ($query) use ($quiz_status_attempts, $user_id) {
                    $query->where('attempt', $quiz_status_attempts);
                    $query->where('user_id', $user_id);
                }])
                ->where('quiz_id', $quiz_id)
                ->get();

            foreach ($questions as $question) {
                $question->is_correct = $question->quiz_results->where('is_correct', 1)->count();
                unset($question->quiz_results);
            }

            $questionsData = [
                'quiz_id' => $quiz_id,
                'attempts' => $quiz_status_attempts,
                'marks' => $quiz->marks,
                'feedback' => $quiz->feedback,
                'questions' => $questions,

            ];

            return $questionsData;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Question Wise Assessment
     */

    public function questionWiseAssessment($data)
    {
        try {
            $user = auth()->user();
            $quiz_id = $data['quiz_id'];
            $question_id = $data['question_id'];
            $attempt = $data['attempt'];

            $question = Question::select('id', 'question', 'reason', 'answers', 'image')->with(['quiz_results' => function ($query) use ($user, $attempt) {
                $query->where('user_id', $user->id);
                $query->where('attempt', $attempt);
            }])->where('quiz_id', $quiz_id)->where('id', $question_id)->first();

            if (!$question) {
                return false;
            }

            $answers = json_decode($question->answers);

            $correctAnswers = array_filter($answers, function ($answer) {
                return $answer->is_correct == 1;
            });
            $question->type = count($correctAnswers) > 1 ? APPConstants::QUESTION_TYPE_CHECKBOX : APPConstants::QUESTION_TYPE_RADIO;

            $question->answers = $answers;

            return $question;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Quiz Submission List
     */

    public function submissionList()
    {
        try {
            // $quiz = QuizStatus::with('quiz', 'user', 'quiz.course')->where('is_finished', 1)->get();

            $quiz = QuizStatus::with('quiz', 'user', 'quiz.course')
                ->where('is_finished', 1)
                ->whereHas('quiz', function ($query) {
                    $query->whereNull('deleted_at'); // Check if the quiz is not deleted
                })
                ->get();
            //set quiz name and course name
            $quiz = $quiz->map(function ($item) {
                $item->quiz_name = $item->quiz->name;
                $item->course_name = $item->quiz->course->name;
                // Check if user is not null
                if ($item->user) {
                    $first_name = $item->user->first_name ?? '';
                    $last_name = $item->user->last_name ?? '';
                    $item->user_name = $first_name . ' ' . $last_name;
                } else {
                    $item->user_name = ''; // or assign a default value like 'Unknown User'
                }
                // $item->user_name = $item->user->first_name ?? '' . ' ' . $item->user->last_name ?? '';
                unset($item->quiz);
                unset($item->user);
                return $item;
            });

            return $quiz;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Quiz Submission List
     */

    public function addFeedback($data)
    {

        try {
            $quiz_status = QuizStatus::find($data['quiz_status_id']);
            $quiz_status->feedback = $data['feedback'];
            $quiz_status->save();

            return $quiz_status;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * Quizzes By Course Id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function quizzesByCourseId($id)
    {
        try {
            $quizzes = Quiz::where('course_id', $id)
                ->where('status', AppConstants::ACTIVE)
                ->where('is_completed', 1)
                ->get();
            return $quizzes;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * studentQuestionWiseAssessment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function studentQuestionWiseAssessment($data)
    {
        try {
            $user = $data['user_id'];
            $quiz_id = $data['quiz_id'];
            $question_id = $data['question_id'];
            $attempt = $data['attempt'];

            $question = Question::select('id', 'question', 'reason', 'answers', 'image')->with(['quiz_results' => function ($query) use ($user, $attempt) {
                $query->where('user_id', $user);
                $query->where('attempt', $attempt);
            }])->where('quiz_id', $quiz_id)->where('id', $question_id)->first();

            if (!$question) {
                return false;
            }

            $answers = json_decode($question->answers);

            $correctAnswers = array_filter($answers, function ($answer) {
                return $answer->is_correct == 1;
            });
            $question->type = count($correctAnswers) > 1 ? APPConstants::QUESTION_TYPE_CHECKBOX : APPConstants::QUESTION_TYPE_RADIO;

            $question->answers = $answers;

            return $question;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //deleteAttempt
    public function deleteAttempt($data)
    {
        try {
            $user = $data['user_id'];
            $quiz_id = $data['quiz_id'];
            $attempt = $data['attempt'];

            $quiz_status = QuizStatus::where('user_id', $user)
                ->where('quiz_id', $quiz_id)
                ->where('attempts', $attempt)
                ->first();

            if (!$quiz_status) {
                return false;
            }

            $quiz_status->delete();

            //delete from quiz_results table also
            QuizResult::where('user_id', $user)
                ->where('quiz_id', $quiz_id)
                ->where('attempt', $attempt)
                ->delete();

            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //updateQuiz
    public function updateQuiz($data)
    {
        try {
            $user = auth()->user();
            $quiz = Quiz::find($data['quiz_id']);

            if (!$quiz) {
                return ['status' => false, 'message' => 'Quiz not found.'];
            }

            //check questions count equals to no_of_questions
            $questionsCount = Question::where('quiz_id', $quiz->id)->count();

            // // if $questionsCount not equals to 0 then return error
            // if ($questionsCount != 0) {
            //     return ['status' => false, 'message' => 'You cannot update quiz as questions are already added.'];
            // }

            $quiz->name = $data['name'];
            $quiz->no_of_questions = $data['no_of_questions'];
            $quiz->no_of_attempts = $data['no_of_attempts'];
            $quiz->duration = $data['duration'];
            $quiz->week = $data['week'];
            $quiz->description = $data['description'];
            $quiz->save();

            activity()
                ->causedBy($user->id)
                ->performedOn($user)
                ->withProperties([
                    'quiz_id' => $quiz->id,
                    'name' => $quiz->name,
                    'no_of_questions' => $quiz->no_of_questions,
                    'no_of_attempts' => $quiz->no_of_attempts,
                    'duration' => $quiz->duration,
                    'week' => $quiz->week,
                    'status' => $quiz->status,
                    'description' => $quiz->description
                ])
                ->log('quiz_update');
            return ['status' => true, 'message' => 'Quize updated successfully.', 'data' => $quiz];
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Quiz update failed.'];
        }
    }

    //updateMarks
    public function updateMarks()
    {
        try {
            //get all the quiz results
            $quizResults = QuizResult::all();
            foreach ($quizResults as $quizResult) {
                $quiz_id = $quizResult->quiz_id;
                $user_id = $quizResult->user_id;
                $attempt = $quizResult->attempt;

                //get correct answer
                $correctAnswers = Question::where('id', $quizResult->question_id)
                    ->where('quiz_id', $quiz_id)->first();

                $correctAnswer = json_decode($correctAnswers->answers);

                //get is_correct 1 id
                $correct = array_filter($correctAnswer, function ($answer) {
                    //is correct 1 's id
                    return $answer->is_correct == 1;
                });

                //get id from correct answer
                $correct = array_column($correct, 'id');

                //given answer
                $givenAnswers = $quizResult->answer;
                echo '<br>';
                print_r($correctAnswers);
                echo '<br>';
                echo $givenAnswers[0];

                echo '---';
                echo $correct[0];
                echo '<br>';
                if ($givenAnswers[0] == $correct[0]) {
                    $quizResult->is_correct = 1;
                } else {
                    $quizResult->is_correct = 0;
                }
                // $quizResult->is_correct = $givenAnswers == $correct ? 1 : 0;
                $quizResult->save();
            }
            return true;
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return false;
        }
    }
}
