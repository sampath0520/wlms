<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\MessageCreateRequest;
use App\Services\MessageService;
use App\Services\UserService;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    protected $messageService;
    protected $userService;
    public function __construct(MessageService $messageService, UserService $userService)
    {
        $this->middleware('auth:api');
        $this->messageService = $messageService;
        $this->userService = $userService;
    }

    /**
     * create message
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function createMessage(MessageCreateRequest $request)
    {
        $validated = $request->validated();

        $msg = $this->messageService->createMessage($request);
        if ($msg) {
            return ResponseHelper::success(trans('messages.message_sent'));
        } else {
            return ResponseHelper::error(trans('messages.record_creation_failed'));
        }
    }

    /**
     * get messages
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function fetchMessages()
    {
        $allMsg = $this->messageService->fetchAllMessages();
        if ($allMsg) {
            return ResponseHelper::success(trans('messages.record_fetched'), $allMsg);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Chat view
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function chatView($id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user || $user['is_active'] != 1) {
            return ResponseHelper::error(trans('messages.user_not_found'));
        }
        $chat = $this->messageService->SingleChat($id);
        if ($chat) {
            return ResponseHelper::success(trans('messages.record_fetched'), $chat);
        } else {
            return ResponseHelper::error(trans('messages.record_fetch_failed'));
        }
    }

    /**
     * Delete message
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     */

    public function deleteMessage($id)
    {
        $message = $this->messageService->deleteMessage($id);
        if ($message) {
            return ResponseHelper::success('Message deleted successfully');
        } else {
            return ResponseHelper::error(trans('messages.delete_failed'));
        }
    }
}
