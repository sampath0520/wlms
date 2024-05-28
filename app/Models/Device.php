<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Device
 * 
 * @property int $id
 * @property Carbon|null $created_at
 * @package App\Models
 */
class Device extends Model
{
	protected $table = 'devices';

	public $timestamps = false;

	protected $casts = [
		'created_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'device_id',
		'created_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
