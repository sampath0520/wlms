<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


/**
 * Class Announcement
 *
 * @property int $id
 * @property string $title
 * @property string|null $message
 * @property string|null $image
 * @property string|null $material_link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated
 * @property int $course_type
 *
 * @package App\Models
 */
class Announcement extends Model
{
    protected $table = 'announcements';
    public $timestamps = true;

    protected $casts = [
        'updated' => 'datetime',
        'course_type' => 'int'
    ];

    protected $fillable = [
        'title',
        'message',
        'image',
        'material_link',
        'updated',
        'course_type'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_type');
    }
}
