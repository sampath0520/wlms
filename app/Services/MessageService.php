<?php

namespace App\Services;

use App\Helpers\ErrorLogger;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler\Profile;

class MessageService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Add Message
     */

    public function createMessage($data)
    {
        try {
            if (isset($data['attachment'])) {

                $images = [];
                foreach ($data['attachment'] as $image) {
                    $imagePath = $image->store('attachments/messages', 'public');
                    array_push($images, $imagePath);
                }
                //convert array json
                $imagePath = json_encode($images);
                // $imagePath = $data['attachment']->store('attachments/messages', 'public');
            } else {
                $imagePath = null;
            }

            $message = Message::create([
                'user_from' => auth()->user()->id,
                'message' => $data['message'],
                'to_user' => $data['to_user'] ?? 0,
                'attachment' => $imagePath,
            ]);
            return $message;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch all messages
     */

    public function fetchAllMessages()
    {
        try {
            $messages = Message::with('user', 'user.payment_details.course')
                ->groupBy('user_from')
                ->selectRaw('count(*) as total, user_from')
                ->orderBy('id', 'desc')
                ->get()

                ->map(function ($item) {

                    $user = User::find($item->user_from);
                    //remove admin roles
                    $user->roles = $user->roles->filter(function ($role) {
                        return $role->name != 'admin';
                    });



                    $user = $item->user;

                    $paymentDetails = $user->payment_details->first(); // Assuming a user has one payment detail


                    return [
                        'user_id' => $user->id,
                        'message_count' => $item->total,
                        'creator' => $user->first_name . ' ' . $user->last_name,
                        'course_name' => $paymentDetails ? $paymentDetails->course->name : 'N/A',
                    ];
                });

            return $messages;
        } catch (\Exception $e) {
            dd($e->getMessage());
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch single chat
     */

    public function SingleChat($id)
    {
        try {
            $user = User::find($id);


            $chat = Message::with('user')
                ->where('user_from', $id)
                ->orWhere('to_user', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    $user = $item->user;
                    return [
                        'id' => $item->id,
                        'role' => $user->roles->first()->name,
                        'user_id' => $user->id,
                        'message' => $item->message,
                        'attachment' => json_decode($item->attachment),
                        'creator' => $user->first_name . ' ' . $user->last_name,
                        'created_at' => $item->created_at,
                    ];
                });
            //get user's profile image
            // $profile_image = $user->profile_image;

            return [
                'user' => $user,
                'chat' => $chat,
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Delete message
     */

    public function deleteMessage($id)
    {
        try {
            $message = Message::find($id);
            if (!$message) {
                return false;
            }
            $message->delete();
            return $message;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
