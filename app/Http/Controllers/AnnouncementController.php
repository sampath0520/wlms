<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CreateAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{

    protected $announcementService;
    public function __construct(AnnouncementService $announcementService)
    {
        $this->middleware('auth');
        $this->announcementService = $announcementService;
    }

    /**
     * create Announcement
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * create Announcement
     */

    public function createAnnouncement(CreateAnnouncementRequest $request)
    {
        $validated = $request->validated();

        $announcement = $this->announcementService->createAnnouncement($validated);
        if ($announcement) {
            return ResponseHelper::success('Announcement created successfully', $announcement);
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * fetchAnnouncements
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchAnnouncements
     */

    public function fetchAnnouncements()
    {
        $announcement = $this->announcementService->fetchAnnouncements();
        if ($announcement) {
            return ResponseHelper::success(trans('messages.record_fetched'), $announcement);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * fetchAll
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchAll
     */

    public function fetchAllAnnouncements()
    {
        $announcement = $this->announcementService->fetchAll();
        if ($announcement) {
            return ResponseHelper::success(trans('messages.record_fetched'), $announcement);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * deleteAnnouncement
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * deleteAnnouncement
     */

    public function deleteAnnouncement($id)
    {
        $announcement = $this->announcementService->deleteAnnouncement($id);
        if ($announcement) {
            return ResponseHelper::success('Announcement deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * fetchAnnouncementById
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * fetchAnnouncementById
     */

    public function fetchAnnouncementById($id)
    {
        $announcement = $this->announcementService->fetchAnnouncementById($id);
        if ($announcement['status']) {
            return ResponseHelper::success($announcement['message'], $announcement['data']);
        } else {
            return ResponseHelper::error($announcement['message']);
        }
    }

    /**
     * updateAnnouncement
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * updateAnnouncement
     */

    public function updateAnnouncementById(UpdateAnnouncementRequest $request)
    {
        $validated = $request->validated();
        $announcement = $this->announcementService->updateAnnouncementById($validated);
        if ($announcement['status']) {
            return ResponseHelper::success($announcement['message'], $announcement['data']);
        } else {
            return ResponseHelper::error($announcement['message']);
        }
    }
}
