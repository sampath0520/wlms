<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_active
 * @property bool|null $gender
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|PaymentDetail[] $payment_details
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasRoles, HasApiTokens, Notifiable;
    protected $table = 'users';
    public $timestamps = true;
    use SoftDeletes;

    // public function getCreatedAtAttribute($value)
    // {
    //     return Carbon::parse($value)->timezone(env('APP_TIMEZONE'))->format('d/m/Y' . ' ' . 'H:i:s');
    // }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'integer',
        'gender' => 'integer',
        'created_at' => 'datetime:d/m/Y' . ' ' . 'H:i:s',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'email_verified_at',
        'password',
        'remember_token',
        'is_active',
        'gender'
    ];

    public function payment_details()
    {
        return $this->hasMany(PaymentDetail::class);
    }
}
