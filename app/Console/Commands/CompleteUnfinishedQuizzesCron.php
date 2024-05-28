<?php

namespace App\Console\Commands;

use App\Models\QuizStatus;
use App\Services\QuizService;
use Illuminate\Console\Command;

class CompleteUnfinishedQuizzesCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'completeUnfinishedQuizzes:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete unfinished quizzes';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $quizService;
    public function __construct(QuizService $quizService)
    {
        parent::__construct();
        $this->quizService = $quizService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Cron Job (completeUnfinishedQuizzes) running at " . now());

        // Get all unfinished quizzes from quiz_status table with quizzes duration

        $unfinishedQuizzes = QuizStatus::with('quiz')->where('is_finished', 0)->get();

        // $unfinishedQuizzes = QuizStatus::with('quiz')->where('is_finished', 1)->where('marks', null)->get();

        //check quiz duration and compare with quiz_status table started_at column
        //if quiz duration is equal or greater than quiz_status table started_at column then update quiz_status table is_finished column to 1 and remark as system
        foreach ($unfinishedQuizzes as $unfinishedQuiz) {

            $user_id = $unfinishedQuiz->user_id;
            $quiz_id = $unfinishedQuiz->quiz_id;
            $attempt = $unfinishedQuiz->attempts;

            // call to calculateMarks function from QuizService
            $marks =  $this->quizService->calculateMarks($user_id, $quiz_id, $attempt);

            $quizDuration = $unfinishedQuiz->quiz->duration;

            $startedAt = $unfinishedQuiz->started_at;
            $now = now();
            $diff = $now->diffInMinutes($startedAt);

            if ($diff >= $quizDuration) {
                $unfinishedQuiz['is_finished'] = 1;
                $unfinishedQuiz['finished_at'] = $now;
                $unfinishedQuiz['remark'] = 'system';
                $unfinishedQuiz['marks'] = $marks;
                $unfinishedQuiz->save();
            }
        }

        $this->info('completeUnfinishedQuizzes:cron Command Run successfully!');
    }
}
