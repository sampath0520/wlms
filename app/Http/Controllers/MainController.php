<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MainController extends Controller
{
    /**
     * return success response
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    static function sendSuccess($message, $result = [], $code = 200){

        $response = [
            'message' => $message,
            'status_code' => 1,
        ];

        if(!empty($result)){
            $response['data'] = $result;
        }

        return response()->json($response,$code);

    }

    /**
     * return fail response
     * 
     * @return \Illuminate\Http\Response
     * 
     */
    static function sendError($error, $result = [], $code = 200){

        $response = [
            'message' => $error,
            'status_code' => 0,
        ];

        if(!empty($result)){
            $response['data'] = $result;
        }

        return response()->json($response,$code);

    }

}
