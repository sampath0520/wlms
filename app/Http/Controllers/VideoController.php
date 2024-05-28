<?php

namespace App\Http\Controllers;

use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Http\Requests\AddVideoRequest;
use App\Http\Requests\SampleClassRequest;
use App\Http\Requests\VideoUpdateRequest;
use App\Models\SampleClass;
use App\Models\VideoAccess;
use App\Services\CourseService;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Redirect;
use App\VideoStream;

class VideoController extends Controller
{
    protected $videoService;
    protected $courseService;
    public function __construct(VideoService $videoService, CourseService $courseService)
    {
        // $this->middleware('auth:api');
        $this->videoService = $videoService;
        $this->courseService = $courseService;
    }

    /**
     * Fetch video
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function fetchAllVideos()
    {

        $videos = $this->videoService->fetchAllVideos();
        if ($videos) {
            return ResponseHelper::success(trans('messages.record_fetched'), $videos);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Create video
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function createVideo(AddVideoRequest $request)
    {
        $validated = $request->validated();
        $videos = $this->videoService->createVideo($request);
        if ($videos) {
            return ResponseHelper::success(trans('messages.video_upload_success'), $videos);
        } else {
            return ResponseHelper::error(trans('messages.video_upload_failed'));
        }
    }

    /**
     * Sample Class
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function sampleClass(SampleClassRequest $request)
    {
        $validated = $request->validated();
        $videos = $this->videoService->createSampleClass($request);
        if ($videos['status']) {
            return ResponseHelper::success(trans('messages.record_created'), $videos['data']);
        } else {
            return ResponseHelper::error($videos['message']);
        }
    }

    /**
     * Fetch Sample Class
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function fetchSampleClass($id)
    {
        $course = $this->courseService->getCourseById($id);

        if (!$course['status']) {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
        $videos = $this->videoService->fetchAllSampleClass($id);
        if ($videos) {
            return ResponseHelper::success(trans('messages.record_fetched'), $videos);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Delete Video
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function deleteVideo($id)
    {
        $video = $this->videoService->fetchVideoById($id);
        if ($video) {
            $delete = $this->videoService->deleteVideo($id);
            if ($delete) {
                return ResponseHelper::success(trans('messages.delete_with_course_contents'));
            } else {
                return ResponseHelper::success(trans('messages.delete_failed'));
            }
        } else {
            return ResponseHelper::error(trans('messages.data_not_found'));
        }
    }

    /**
     * Fetch Video By Id
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function updateVideo(VideoUpdateRequest $request)
    {
        $validated = $request->validated();
        $delete = $this->videoService->updateVideo($validated);
        if ($delete) {
            return ResponseHelper::success(trans('messages.video_update_success'));
        } else {
            return ResponseHelper::success(trans('messages.video_update_failed'));
        }
    }

    //streamVideo
    public function streamVideo($token)
    {

        try {

            // $s3 = Storage::disk('s3');
            // dd($s3);
            $s3Client = app('s3');
            // Get the file from video access table
            $videoAccess = VideoAccess::where('token', $token)->first();



            // Check if the file exists
            if (!$videoAccess) {
                abort(404);
            }
            // $duration = '+' . $videoAccess->duration;

            $objectKey = $videoAccess->video_url;

            // $cmd = $s3Client->getCommand('GetObject', [
            //     'Bucket' => env('AWS_BUCKET'),
            //     'Key' => $objectKey,
            // ]);


            // $request = $s3Client->createPresignedRequest($cmd, '+1 minutes');
            // Get the actual presigned-url
            // $presignedUrl = (string)$request->getUri();
            $presignedUrl = 'https://wlms-staging.s3.eu-west-2.amazonaws.com/' . $objectKey;
            // $mime = (\mime_content_type($presignedUrl));
            //get file size
            // $size = $s3Client->headObject([
            //     'Bucket' => env('AWS_BUCKET'),
            //     'Key' => $objectKey,
            // ])->get('ContentLength');

            $headers = [
                'Content-Type' => 'video/mp4',
                // 'Content-Length' => $size,
                // 'Accept-Ranges' => 'bytes',
            ];

            // Stream the file content as a download
            return response()->stream(
                function () use ($presignedUrl) {
                    // readfile($presignedUrl, 'r');
                    readfile($presignedUrl);
                },
                200,
                $headers
            );
        } catch (\Exception $e) {
            dd($e);
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Signed url fetch failed'];
        }
    }
}
