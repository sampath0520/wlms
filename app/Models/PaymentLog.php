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
class PaymentLog extends Model
{
    protected $table = 'payment_log';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'currency_id',
        'price',
        'promo_code_id',
        'user_id',
        'promo_discount_type',
        'promo_discount',
        'currency_id',
        'currency',
        'is_one_time_promo',
        'promo_code'
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
