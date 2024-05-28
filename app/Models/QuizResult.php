<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QuizResult
 * 
 * @property int $id
 * @property int $user_id
 * @property bool $attempt
 * @property int $question_id
 * @property string $answers
 * @property int $marks
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property int $created_by
 * 
 * @property Question $question
 *
 * @package App\Models
 */
class QuizResult extends Model
{
	protected $table = 'quiz_results';

	protected $casts = [
		'user_id' => 'int',
		'quiz_id' => 'int',
		'attempt' => 'int',
		'question_id' => 'int',
		'is_correct' => 'int',
		'answer' => 'json'
	];

	protected $fillable = [
		'user_id',
		'quiz_id',
		'attempt',
		'question_id',
		'answer',
		'is_correct'
	];

	public function question()
	{
		return $this->belongsTo(Question::class);
	}
}
