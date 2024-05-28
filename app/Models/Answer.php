<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Answer
 * 
 * @property int $id
 * @property int $question_id
 * @property string $answer
 * @property bool $is_true
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Question $question
 *
 * @package App\Models
 */
class Answer extends Model
{
	protected $table = 'answers';

	protected $casts = [
		'question_id' => 'int',
		'is_true' => 'bool'
	];

	protected $fillable = [
		'question_id',
		'answer',
		'is_true'

	];

	public function question()
	{
		return $this->belongsTo(Question::class);
	}
}
