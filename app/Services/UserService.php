<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Exports\PaymentExportReport;
use App\Helpers\ErrorLogger;
use App\Models\Role;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Student;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;
use App\Providers\LogServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exports\StudentExportReport;
use App\Models\Device;
use App\Models\Forum;
use App\Models\Message;
use App\Models\PaymentDetail;
use AWS\CRT\HTTP\Request;
use Illuminate\Support\Facades\Config;

class UserService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * register a new user
     */
    public function createUser($userRequest, $role)
    {

        try {

            //user create and assign role
            $user = User::create([
                'first_name' => $userRequest['first_name'],
                'last_name' => $userRequest['last_name'],
                'email' => $userRequest['email'],
                'password' => bcrypt($userRequest['password']),
                'is_active' => AppConstants::USER_ACTIVE,
                'gender' => AppConstants::NO_GENDER_DETAILS,

            ]);

            $user->assignRole(AppConstants::STUDENT_ROLE);

            //Insert to device table by checking AppConstants::MAXIMUM_NUMBER_OF_DEVICES
            if ($role == AppConstants::STUDENT_ROLE_ID) {
                $device = Device::where('user_id', $user->id)->count();
                if ($device < AppConstants::MAXIMUM_NUMBER_OF_DEVICES) {
                    $device = Device::create([
                        'user_id' => $user->id,
                        'device_id' => $userRequest['device_id'],
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            return $user;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch user from user id
     */

    public function getUserById($id)
    {
        try {
            $user = User::find($id);

            return $user; // Return the User object
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch user from user email
     */

    public function getUserByEmail($email)
    {
        try {
            $user = User::where('email', $email)->first();
            return $user; // Return the User object
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * reset password
     */

    public function resetPassword($email, $password)
    {

        try {

            $user = User::where('email', $email)->first();
            $user->password = bcrypt($password);
            $user->save();
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties(['password' => $password, 'email' => $email])
                ->log('password_reset');
            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * create admin user
     */

    public function createAdminUser($adminRequest)
    {

        try {

            DB::beginTransaction();

            //user create and assign role
            $user = User::create([
                'first_name' => $adminRequest['first_name'],
                'last_name' => $adminRequest['last_name'],
                'email' => $adminRequest['email'],
                'is_active' => AppConstants::USER_ACTIVE,
                'gender' => $adminRequest['gender'],
                'phone_number' => $adminRequest['phone_number'],
                'password' => bcrypt($adminRequest['password']),
            ]);

            $user->assignRole(AppConstants::ADMIN_ROLE);
            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * check user type
     */
    public function checkUserType($user)
    {
        try {
            if ($user->hasRole(AppConstants::ADMIN_ROLE)) {
                return AppConstants::ADMIN_ROLE;
            } else if ($user->hasRole(AppConstants::STUDENT_ROLE)) {
                return AppConstants::STUDENT_ROLE;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch all users
     */
    public function fetchUsers($request)
    {

        try {
            $users = User::with([
                'payment_details' => function ($query) use ($request) {
                    //check user role is student
                    if ($request['type'] == AppConstants::STUDENT_ROLE_ID) {
                        $query->where('status', AppConstants::ACTIVE);
                    }

                    $query->with('course:id,name');
                    // Filter payment details by course_id if provided in the request
                    if (isset($request['course_id'])) {
                        $query->where('course_id', $request['course_id']);
                    }
                }
            ])
                //if type is there filter with role
                ->when($request['type'], function ($query) use ($request) {
                    $query->whereHas('roles', function ($query) use ($request) {
                        $query->where('role_id', $request['type']);
                    });
                })
                // ->where('is_active', AppConstants::USER_ACTIVE)
                // ->whereHas('roles', function ($query) {
                //     $query->where('role_id', AppConstants::STUDENT_ROLE_ID);
                // })

                ->orderBy('created_at', 'desc')
                ->get();

            // dd($users);

            if ($request['type'] == AppConstants::STUDENT_ROLE_ID) {
                // If you want to remove users with no payment details after filtering, you can do:
                $users = $users->filter(function ($user) {
                    return $user->payment_details->isNotEmpty();
                })->values();
            }

            return $users;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * activate_deactivate a user
     */

    public function activate_deactivate($request)
    {
        try {
            $user = User::find($request['user_id']);
            $user->is_active = $request['status'];
            $user->save();
            return true;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * update user
     */
    public function fetchUserById($id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                // Convert timestamps to 'Asia/Kolkata' timezone
                $user->created_at = Carbon::parse($user->created_at)->timezone('Asia/Kolkata');
                $user->updated_at = Carbon::parse($user->updated_at)->timezone('Asia/Kolkata');
            }
            return $user;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * update user
     */
    public function updateAdminUser($request)
    {
        try {
            $user = User::find($request['id']);
            $user->first_name = $request['first_name'];
            $user->last_name = $request['last_name'];
            $user->email = $request['email'];
            $user->phone_number = $request['phone_number'];
            $user->gender = $request['gender'] ?? 0;

            if (isset($request['profile_image'])) {
                $imagePath = $request['profile_image']->store('image/profile_image', 'public');
                $user->profile_image = $imagePath;
            }


            $user->save();
            return $user;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete user
     */
    public function deleteUser($id)
    {
        try {
            //check user is in forum created by
            $forum = Forum::where('created_by', $id)->get();
            //check usey is in message from_user or to_user
            $message = Message::where('user_from', $id)->orWhere('to_user', $id)->get();

            if (count($message) > 0) {
                return ['status' => false, 'message' => 'We re unable to delete account at this time.
                 Account have created important data within the system that cannot be removed.'];
            }

            if (count($forum) > 0) {
                return ['status' => false, 'message' => 'We re unable to delete account at this time.
                Account have created important data within the system that cannot be removed.'];
            }


            $user = User::find($id);
            $user->delete();
            return ['status' => true, 'message' => 'User deleted successfully'];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch latest student user's for the week
     */
    public function fetchLatestStudents()
    {
        try {
            $endDate = Carbon::now()->endOfDay();
            $startDate = $endDate->copy()->subDays(6)->startOfDay();

            $students = User::whereHas('roles', function ($query) {
                $query->where('role_id', AppConstants::STUDENT_ROLE_ID);
            })
                ->where('is_active', AppConstants::USER_ACTIVE)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $countsByDay = [];

            for ($i = 0; $i <= 6; $i++) {
                $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                $countsByDay[] = [
                    'day' => $date,
                    'count' => 0
                ];
            }

            foreach ($students as $student) {
                $date = $student->created_at->format('Y-m-d');
                foreach ($countsByDay as &$count) {
                    if ($count['day'] === $date) {
                        $count['count']++;
                        break;
                    }
                }
            }

            $activeStudentsCount = User::whereHas('roles', function ($query) {
                $query->where('role_id', AppConstants::STUDENT_ROLE_ID);
            })
                ->where('is_active', AppConstants::USER_ACTIVE)
                ->count();

            $activeAdminCount = User::whereHas('roles', function ($query) {
                $query->where('role_id', AppConstants::ADMIN_ROLE_ID);
            })
                ->where('is_active', AppConstants::USER_ACTIVE)
                ->count();

            return [
                'activeStudentsCount' => $activeStudentsCount,
                'activeAdminCount' => $activeAdminCount,
                'countsByDay' => $countsByDay
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch Users by type
     */

    public function usersDropdown($type)
    {
        try {
            $role = Role::where('id', $type)->first();
            if (!$role) {
                return false;
            }
            $users = User::whereHas('roles', function ($query) use ($type) {
                $query->where('role_id', $type);
            })
                ->where('is_active', AppConstants::USER_ACTIVE)
                ->get();
            return $users;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * change password
     */

    public function changePassword($request)
    {
        try {
            //check if old password is correct
            $user = Auth::user();

            // bcrypt
            if (Hash::check($request['old_password'], $user->password)) {
                //update password
                $user->password = bcrypt($request['password']);
                $user->save();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch student report
     */

    public function studentReport($request)
    {
        try {
            $students = User::with([
                'payment_details' => function ($query) use ($request) {
                    $query->with('course:id,name');
                    // Filter payment details by course_id if provided in the request
                    if (isset($request['course_id'])) {
                        $query->where('course_id', $request['course_id']);
                    }
                }
            ])
                ->where('is_active', AppConstants::USER_ACTIVE)
                ->whereHas('roles', function ($query) {
                    $query->where('role_id', AppConstants::STUDENT_ROLE_ID);
                })
                ->when($request['from_date'], function ($query) use ($request) {
                    $query->whereHas('payment_details', function ($query) use ($request) {
                        $query->whereDate('created_at', '>=', $request['from_date']);
                    });
                })
                ->when($request['to_date'], function ($query) use ($request) {
                    $query->whereHas('payment_details', function ($query) use ($request) {
                        $query->whereDate('created_at', '<=', $request['to_date']);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $students = $students->filter(function ($user) {
                return $user->payment_details->isNotEmpty();
            })->values();

            return $students;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }


    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch payment report
     */

    public function paymentReport($request)
    {
        try {
            //select paymentdetails user details need to filter by date and course
            $payment_details = PaymentDetail::with(['user', 'course', 'courseCurrency'])
                ->when($request['course_id'], function ($query) use ($request) {
                    $query->where('course_id', $request['course_id']);
                })
                ->when($request['from_date'], function ($query) use ($request) {
                    $query->whereDate('created_at', '>=', $request['from_date']);
                })
                ->when($request['to_date'], function ($query) use ($request) {
                    $query->whereDate('created_at', '<=', $request['to_date']);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            //get currecy symbol
            // $currency_symbol = AppConstants::CANADIAN_DOLLAR;
            //set to payment details
            // foreach ($payment_details as $payment_detail) {
            //     $payment_detail->currency = AppConstants::CANADIAN_DOLLAR;
            // }
            return $payment_details;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * Student report excel export
     */

    public function studentReportExcel($request)
    {
        try {
            $student_report = $this->studentReport($request);

            // Use the Excel facade to export the data using your export class
            return Excel::download(new StudentExportReport($student_report), 'student_report.xlsx');
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * payment report excel export
     */

    public function paymentReportExcel($request)
    {
        try {
            $payment_report = $this->paymentReport($request);

            // Use the Excel facade to export the data using your export class
            return Excel::download(new PaymentExportReport($payment_report), 'payment_report-' . time() . '.xlsx');
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * Check if the device id is already registered for the user
     *
     * @param int $userId
     * @param string $deviceId
     * @return bool
     *
     */

    public function checkDeviceId($userId, $deviceId)
    {
        try {
            $device = Device::where('user_id', $userId)->where('device_id', $deviceId)->first();
            if ($device) {
                return true;
            } else {
                //check device id count
                $device = Device::where('user_id', $userId)->count();
                if ($device < AppConstants::MAXIMUM_NUMBER_OF_DEVICES) {

                    $device = Device::create([
                        'user_id' => $userId,
                        'device_id' => $deviceId,
                        'created_at' => Carbon::now(),
                    ]);
                    return true;
                }
                return false;
            }
        } catch (\Exception $e) {

            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * Get the user details for the current token
     *
     * @return void
     */
    public function userDetails()
    {
        try {
            //get current token
            $token = Request()->bearerToken();
            //get logged in user
            $user = Auth::user();
            //get user type
            $userType = $this->checkUserType($user);
            return [
                'token' => $token,
                'user_type' => $userType,
                'user' => $user
            ];
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false; // Return null instead of false
        }
    }

    /**
     * Email Verify
     *
     * @return void
     */
    public function emailVerify($email)
    {
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                return ['status' => true, 'message' => "Email address you entered cannot be used for creating a new account.", 'data' => 1];
            } else {
                return ['status' => true, 'message' => 'Email not exists.', 'data' => 0];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }

    /**
     * courseDisable
     *
     * @return void
     */

    public function courseDisable($request)
    {
        try {
            $payment_record = PaymentDetail::where('course_id', $request['course_id'])->where('user_id', $request['user_id'])->first();
            if ($payment_record) {
                $payment_record->status = $request['status'];
                $payment_record->save();
                if ($request['status'] == 0) {
                    return ['status' => true, 'message' => 'The student has successfully unsubscribed from the course.'];
                }
                return ['status' => true, 'message' => 'The student has successfully subscribed to the course.'];
            } else {
                return ['status' => false, 'message' => 'No payment record found.'];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    //fetchAllStudents

    public function fetchAllStudents($request)
    {
        try {

            $users = User::with([
                'payment_details' => function ($query) use ($request) {

                    $query->with('course:id,name');
                    // Filter payment details by course_id if provided in the request
                    if (isset($request['course_id'])) {
                        $query->where('course_id', $request['course_id']);
                    }
                }
            ])
                //if type is there filter with role
                ->when($request['type'], function ($query) use ($request) {
                    $query->whereHas('roles', function ($query) use ($request) {
                        $query->where('role_id', $request['type']);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $users = $users->filter(function ($user) {
                return $user->payment_details->isNotEmpty();
            })->values();
            if ($users) {
                return ['status' => true, 'message' => 'Students fetched successfully.', 'data' => $users];
            } else {
                return ['status' => true, 'message' => 'No students found.', 'data' => []];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }

    //resetDevice
    public function resetDevice($email)
    {
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                $device = Device::where('user_id', $user->id)->delete();
                return ['status' => true, 'message' => 'Device reset successfully.'];
            } else {
                return ['status' => false, 'message' => 'User not found.'];
            }
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ['status' => false, 'message' => 'Something went wrong.'];
        }
    }
}
