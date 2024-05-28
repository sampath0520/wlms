<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\Forum;
use App\Models\PaymentDetail;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Add Announcement
     */

    public function createAnnouncement($data)
    {
        try {

            //image upload
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/Announcement', 'public');
            } else {
                $imagePath = null;
            }
            $announcement = Announcement::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'course_type' => $data['course'],
                'image' => $imagePath,
                'material_link' => $data['study_material'] ?? null,
            ]);
            return $announcement;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetchAnnouncements
     */

    public function fetchAnnouncements()
    {
        try {
            $logged_user = Auth::user();
            //fetch annoucements for logged in user where $logged_user is in payment_details.user_id
            // $announcements = Announcement::whereHas('course.payment_details', function ($query) use ($logged_user) {
            //     $query->where('user_id', $logged_user->id);
            // })
            //     ->orWhere('course_type', AppConstants::COURSE_TYPE_ALL)
            //     ->orderBy('created_at', 'desc')
            //     ->get();

            $announcements = Announcement::whereHas('course.payment_details', function ($query) use ($logged_user) {
                $query->where('user_id', $logged_user->id)
                    ->where('status', 1);
            })
                ->orWhere('course_type', AppConstants::COURSE_TYPE_ALL)
                ->orderBy('created_at', 'desc')
                ->get();

            //if announcement is null then set default image
            foreach ($announcements as $key => $announcement) {
                if ($announcement->image == null) {
                    // $announcements[$key]->image = 'image/default/default.png';
                    $announcements[$key]->image = "image/default/announcement-thumb.jpeg";
                }
            }


            return $announcements;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetchAll
     */

    public function fetchAll()
    {
        try {
            $announcements = Announcement::orderBy('created_at', 'desc')->get();
            //if image is null then set default image
            foreach ($announcements as $key => $announcement) {
                if ($announcement->image == null) {
                    // $announcements[$key]->image = 'image/default/default.png';
                    $announcements[$key]->image = "image/default/announcement-thumb.jpeg";
                }
            }
            return $announcements;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * deleteAnnouncement
     */

    public function deleteAnnouncement($id)
    {
        try {
            $announcement = Announcement::find($id);
            if (!$announcement) {
                return false;
            }
            $announcement->delete();
            return $announcement;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetchAnnouncementById
     */

    public function fetchAnnouncementById($id)
    {
        try {
            $announcement = Announcement::find($id);
            if (!$announcement) {
                return ['status' => false, 'message' => 'Announcement not found'];
            }
            return ['status' => true, 'message' => 'Announcement fetched successfully', 'data' => $announcement];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * updateAnnouncement
     */

    public function updateAnnouncementById($data)
    {
        try {
            $announcement = Announcement::find($data['id']);
            if (!$announcement) {
                return ['status' => false, 'message' => 'Announcement not found'];
            }
            //image upload
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/Announcement', 'public');
            } else {
                $imagePath = null;
            }
            $announcement->title = $data['title'];
            $announcement->message = $data['message'];
            $announcement->course_type = $data['course'];
            if ($imagePath != null) {
                $announcement->image = $imagePath;
            }
            $announcement->material_link = $data['study_material'] ?? null;
            $announcement->save();
            return ['status' => true, 'message' => 'Announcement updated successfully', 'data' => $announcement];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong'];
        }
    }
}
