<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Forum
 *
 * @property int $id
 * @property string $name
 * @property string|null $image
 * @property string|null $description
 * @property int $created_by
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|ForumParticipant[] $forum_participants
 * @property Collection|ForumReply[] $forum_replies
 *
 * @package App\Models
 */
class Forum extends Model
{
    protected $table = 'forums';
    use SoftDeletes;

    protected $casts = [
        'created_by' => 'int',
        'status' => 'int'
    ];

    protected $fillable = [
        'course_id',
        'name',
        'image',
        'description',
        'created_by',
        'status'
    ];

    public function forum_participants()
    {
        return $this->hasMany(ForumParticipant::class);
    }

    public function forum_replies()
    {
        return $this->hasMany(ForumReply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
