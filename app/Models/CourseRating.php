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
class CourseRating extends Model
{
	protected $table = 'course_ratings';

	protected $fillable = [
		'course_id',
		'user_id',
		'rating',
		'feedback',
		'is_approved'
	];


	public function courses()
	{
		return $this->belongsTo(Course::class, 'course_id');
	}
	public function users()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
