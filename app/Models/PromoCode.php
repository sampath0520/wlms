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
class PromoCode extends Model
{
    protected $table = 'promo_codes';

    protected $fillable = [
        'promo_code',
        'code_type',
        'discount_type',
        'user_id',
        'start_date',
        'end_date',
        // 'course_id',
        'is_one_time',
        'status',
    ];

    // public function courses()
    // {
    //     return $this->belongsTo(Course::class, 'course_id', 'id');
    // }

    public function promoCodeDiscount()
    {
        return $this->hasMany(PromoCodeDiscount::class, 'promo_code_id', 'id');
    }
}
