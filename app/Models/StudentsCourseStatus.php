<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StudentsCourseStatus
 * 
 * @property int $id
 * @property int $course_id
 * @property int $week
 * @property string $content
 * @property string|null $content_link
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Course $course
 *
 * @package App\Models
 */
class StudentsCourseStatus extends Model
{
	protected $table = 'students_course_status';

	protected $casts = [
		'user_id' => 'int',
		'course_contents_id' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'user_id',
		'course_contents_id',
		'status'
	];

	public function CourseContent()
	{
		return $this->belongsTo(CourseContent::class, 'course_contents_id');
	}
}
