<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\VideoAccess;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProxyController extends Controller
{
    public function proxyVideo($token)
    {
        // Retrieve the actual S3 URL from the database based on the token
        $videoMapping =  VideoAccess::where('token', $token)->first();

        if (!$videoMapping) {
            abort(404); // Token not found
        }

        // Get the S3 URL
        // $s3Url = $videoMapping->video_url;
        $s3Url = 'https://wlms-staging.s3.eu-west-2.amazonaws.com/videos/1702546833.mp4';
        // Use Symfony's BinaryFileResponse to send the file
        // Create a basic response with appropriate headers
        // Create a response with appropriate headers
        // Set headers for video streaming
        $headers = [
            'Content-Type' => 'video/mp4',
        ];

        // Stream the file content as a download
        return response()->stream(
            function () use ($s3Url) {
                readfile($s3Url);
            },
            200,
            $headers
        );
    }
}
