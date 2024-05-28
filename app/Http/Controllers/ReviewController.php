<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\AddReviewByAdminRequest;
use App\Http\Requests\ApproveReviewRequest;
use App\Http\Requests\FullReviewListRequest;
use App\Services\CourseRatingService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $courseRating;
    public function __construct(CourseRatingService $courseRating)
    {
        $this->middleware('auth:api');
        $this->courseRating = $courseRating;
    }

    /**
     * Review list
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchReviews($course_id)
    {
        $reviews = $this->courseRating->fetchReviews($course_id);
        if ($reviews) {
            return ResponseHelper::success(trans('messages.record_fetched'), $reviews);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }


    /**
     * unapproved List
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function unapprovedList()
    {
        $reviews = $this->courseRating->unapprovedReviews();
        if ($reviews) {
            return ResponseHelper::success(trans('messages.record_fetched'), $reviews);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * addReviewByAdmin
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function addReviewByAdmin(AddReviewByAdminRequest $request)
    {
        $validated = $request->validated();
        $reviews = $this->courseRating->addReviewByAdmin($validated);
        if ($reviews['status'] == true) {
            return ResponseHelper::success('Review added successfully.');
        } else {
            return ResponseHelper::error($reviews['message']);
        }
    }

    /**
     * approveReviewByAdmin
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function approveReview(ApproveReviewRequest $request)
    {
        $validated = $request->validated();
        $reviews = $this->courseRating->approveReview($validated);
        if ($reviews) {
            return ResponseHelper::success('Review approved successfully', $reviews);
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * fullList
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fullList(FullReviewListRequest $request)
    {
        $validated = $request->validated();
        $reviews = $this->courseRating->fullList($validated);
        if ($reviews) {
            return ResponseHelper::success(trans('messages.record_fetched'), $reviews);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * deleteReviewByAdmin
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function deleteReview($id)
    {
        $reviews = $this->courseRating->deleteReview($id);
        if ($reviews) {
            return ResponseHelper::success('Review deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }
}
