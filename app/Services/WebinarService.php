<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\PaymentDetail;
use App\Models\Webinar;
use App\Models\ZoomToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebinarService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create Webinar
     */
    public function createWebinar($course, $request, $data)
    {

        // dd($request);
        // //get date and time separately
        // $date_time =  $request->start_time;
        // //get date and time separately
        // $date = substr($date_time, 0, 10);
        // $time = substr($date_time, 11, 5);
        // // Extract the hour and minute from the time
        // list($hour, $minute) = explode(':', $time);

        // // Determine if it's AM or PM
        // $time_ext = ($hour >= 12) ? 'PM' : 'AM';
        try {
            $webinar =   Webinar::create([
                'course_id' => $course,
                'name' => 'test',
                'date' => $data->date,
                'time' => $data->time,
                'time_ext' => $data->time_ext,
                'duration' => $request->duration,
                'course_id' => $course,
                'join_url' => $request->join_url,
                'start_url' => $request->start_url,
                'status' => 1,
                'meeting_id' => $request->id,
                'meeting_uuid' => $request->uuid,
                'meeting_password' => $request->password,
                'timezone' => $request->timezone,
                'created_at' => $request->created_at
            ]);
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Get All Webinars
     */
    public function getAllWebinars()
    {
        try {
            $webinars = Webinar::with('course')->orderBy('id', 'desc')->get();
            return $webinars;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Activate and deactivate Webinar
     */

    public function activateDeactivateWebinar($request)
    {
        $id = $request['id'];
        $status = $request['status'];

        try {
            $webinar = Webinar::find($id);
            $webinar->status = $status;
            $webinar->save();
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //saveZoomToken
    public function saveZoomToken($request)
    {
        try {
            $token = ZoomToken::create([
                'access_token' => $request['access_token'],
                'token_type' => $request['token_type'],
                'refresh_token' => $request['refresh_token'],
                'expires_in' => $request['expires_in'],
                'scope' => $request['scope'],
            ]);

            return $token;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //check token is valid or not
    public function checkToken($clientId, $clientSecret)
    {
        try {
            //get latest token
            $token = ZoomToken::latest()->first();

            //get token expiry time
            $expires_in = $token->expires_in;
            //get token created time
            $created_at = $token->created_at;
            //get current time

            $current_time = date('Y-m-d H:i:s');

            //get difference between current time and token created time
            $diff = strtotime($current_time) - strtotime($created_at);

            //check if difference is greater than expiry time
            if ($diff > $expires_in) {
                //if yes then get new token
                $token = $this->generateToken($clientId, $clientSecret);
            } else {
                $token = $token->access_token;
            }

            return $token;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //generate token
    public function generateToken($clientId, $clientSecret)
    {
        try {
            //get latest token
            $token = ZoomToken::latest()->first();
            //get refresh token
            $refresh_token = $token->refresh_token;


            //get new token
            $tokenResponse = Http::asForm()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ])->post('https://zoom.us/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
            ]);


            if ($tokenResponse->successful()) {
                //Save the access token and refresh tokens for future use
                $this->saveZoomToken($tokenResponse->json());
                $accessToken = $tokenResponse->json('access_token');
                return $accessToken;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Delete Webinar
     */
    public function deleteWebinar($id)
    {
        try {
            $webinar = Webinar::find($id);
            $webinar->delete();

            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //getWebinarByMeetingId
    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Get Webinar By Meeting Id
     */
    public function getWebinarByMeetingId($meetingId)
    {
        try {
            $webinar = Webinar::where('meeting_id', $meetingId)->first();
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Get Webinar By Id
     */

    public function getWebinarById($id)
    {
        try {
            $webinar = Webinar::where('id', $id)->first();
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Update Webinar By Id
     */

    public function updateWebinarById($request)
    {
        try {
            $webinar = Webinar::find($request['id']);
            $webinar->date = $request['date'];
            $webinar->time = $request['time'];
            $webinar->time_ext = $request['time_ext'];
            $webinar->duration = $request['duration'];
            // $webinar->course_id = $request['course'];
            $webinar->course_id = 0;
            $webinar->updated_at =  date('Y-m-d H:i:s');
            $webinar->save();
            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Get Webinar By Course Id
     */
    public function getWebinarByCourseId($courseId)
    {
        //get latest webinar by course id
        try {
            $webinar = Webinar::where('course_id', $courseId)->latest()->first();
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * complete Webinar
     */

    public function completeWebinar($request)
    {
        try {
            $webinar = Webinar::find($request['id']);
            $webinar->is_completed = 1;
            $webinar->save();
            return $webinar;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * getAllWebinarsForUser
     */

    public function getAllWebinarsForUser()
    {
        try {
            //GET LOGGED IN USER
            $user = auth()->user();

            $courses = PaymentDetail::where('user_id', $user->id)
                ->whereHas('course', function ($query) {
                    $query->where('is_active', AppConstants::ACTIVE);
                })
                ->where('status', AppConstants::ACTIVE)
                ->get();


            //get webinars for related courses
            $webinars = [];
            foreach ($courses as $course) {

                //course_id is json in table
                // $webinar = Webinar::with('course')->where('course_id', $course->course_id)->first();
                $query = Webinar::with('course')->whereJsonContains('course_id', strval($course->course_id));
                dd($query->toSql());
                if ($webinar) {
                    array_push($webinars, $webinar);
                }
            }

            return ['data' => $webinars, 'status' => true];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
