<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function success($message = 'Success', $data = [], $status = 200)
    {
        return response()->json([
            'status_code' => 1, // 0 for 'error', 1 for 'success'
            'status' => true, // false for 'error', true for 'success'
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error($message = 'Error', $data = null, $status = 400)
    {
        return response()->json([
            'status_code' => 0,
            'status' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
