<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CourseContent
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
class CourseContent extends Model
{
    protected $table = 'course_contents';

    protected $casts = [
        'course_id' => 'int',
        'day' => 'int',
        'status' => 'int',
        'is_locked' => 'int'
    ];

    protected $fillable = [
        'course_id',
        'week',
        'content',
        'content_link',
        'duration',
        'day',
        'status',
        'content_type',
        'is_locked'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function studentsCourseStatus()
    {
        //course_contents.id = students_course_status.course_contents_id
        return $this->hasMany(StudentsCourseStatus::class, 'course_contents_id', 'id');
    }
}
