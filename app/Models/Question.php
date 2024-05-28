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
 * Class Question
 * 
 * @property int $id
 * @property int $quiz_id
 * @property string $question
 * @property string|null $image
 * @property string|null $reason
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property string $answers
 * 
 * @property Quiz $quiz
 * @property Collection|QuizResult[] $quiz_results
 *
 * @package App\Models
 */
class Question extends Model
{
	protected $table = 'questions';
	use SoftDeletes;
	
	protected $casts = [
		'quiz_id' => 'int'
	];

	protected $fillable = [
		'quiz_id',
		'question',
		'image',
		'reason',
		'answers'
	];

	public function quiz()
	{
		return $this->belongsTo(Quiz::class);
	}

	// public function answers()
	// {
	// 	return $this->hasMany(Answer::class);
	// }

	public function quiz_results()
	{
		return $this->hasMany(QuizResult::class);
	}
}
