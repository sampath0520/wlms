<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Orientation
 * 
 * @property int $id
 * @property int $course_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $message
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Course $course
 *
 * @package App\Models
 */
class Orientation extends Model
{
	protected $table = 'orientations';

	protected $casts = [
		'course_id' => 'int'
	];

	protected $fillable = [
		'course_id',
		'name',
		'email',
		'phone',
		'message'
	];

	public function course()
	{
		return $this->belongsTo(Course::class);
	}
}
