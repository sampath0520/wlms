<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ForumParticipant
 * 
 * @property int $id
 * @property int $forum_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Forum $forum
 *
 * @package App\Models
 */
class ForumParticipant extends Model
{
	protected $table = 'forum_participants';

	protected $casts = [
		'forum_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'forum_id',
		'user_id'
	];

	public function forum()
	{
		return $this->belongsTo(Forum::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
