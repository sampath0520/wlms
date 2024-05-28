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
 *
 * @package App\Models
 */
class ZoomToken extends Model
{
	protected $table = 'zoom_tokens';
	public $timestamps = true;

	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime'
	];

	protected $fillable = [
		'access_token',
		'token_type',
		'refresh_token',
		'expires_in',
		'scope',
		'created_at',
		'updated_at'
	];
}
