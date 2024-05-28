<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Quiz
 *
 * @property int $id
 * @property int $course_id
 * @property string $name
 * @property int $no_of_questions
 * @property int $no_of_attempts
 * @property string|null $duration
 * @property int $week
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Course $course
 * @property Collection|Question[] $questions
 *
 * @package App\Models
 */
class Quiz extends Model
{
    protected $table = 'quizzes';
    use SoftDeletes;
    public $timestamps = true;

    protected $casts = [
        'course_id' => 'int',
        'no_of_questions' => 'int',
        'no_of_attempts' => 'int',
        'status' => 'int'
    ];

    protected $fillable = [
        'course_id',
        'name',
        'no_of_questions',
        'no_of_attempts',
        'duration',
        'week',
        'description',
        'status'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function quizStatus()
    {
        return $this->hasMany(QuizStatus::class, 'quiz_id', 'id');
    }

    public function quizResults()
    {
        return $this->hasMany(QuizResult::class, 'quiz_id', 'id');
    }
}
