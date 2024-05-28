<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VideoAccess
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @package App\Models
 */
class VideoAccess extends Model
{
    protected $table = 'video_accesses';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime'
    ];

    protected $fillable = [
        'token',
        'is_used',
        'created_at',
        'video_url',
        'duration',
    ];
}
