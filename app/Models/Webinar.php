<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Webinar
 *
 * @property int $id
 * @property int $course_id
 * @property string $name
 * @property string $link
 * @property int|null $duration
 * @property Carbon $date
 * @property Carbon $time
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * @property Course $course
 *
 * @package App\Models
 */
class Webinar extends Model
{
    protected $table = 'webinars';

    protected $casts = [
        'course_id' => 'int',
        'duration' => 'int',
        'date' => 'date',
        'status' => 'int'
    ];

    protected $fillable = [
        'course_id',
        'name',
        'join_url',
        'start_url',
        'duration',
        'date',
        'time',
        'time_ext',
        'meeting_id',
        'meeting_uuid',
        'meeting_password',
        'timezone',
        'status'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
