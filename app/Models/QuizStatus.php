<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QuizStatus
 * 
 *
 * @package App\Models
 */
class QuizStatus extends Model
{
	protected $table = 'quiz_status';

	protected $casts = [
		'user_id' => 'int',
		'quiz_id' => 'int',
		'attempts' => 'int',
	];

	protected $fillable = [
		'user_id',
		'quiz_id',
		'attempts',
		'started_at',
		'is_started',
		'is_finished',
		'finished_at',
		'remark'
	];

	public function quiz()
	{
		return $this->belongsTo(Quiz::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function questions()
	{
		return $this->hasMany(Question::class, 'quiz_id', 'quiz_id');
	}
	
}
