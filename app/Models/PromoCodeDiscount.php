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
class PromoCodeDiscount extends Model
{
    protected $table = 'promo_code_discounts';

    protected $fillable = [
        'promo_code_id',
        'currency_id',
        'discount',
        // 'discount_type',
        'status',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
