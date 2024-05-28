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
 * Class Course
 *
 * @property int $id
 * @property string $name
 * @property float $price
 * @property int $duration
 * @property string|null $lecturer
 * @property string|null $description
 * @property string|null $course_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|CourseContent[] $course_contents
 * @property Collection|Orientation[] $orientations
 * @property Collection|PaymentDetail[] $payment_details
 * @property Collection|Quiz[] $quizzes
 * @property Collection|Webinar[] $webinars
 *
 * @package App\Models
 */
class Course extends Model
{
    protected $table = 'courses';

    use SoftDeletes;

    protected $casts = [
        'price' => 'float',
        'duration' => 'int'
    ];

    protected $fillable = [
        'name',
        // 'price',
        'duration',
        'lecturer',
        'description',
        'course_image',
        'is_active',
        'is_free',
        'is_invisible'
        // 'is_top_banner'
    ];

    public function course_contents()
    {
        return $this->hasMany(CourseContent::class);
    }

    public function orientations()
    {
        return $this->hasMany(Orientation::class);
    }

    public function payment_details()
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function webinars()
    {
        return $this->hasMany(Webinar::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_courses');
    }

    public function courseRating()
    {
        return $this->hasMany(CourseRating::class);
    }

    //forums
    public function forums()
    {
        return $this->hasMany(Forum::class);
    }

    //course currencies
    public function courseCurrencies()
    {
        return $this->hasMany(CoursesCurrency::class);
    }
}
