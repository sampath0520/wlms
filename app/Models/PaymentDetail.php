<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PaymentDetail
 *
 * @property int $id
 * @property int $user_id
 * @property float $price
 * @property int $course_id
 * @property bool $is_manual_payment
 * @property Carbon $created_at
 *
 * @property User $user
 * @property Course $course
 *
 * @package App\Models
 */
class PaymentDetail extends Model
{
    protected $table = 'payment_details';

    public $timestamps = true;

    protected $casts = [
        'user_id' => 'int',
        'price' => 'float',
        'course_id' => 'int',
        'is_manual_payment' => 'integer',
        'payment_trans_id' => 'string',
        'created_at' => 'datetime:d/m/Y' . ' ' . 'H:i:s',
    ];

    protected $fillable = [
        'user_id',
        'price',
        'course_id',
        'is_manual_payment',
        'payment_trans_id',
        'course_currency_id',
        'promo_code_id',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseCurrency()
    {
        return $this->belongsTo(Currency::class, 'course_currency_id');
    }
}
