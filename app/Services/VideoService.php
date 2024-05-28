<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\CourseContent;
use App\Models\SampleClass;
use App\Models\Video;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VideoService
{
    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Get All Videos
     */
    public function fetchAllVideos()
    {
        try {
            $videos =   Video::where('status', AppConstants::ACTIVE)->orderBy('id', 'desc')->get();
            foreach ($videos as $video) {
                $video->aws_file_name = basename(parse_url($video->link, PHP_URL_PATH));
                $video->link .= '?id=' . $video->id; // Append the video id as a query parameter
            }
            return $videos;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create Video
     */

    public function createVideo($data)
    {
        try {
            if (isset($data['thumbnail'])) {
                $imagePath = $data['thumbnail']->store('image/video_thumbnail', 'public');
            } else {
                // $imagePath = null;
                $imagePath = "image/default/video-thumb.jpeg";
            }

            //upload video to s3 bucket
            $video = $data->file('video');
            $videoName = time() . '.' . $video->getClientOriginalExtension();
            $video->storeAs('videos', $videoName, 's3');
            $videoUrl = 'https://' . env('AWS_BUCKET') . '.s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/videos/' . $videoName;
            $video = Video::create([
                'title' => $data->title,
                'link' => $videoUrl,
                'thumbnail' => $imagePath,
                'status' => AppConstants::ACTIVE
            ]);
            return $video;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Create Sample Class
     */

    public function createSampleClass($data)
    {
        try {

            if (isset($data['thumbnail'])) {
                $imagePath = $data['thumbnail']->store('image/sample_class_thumbnail', 'public');
            } else {
                // $imagePath = null;
                $imagePath = "image/default/video-thumb.jpeg";
            }
            //upload video to s3 bucket
            $video = $data->file('video');
            if (!$video) {
                return ['status' => false, 'message' => 'Video is required'];
            }

            $videoName = time() . '.' . $video->getClientOriginalExtension();
            $video->storeAs('videos', $videoName, 's3');
            $videoUrl = 'https://' . env('AWS_BUCKET') . '.s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/videos/' . $videoName;
            $sampleClass = SampleClass::create([
                'title' => $data->title,
                'sub_title' => $data->sub_title,
                'link' => $videoUrl,
                'status' => AppConstants::ACTIVE,
                'thumbnail' => $imagePath
            ]);
            return ['status' => true, 'message' => 'Sample Class Created Successfully', 'data' => $sampleClass];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }

    //fetchAllSampleClass
    public function fetchAllSampleClass($id)
    {
        try {
            $sampleClass =   CourseContent::where('course_id', $id)
                ->where('content_type', 1)
                ->where('status', AppConstants::ACTIVE)
                ->where('is_locked', 0)
                ->get();
            foreach ($sampleClass as $content) {

                //get link url id and get thumbnail from Video model
                $content->thumbnail = null;
                $videoId = explode('=', $content->content_link)[1] ?? null;

                $content->aws_file_name = basename(parse_url($content->content_link, PHP_URL_PATH));

                if (isset($videoId)) {
                    $video = Video::find($videoId);
                    if (!$video) {
                        $content->thumbnail = null;
                    } else {
                        $content->thumbnail = $video->thumbnail;
                    }
                }
            }
            return $sampleClass;
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Fetch Video By Id
     */
    public function fetchVideoById($id)
    {
        try {
            $video =   Video::where('id', $id)->first();
            return $video;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Delete Video
     */

    public function deleteVideo($id)
    {


        try {
            DB::beginTransaction();
            $video = Video::where('id', $id)->first();
            if ($video) {
                $video->delete();
                $videoName = basename($video->link);
                $videoName = substr($video->link, strrpos($video->link, '/') + 1);

                // Configure AWS S3
                $s3 = new S3Client([
                    'region' => env('AWS_DEFAULT_REGION'),
                    'version' => 'latest',
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                // Specify your S3 bucket name
                $bucket = env('AWS_BUCKET');
                // Delete the video file from S3
                $s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => 'videos/' . $videoName,
                ]);

                //check is deleted or not
                $isDeleted = $s3->doesObjectExist($bucket, 'videos/' . $videoName);
                if ($isDeleted) {
                    DB::rollBack();
                    return false;
                }

                $videoId = $video->id;

                // Construct the expected query parameter string for the video ID
                $videoIdQueryParameter = 'id=' . $videoId;

                // Retrieve all CourseContent entries where content_link contains the video ID query parameter
                $courseContents = CourseContent::where('content_link', 'like', '%' . $videoIdQueryParameter . '%')->where('content_type', 1)->get();

                // Delete the associated CourseContent entries
                foreach ($courseContents as $courseContent) {
                    //update course content as null
                    $courseContent->content_link = null;
                    $courseContent->save();
                }



                // Optionally, you can also delete any associated thumbnails or other files
                // $s3->deleteObject([
                //     'Bucket' => $bucket,
                //     'Key' => 'path/to/thumbnails/' . $videoName,
                // ]);

                // Optionally, you can also delete the directory if it's empty
                // $s3->deleteObject([
                //     'Bucket' => $bucket,
                //     'Key' => 'path/to/videos/',
                // ]);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Update Video
     */

    public function updateVideo($data)
    {

        try {
            $video = Video::find($data['id']);
            if (!$video) {
                return false;
            }
            $video->title = $data['title'];
            $video->save();
            return $video;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
