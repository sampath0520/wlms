<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ResponseHelper;
use App\Http\Requests\OrientationCreateRequest;
use App\Mail\OrientationRequestEmail;
use App\Services\OrientationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrientationController extends Controller
{
    protected $orientationService;
    public function __construct(OrientationService $orientationService)
    {
        // $this->middleware('auth:api');
        $this->orientationService = $orientationService;
    }


    /**
     * create orientation
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * 
     */

    public function createOrientation(OrientationCreateRequest $request)
    {
        $validated = $request->validated();
        $response = $this->orientationService->createOrientation($validated);
        if ($response) {
            //send email
            $emailData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'message' => $validated['message'],
            ];

            $email =   Mail::to(AppConstants::ORIENTATION_EMAIL)->send(new OrientationRequestEmail($emailData));

            return ResponseHelper::success(trans('messages.record_created'));
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * get orientation
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * 
     */

    public function fetchOrientations()
    {
        $orientations = $this->orientationService->fetchOrientations();
        if ($orientations) {
            return ResponseHelper::success(trans('messages.record_fetched'), $orientations);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }
}
