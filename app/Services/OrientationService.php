<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Orientation;
use Illuminate\Support\Facades\Auth;

class OrientationService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create Orientation
     */

    public function createOrientation($data)
    {
        try {
            $orientation = Orientation::create([
                'course_id' => $data['course_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message']
            ]);
            return $orientation;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch Orientation
     */

    public function fetchOrientations()
    {
        try {
            $orientations = Orientation::all();
            return $orientations;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
