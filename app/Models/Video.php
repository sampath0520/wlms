<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Video
 * 
 * @property int $id
 * @property string $title
 * @property string $link
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Video extends Model
{
	protected $table = 'videos';

	use SoftDeletes;

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'title',
		'link',
		'thumbnail',
		'status'
	];
}
