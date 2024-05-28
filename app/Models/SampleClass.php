<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Course Rating
 */
class SampleClass extends Model
{
	protected $table = 'sample_classes';

	protected $fillable = [
		'id',
		'title',
		'sub_title',
		'status',
		'link',
		'thumbnail',
	];
}
