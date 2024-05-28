<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CreateQuestionsAnswers;
use App\Http\Requests\ForumCreateRequest;
use App\Http\Requests\ForumRegisterRequest;
use App\Http\Requests\ForumReplyRequest;
use App\Http\Requests\ForumStatusRequest;
use App\Http\Requests\ForumUpdateRequest;
use App\Http\Requests\QuizCreateRequest;
use App\Http\Requests\QuizStatusRequest;
use App\Services\CourseService;
use App\Services\ForumService;
use App\Services\QuizService;
use Illuminate\Http\Request;

class ForumController extends Controller
{

    protected $forumService;
    public function __construct(ForumService $forumService)
    {
        $this->middleware('auth');
        $this->forumService = $forumService;
    }

    /**
     * createForum
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * createForum
     */

    public function createForum(ForumCreateRequest $request)
    {
        $validated = $request->validated();

        $forum = $this->forumService->createForum($validated);
        if ($forum) {
            return ResponseHelper::success(trans('messages.forum_create_success'), $forum);
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * addReply
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * addReply
     */

    public function addReply(ForumReplyRequest $request)
    {
        $validated = $request->validated();

        $forum = $this->forumService->addReply($request);
        if ($forum) {
            return ResponseHelper::success(trans('messages.record_created'));
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * deleteReply
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * deleteReply
     */

    public function deleteReply($id)
    {
        $forum = $this->forumService->deleteReply($id);
        if ($forum) {
            return ResponseHelper::success(trans('messages.delete_success'));
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * deleteForum
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * deleteForum
     */

    public function deleteForum($id)
    {
        $forum = $this->forumService->deleteForum($id);
        if ($forum) {
            return ResponseHelper::success('Forum deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }

    /**
     * registerForum
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * registerForum
     */

    public function registerForum(ForumRegisterRequest $request)
    {
        $forum = $this->forumService->registerForum($request->all());
        if ($forum['status']) {
            return ResponseHelper::success($forum['message'], $forum['data']);
        } else {
            return ResponseHelper::error($forum['message']);
        }
    }

    /**
     * forum Details
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * forum Details
     */

    public function forumDetails(Request $request)
    {

        $forum = $this->forumService->forumDetails($request);
        if ($forum) {
            return ResponseHelper::success(trans('messages.record_fetched'), $forum);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * forum Threads
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * forum Threads
     */

    public function forumThreads($forumId)
    {
        $forum = $this->forumService->fetchForumById($forumId);
        if (!$forum) {
            return ResponseHelper::error('Forum not found');
        }

        $data = [
            'forum_id' => $forumId
        ];

        $forum = $this->forumService->forumThreads($data);
        if ($forum) {
            return ResponseHelper::success(trans('messages.record_fetched'), $forum);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * updateForum
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * updateForum
     */

    public function updateForum(ForumUpdateRequest $request)
    {

        $validated = $request->validated();

        $forum = $this->forumService->updateForum($validated);
        if ($forum) {
            return ResponseHelper::success('Forum updated successfully');
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * updateForumStatus
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * updateForumStatus
     */

    public function changeStatus(ForumStatusRequest $request)
    {
        $validated = $request->validated();
        $forum = $this->forumService->changeStatus($validated);
        if ($forum) {
            return ResponseHelper::success('Forum status updated successfully');
        } else {
            return ResponseHelper::error(trans('messages.record_update_failed'));
        }
    }

    /**
     * fetchForumById
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * fetchForumById
     */

    public function fetchForumById($id)
    {
        $forum = $this->forumService->fetchForumById($id);
        if ($forum) {
            return ResponseHelper::success(trans('messages.record_fetched'), $forum);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * fetchForumParticipants
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * fetchForumParticipants
     */

    public function fetchForumParticipants($id)
    {
        $forum = $this->forumService->fetchForumById($id);
        if ($forum) {
            $participants = $this->forumService->fetchForumParticipants($id);
            if ($participants) {
                return ResponseHelper::success(trans('messages.record_fetched'), $participants);
            } else {
                return ResponseHelper::error(trans('messages.record_fetch_failed'));
            }
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }


    public function deleteParticipants($id)
    {
        $forum = $this->forumService->deleteParticipants($id);
        if ($forum) {
            return ResponseHelper::success(trans('messages.delete_success'));
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }
}
