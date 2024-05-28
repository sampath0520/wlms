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
class Currency extends Model
{
    protected $table = 'currencies';

    protected $fillable = [
        'currency',
        'currency_name'
    ];

    public function courses()
    {
        return $this->hasMany(CoursesCurrency::class);
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function promoCodeDiscount()
    {
        return $this->hasMany(PromoCodeDiscount::class);
    }
}
