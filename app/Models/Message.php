<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Message
 * 
 * @property int $id
 * @property string $message
 * @property int $user_from
 * @property int $to_user
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class Message extends Model
{
	protected $table = 'messages';

	protected $casts = [
		'user_from' => 'int',
		'to_user' => 'int'
	];

	protected $fillable = [
		'message',
		'user_from',
		'to_user',
		'attachment'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'user_from');
	}
}
