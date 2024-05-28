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
class CoursesCurrency extends Model
{
    protected $table = 'courses_currencies';
    public $timestamps = true;

    protected $casts = [
        'updated' => 'datetime',
        'course_id' => 'int'
    ];
    protected $fillable = [
        'course_id',
        'currency_id',
        'price',
        'other_price',
        'is_default',
        'created_date',
        'updated_date',
        'deleted_at',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
