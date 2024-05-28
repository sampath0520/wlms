<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Http\Requests\AddWebinarRequest;
use App\Http\Requests\DeleteWebinarRequest;
use App\Http\Requests\UpdateWebinarRequest;
use App\Http\Requests\WebinarActivateRequest;
use App\Http\Requests\WebinarCompletedRequest;
use App\Services\WebinarService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebinarController extends Controller
{
    protected $webinarService;

    public function __construct(WebinarService $webinarService)
    {
        // $this->middleware('auth');
        $this->webinarService = $webinarService;
    }

    public function createWebinar(AddWebinarRequest $request)
    {
        $validated = $request->validated();
        $meeting_data =  json_encode($validated);

        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
        $redirectUri = AppConstants::ZOOM_REDIRECT_URI;

        $authorizationUrl = 'https://zoom.us/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $meeting_data,
        ]);

        if (!$authorizationUrl) {
            return ResponseHelper::error(trans('messages.authentication_failed'));
        }

        if ($authorizationUrl) {
            return ResponseHelper::success(trans('messages.authentication_success'), $authorizationUrl);
        } else {
            return ResponseHelper::error(trans('messages.authentication_failed'));
        }
    }

    public function handleZoomCallback(Request $request)
    {

        $stateData = $request->query('state');

        // $data = json_decode($stateData);
        // $date = $data->date;
        // $time = $data->time;
        // $time_ext = $data->time_ext;
        // $duration = $data->duration;
        // $course = $data->course;

        // // Parse time
        // list($hour, $minute) = explode(':', $time);

        // // Adjust for PM if needed
        // if ($time_ext === 'PM' && $hour < 12) {
        //     $hour += 12;
        // }

        // // Format the date and time in ISO 8601 format
        // $dateTime = sprintf('%sT%02d:%s:00Z', $date, $hour, $minute);

        $code = $request->query('code'); // Get the authorization code

        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
        $redirectUri = AppConstants::ZOOM_REDIRECT_URI;


        $tokenResponse = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        ])->post('https://zoom.us/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        if ($tokenResponse->successful()) {

            //Save the access token and refresh tokens for future use
            $this->webinarService->saveZoomToken($tokenResponse->json());
            return  $this->createZoomWebinar($stateData);


            // $accessToken = $tokenResponse->json('access_token');

            //******************WEBINAR********************* */

            // $webinarResponse = Http::withToken($accessToken)->post('https://api.zoom.us/v2/users/me/webinars', [
            //     'topic' => 'My Webinar',
            //     'type' => 5,
            //     'start_time' => $dateTime,
            //     'duration' => $duration,
            // ]);

            // $responseBody = $webinarResponse->json();

            //******************WEBINAR********************* */

            //******************MEETING********************* */
            // $client = new Client();
            // $response = $client->request('POST', 'https://api.zoom.us/v2/users/me/meetings', [
            //     'headers' => [
            //         'Authorization' => 'Bearer ' . $accessToken,
            //         'Content-Type' => 'application/json',
            //         'Accept' => 'application/json',
            //     ],
            //     'json' => [
            //         'topic' => 'Meeting',
            //         'type' => 2,
            //         'start_time' => $dateTime,
            //         'duration' => $duration,
            //     ],
            // ]);
            // if ($response->getStatusCode() != 201) {
            //     return ResponseHelper::error(trans('messages.record_creation_failed'));
            // }

            // $responseBody = json_decode($response->getBody()->getContents());

            // $webinar = $this->webinarService->createWebinar($course, $responseBody);
            // if ($webinar) {
            //     return ResponseHelper::success(trans('messages.record_created'));
            //     // return redirect()->away('http://localhost:3000/admin-webinar');
            // } else {
            //     return ResponseHelper::error(trans('messages.record_creation_failed'));
            // }


            //******************MEETING********************* */


        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Get all webinars
     */
    public function getAllWebinars()
    {
        $webinars = $this->webinarService->getAllWebinars();
        if ($webinars) {
            return ResponseHelper::success(trans('messages.record_fetch'), $webinars);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Activate Deactivate Webinar
     */
    public function activateDeactivateWebinar(WebinarActivateRequest $request)
    {
        $validated = $request->validated();
        $webinars = $this->webinarService->activateDeactivateWebinar($validated);
        if ($webinars) {
            return ResponseHelper::success(trans('messages.record_updated'));
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Delete Webinar
     */
    public function deleteWebinar($id)
    {
        try {
            //check meeting id is in the database
            $webinar = $this->webinarService->getWebinarById($id);

            if (!$webinar || !$webinar->meeting_id) {
                return ResponseHelper::error(trans('messages.data_not_found'));
            }

            // $clientId = AppConstants::ZOOM_CLIENT_ID;
            // $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
            // // Create a Guzzle HTTP client
            // $client = new Client();

            // Construct the URL for the Delete Meeting API endpoint
            // $url = "https://api.zoom.us/v2/meetings/{$webinar->meeting_id}";
            // DB::beginTransaction();
            // try {
            //     $response = $client->delete($url, [
            //         'headers' => [
            //             'Authorization' => 'Bearer ' . $this->webinarService->checkToken($clientId, $clientSecret),
            //         ],
            //     ]);

            // if ($response->getStatusCode() === 204) {
            if ($webinar) {
                //delete webinar from database
                $this->webinarService->deleteWebinar($id);
                // DB::commit();
                return ResponseHelper::success(trans('messages.delete_success'));
            } else {
                // $responseData = json_decode($response->getBody(), true);
                // DB::rollback();
                return ResponseHelper::error(trans('messages.delete_failed'));
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            // DB::rollback();
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Get Webinar By Id
     */
    public function fetchWebinarById($id)
    {
        $webinars = $this->webinarService->getWebinarById($id);
        if ($webinars) {
            return ResponseHelper::success(trans('messages.record_fetch'), $webinars);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Update Webinar By Id
     */
    public function updateWebinarById(UpdateWebinarRequest $request)
    {
        $validated = $request->validated();
        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;

        $date = $validated['date'];
        $time = $validated['time'];
        $time_ext = $validated['time_ext'];

        // Parse time
        list($hour, $minute) = explode(':', $time);

        // Adjust for PM if needed
        if ($time_ext === 'PM' && $hour < 12) {
            $hour += 12;
        }

        // Format the date and time in ISO 8601 format
        $dateTime = sprintf('%sT%02d:%s:00Z', $date, $hour, $minute);

        $meetingData = [
            'topic' => 'Meeting',
            'type' => 2,
            'start_time' => $dateTime,
            'duration' => $validated['duration'],
        ];
        $webinar = $this->webinarService->getWebinarById($validated['id']);
        if (!$webinar || !$webinar->meeting_id) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }

        // Create a Guzzle HTTP client
        $client = new Client();

        // Construct the URL for the Update Meeting API endpoint
        $url = "https://api.zoom.us/v2/meetings/{$webinar->meeting_id}";
        DB::beginTransaction();
        try {
            // Make a PATCH request to update the meeting
            $response = $client->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->webinarService->checkToken($clientId, $clientSecret),
                ],
                'json' => $meetingData, // The data you want to update the meeting with
            ]);


            // Check the response status code
            if ($response instanceof Response && $response->getStatusCode() === 204) {
                //update webinar from database
                $webinar = $this->webinarService->updateWebinarById($validated);
                if ($webinar) {
                    DB::commit(); // Commit the transaction on success
                    return ResponseHelper::success(trans('messages.record_updated'));
                } else {
                    return ResponseHelper::error(trans('messages.record_update_failed'));
                }
            } else {
                DB::rollback(); // Rollback the transaction on failure
                return ResponseHelper::error(trans('messages.record_update_failed'));
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            DB::rollback();
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * create Webinar
     */
    public function createZoomWebinar($stateData)
    {
        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
        $data = json_decode($stateData);

        $date = $data->date;
        $time = $data->time;
        $time_ext = $data->time_ext;
        $duration = $data->duration;
        // $course = $data->course;
        $course = 0;

        // Parse time
        list($hour, $minute) = explode(':', $time);

        // Adjust for PM if needed
        if ($time_ext === 'PM' && $hour < 12) {
            $hour += 12;
        }

        // Format the date and time in ISO 8601 format
        $dateTime = sprintf('%sT%02d:%s:00Z', $date, $hour, $minute);
        //******************MEETING********************* */
        $client = new Client();
        $response = $client->request('POST', 'https://api.zoom.us/v2/users/me/meetings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->webinarService->checkToken($clientId, $clientSecret),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'topic' => 'Meeting',
                'type' => 2,
                'start_time' => $dateTime,
                'duration' => $duration,
            ],
        ]);
        if ($response->getStatusCode() != 201) {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }

        $responseBody = json_decode($response->getBody()->getContents());

        $webinar = $this->webinarService->createWebinar($course, $responseBody, $data);
        if ($webinar) {
            // return ResponseHelper::success(trans('messages.record_created'));
            //ZOOM_MEETING_SUCCESS_URL
            return redirect()->away(APPConstants::ZOOM_MEETING_REDIRECT_URL . '/' . APPConstants::ACTIVE);
        } else {
            // return ResponseHelper::error(trans('messages.record_creation_failed'));
            return redirect()->away(APPConstants::ZOOM_MEETING_REDIRECT_URL . '/' . APPConstants::INACTIVE);
        }

        //******************MEETING********************* */
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * completeWebinar
     */
    public function completeWebinar(WebinarCompletedRequest $request)
    {
        $validated = $request->validated();
        $webinar = $this->webinarService->completeWebinar($validated);
        if ($webinar) {
            return ResponseHelper::success('Webinar completed successfully');
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * Get all webinars for logged in user
     */
    public function getAllWebinarsForUser()
    {
        $webinar = $this->webinarService->getAllWebinarsForUser();

        if ($webinar['status']) {
            return ResponseHelper::success('Webinar fetched successfully', $webinar['data']);
        } else {
            return ResponseHelper::error('Webinar fetch failed');
        }
    }
}
