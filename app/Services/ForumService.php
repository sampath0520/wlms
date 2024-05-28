<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumParticipant;
use App\Models\ForumReply;
use App\Models\PaymentDetail;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Illuminate\Support\Facades\DB;

class ForumService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * create forum
     */

    public function createForum($data)
    {
        try {

            //image upload
            if (isset($data['image'])) {
                $imagePath = $data['image']->store('image/Forum', 'public');
            } else {
                $imagePath = null;
            }

            $forum = Forum::create(
                [
                    'course_id' => $data['course_id'],
                    'name' => $data['name'],
                    'image' => $imagePath,
                    'description' => $data['description'],
                    'status' => 1,
                    'created_by' => auth()->user()->id,
                ]
            );
            $forum_reg = $this->registerForum(['forum_id' => $forum->id]);
            if ($forum_reg['status'] == false) {
                return false;
            }
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * add reply
     */

    public function addReply($data)
    {

        try {

            //images upload
            if (isset($data['images'])) {
                $images = [];
                foreach ($data['images'] as $image) {
                    $imagePath = $image->store('image/Forum', 'public');
                    array_push($images, $imagePath);
                }
                //convert array json
                $images = json_encode($images);
            } else {
                $images = null;
            }

            $forumReply = ForumReply::create([
                'forum_id' => $data['forum_id'],
                'reply' => $data['reply'],
                'created_by' => auth()->user()->id,
                'images' => $images,
            ]);

            return $forumReply;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete reply
     */

    public function deleteReply($id)
    {
        try {
            $forumReply = ForumReply::find($id);
            if (!$forumReply) {
                return false;
            }
            $forumReply->delete();
            return $forumReply;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete forum
     */

    public function deleteForum($id)
    {
        try {
            $forum = Forum::find($id);
            if (!$forum) {
                return false;
            }
            $forum->delete();
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * register forum
     */

    public function registerForum($data)
    {
        try {
            //check if already registered
            $forum = ForumParticipant::where('forum_id', $data['forum_id'])
                ->where('user_id', auth()->user()->id)
                ->first();
            if ($forum) {
                return [
                    'status' => false,
                    'message' => 'User already registered'
                ];
            }
            //check logged in user is admin or not
            $userService = new UserService();
            $user_role = $userService->checkUserType(auth()->user());
            if ($user_role == AppConstants::STUDENT_ROLE) {
                $forum = ForumParticipant::create([
                    'forum_id' => $data['forum_id'],
                    'user_id' => auth()->user()->id,
                ]);
            }

            return [
                'status' => true,
                'message' => 'You have successfully joined the forum.',
                'data' => $forum
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return [
                'status' => false,
                'message' => 'Something went wrong'
            ];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch forum
     */

    public function forumDetails($request)
    {
        // try {
        //     //get logged in user
        //     $user = auth()->user();

        //     //call to user service to get user details
        //     $userService = new UserService();
        //     $user_role = $userService->checkUserType($user);

        //     //forum with forum participants
        //     $forum = Forum::with('course', 'forum_participants', 'user', 'forum_replies')
        //         // ->where('status', AppConstants::ACTIVE)
        //         ->when($user_role == AppConstants::STUDENT_ROLE, function ($query) {
        //             return $query->where('status', AppConstants::ACTIVE);
        //         })
        //         ->when($request->search, function ($query, $search) {
        //             return $query->where('name', 'like', '%' . $search . '%');
        //         })
        //         //if $request->dashboard == 1 then get only 3 records
        //         ->when($request->dashboard == 1, function ($query) {
        //             return $query->limit(3);
        //         })
        //         ->orderBy('id', 'desc')
        //         ->get()

        //         //get count of forum participants
        //         ->map(function ($item) {
        //             //if image null
        //             if ($item->image == null) {
        //                 $item->image = 'image/default/default.png';
        //             }
        //             $item->forum_participants_count = $item->forum_participants->count();
        //             $item->creator = $item->user->first_name . ' ' . $item->user->last_name;
        //             //check current user is registered or not

        //             $item->is_registered = $item->forum_participants->contains('user_id', auth()->user()->id);
        //             //get forum replies name
        //             $item->forum_replies = $item->forum_replies->map(function ($item) {
        //                 $item->replier_name = $item->user->first_name . ' ' . $item->user->last_name;
        //                 unset($item->user);
        //                 return $item;
        //             });

        //             unset($item->forum_participants);
        //             unset($item->user);
        //             return $item;
        //         });

        //     return $forum;
        // } catch (\Exception $e) {
        //     ErrorLogger::logError($e);
        //     return false;
        // }

        try {
            //get logged in user
            $user = auth()->user();

            //call to user service to get user details
            $userService = new UserService();
            $user_role = $userService->checkUserType($user);

            //get user registered courses
            $courses = PaymentDetail::where('user_id', $user->id)
                //if user is student then get only active payment details
                ->when($user_role == AppConstants::STUDENT_ROLE, function ($query) {
                    return $query->where('status', AppConstants::ACTIVE);
                })
                ->get();

            if ($user_role == AppConstants::STUDENT_ROLE) {
                //get courses where in user registered
                $course_ids = $courses->pluck('course_id')->toArray();
            } else {
                //get all course ids
                $course_ids = Course::pluck('id')->toArray();
            }

            //forum with forum participants
            $forum = Course::with('forums', 'forums.forum_participants', 'forums.user', 'forums.forum_replies')
                ->whereIn('id', $course_ids)
                ->when($user_role == AppConstants::STUDENT_ROLE, function ($query) {
                    $query->whereHas('forums', function ($forumQuery) {
                        $forumQuery->where('status', AppConstants::ACTIVE);
                    });
                })

                ->when($request->search, function ($query, $search) {
                    return $query->whereHas('forums', function ($forumQuery) use ($search) {
                        $forumQuery->where('name', 'like', '%' . $search . '%');
                    });
                })
                //if $request->dashboard == 1 then get only 3 records
                ->when($request->dashboard == 1, function ($query) {
                    $query->with(['forums' => function ($forumQuery) {
                        //where status active
                        $forumQuery->where('status', AppConstants::ACTIVE);
                        $forumQuery->limit(3);
                    }]);
                })
                ->when($request->dashboard == 2, function ($query) {
                    $query->with(['forums' => function ($forumQuery) {
                        //where status active
                        $forumQuery->where('status', AppConstants::ACTIVE);
                    }]);
                })


                //order by forum desc
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->forums = $item->forums->map(function ($item) {

                        //if image null
                        if ($item->image == null) {
                            $item->image = 'image/default/default.png';
                        }
                        $item->forum_participants_count = $item->forum_participants->count();
                        $item->creator = $item->user->first_name . ' ' . $item->user->last_name;
                        //check current user is registered or not

                        $item->is_registered = $item->forum_participants->contains('user_id', auth()->user()->id);
                        //get forum replies name
                        $item->forum_replies = $item->forum_replies->map(function ($item) {
                            $item->replier_name = $item->user->first_name . ' ' . $item->user->last_name;
                            unset($item->user);
                            return $item;
                        });

                        unset($item->forum_participants);
                        unset($item->user);
                        return $item;
                    });
                    return $item;
                });


            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * forum Threads
     */

    public function forumThreads($data)
    {
        try {
            $forum = Forum::with('forum_participants', 'forum_replies.user', 'user')
                ->where('id', $data['forum_id'])
                ->where('status', AppConstants::ACTIVE)
                ->orderBy('id', 'desc')
                ->get()

                //get count of forum participants
                ->map(function ($item) {
                    if ($item->image == null) {
                        $item->image = 'image/default/default.png';
                    }
                    // $item->forum_replies_count = $item->forum_replies->count();
                    $item->forum_replies_count = $item->forum_participants->count();
                    // $item->forum_participants_count = $item->forum_participants->count();
                    $item->creator = $item->user->first_name . ' ' . $item->user->last_name;

                    $item->images = $item->forum_replies->map(function ($item) {
                        $item->images = json_decode($item->images);
                        return $item;
                    });

                    //images
                    unset($item->forum_participants);
                    unset($item->user);
                    return $item;
                });

            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * update forum
     */

    public function updateForum($data)
    {
        try {
            $forum = Forum::find($data['forum_id']);
            $forum->name = $data['name'];
            $forum->save();
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * activate deactivate forum
     */

    public function changeStatus($data)
    {
        try {
            $forum = Forum::find($data['forum_id']);
            $forum->status = $data['status'];
            $forum->save();
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch forum by id
     */

    public function fetchForumById($id)
    {

        try {
            $forum = Forum::where('id', $id)
                ->where('status', AppConstants::ACTIVE)
                ->first();
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * fetchForumParticipants
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch forum participants
     */

    public function fetchForumParticipants($id)
    {
        try {
            //need participants count
            $forum = Forum::with('forum_participants', 'forum_participants.user')
                ->where('id', $id)
                ->where('status', AppConstants::ACTIVE)
                ->orderBy('id', 'desc')
                ->get()

                //get count of forum participants
                ->map(function ($item) {
                    $item->creator = $item->user->first_name . ' ' . $item->user->last_name;
                    $item->forum_participants_count = $item->forum_participants->count();
                    $item->forum_participants = $item->forum_participants->map(function ($item) {
                        $item->user_name = $item->user->first_name . ' ' . $item->user->last_name;
                        unset($item->user);
                        return $item;
                    });
                    unset($item->user);
                    return $item;
                });

            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * deleteParticipants
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete forum participants
     */

    public function deleteParticipants($id)
    {
        try {
            $forum = ForumParticipant::where('id', $id)->delete();
            return $forum;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }
}
