<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\CourseRating;

class CourseRatingService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * create forum
     */

    public function fetchReviews($course_id)
    {
        try {
            $reviews = CourseRating::with('users:id,first_name,last_name,profile_image')
                ->where('course_id', $course_id)
                ->where('is_approved', AppConstants::ACTIVE)
                ->orderBy('created_at', 'desc')
                ->get();

            //total review count
            $total_reviews = CourseRating::where('course_id', $course_id)->where('is_approved', AppConstants::ACTIVE)->count();

            //calculate average rating and calculate it to 5 star rating
            $average_rating = CourseRating::where('course_id', $course_id)->where('is_approved', AppConstants::ACTIVE)->avg('rating');


            return [
                'total_reviews' => $total_reviews,
                'average_rating' => $average_rating ? round($average_rating) : 0,
                'reviews' => $reviews
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * unapproved Reviews
     */
    public function unapprovedReviews()
    {
        try {
            //course rating with course and user details
            $reviews = CourseRating::with('courses:id,name', 'users:id,first_name,last_name,profile_image')
                ->where('is_approved', AppConstants::INACTIVE)
                ->orderBy('created_at', 'desc')
                ->get();


            return $reviews;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Add Review By Admin
     */
    public function addReviewByAdmin($data)
    {
        try {
            $review = CourseRating::create([
                'user_id' => $data['user_id'],
                'course_id' => $data['course_id'],
                'rating' => $data['rating'],
                'feedback' => $data['feedback'],
                'is_approved' => AppConstants::ACTIVE
            ]);

            return ['status' => true, 'message' => 'Review added successfully'];
        } catch (\Exception $e) {
            //duplicate entry
            if ($e->getCode() == 23000) {
                return ['status' => false, 'message' => 'Student have already added review for this course'];
            }
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Approve Review
     */
    public function approveReview($data)
    {

        try {
            $review = CourseRating::where('id', $data['rating_id'])->update([
                'is_approved' => $data['is_approved']
            ]);

            return $review;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fullList
     */
    public function fullList($data)
    {
        try {
            $reviews = CourseRating::with('courses:id,name', 'users:id,first_name,last_name,profile_image')

                //if course id is not all then filter by course id
                ->when($data['course_id'] != 'All', function ($query) use ($data) {
                    return $query->where('course_id', $data['course_id']);
                })

                //if approved status is not all then filter by approved status
                ->when($data['approved_status'] != 'All', function ($query) use ($data) {
                    return $query->where('is_approved', $data['approved_status']);
                })

                ->orderBy('created_at', 'desc')
                ->get();

            return $reviews;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //delete review
    public function deleteReview($id)
    {
        try {
            $review = CourseRating::find($id);
            if (!$review) {
                return false;
            }
            $review->delete();
            return $review;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
