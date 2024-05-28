<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ForumReply
 * 
 * @property int $id
 * @property int $forum_id
 * @property string $reply
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Forum $forum
 *
 * @package App\Models
 */
class ForumReply extends Model
{
	protected $table = 'forum_replies';

	protected $casts = [
		'forum_id' => 'int',
		'created_by' => 'int'
	];

	protected $fillable = [
		'forum_id',
		'reply',
		'images',
		'created_by'
	];

	public function forum()
	{
		return $this->belongsTo(Forum::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}
