<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Helpers\ResponseHelper;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\EmailVerifyRequest;
use App\Models\Device;
use App\Models\User;
use App\Services\PasswordRestTokenService;
use App\Services\UserService;
use Dotenv\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    protected $userService;
    protected $PasswordRestTokenService;
    public function __construct(UserService $userService, PasswordRestTokenService $PasswordRestTokenService)
    {
        $this->userService = $userService;
        $this->PasswordRestTokenService = $PasswordRestTokenService;
        $this->middleware('auth:api', ['except' => ['login', 'adminLogin', 'emailVerify']]);
    }


    /**
     * student login
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * login a user
     */

    public function login(AuthRequest $request)
    {

        //validate the request
        $validated = $request->validated();

        // if ($userRegistration == AppConstants::STUDENT_ROLE) {

        // Authenticate the user
        $user = User::where('email', $validated['email'])->first();
        $userType = $this->userService->checkUserType($user);
        // dd($validated['device_id']);

        //check device id
        if ($userType == AppConstants::STUDENT_ROLE) {
            if (!isset($validated['device_id'])) {
                return ResponseHelper::error(trans('messages.device_id_required'), [], 401);
            }
            $device = $this->userService->checkDeviceId($user->id, $validated['device_id']);

            if (!$device) {

                Log::channel('authentication')->info('Device not found', [
                    'device_id' => $validated['device_id'],
                    'user_id' => $user->id ?? 'not found',
                    'email' => $validated['email'],
                    'device' => isset($validated['device']) ? $validated['device'] : 'not found',
                    'browser' => $validated['browser'] ?? 'not found',
                    'date' => date('Y-m-d H:i:s'),
                ]);

                activity()
                    ->causedBy($user)
                    ->performedOn($user)
                    ->withProperties(['password' => $validated['password'], 'device_id' => $validated['device_id'], 'device' => $validated['device'] ?? 'not found', 'browser' => $validated['browser'] ?? 'not found', 'email' => $validated['email']])
                    ->log('device_not_found');

                return ResponseHelper::error(trans('messages.device_not_found'), [], 401);
            }
        }

        if (!$user->is_active) {
            return ResponseHelper::error(trans('messages.authentication_failed'), [], 401);
        }

        if ($user && Hash::check($validated['password'], $user->password)) {
            // $user = User::find(Auth::user()->id);

            //check user is active or not
            if ($user->is_active != 1) {
                return ResponseHelper::error(trans('messages.authentication_failed'), [], 401);
            }

            $user_token = $user->createToken('appToken')->accessToken;
            //create login history
            Log::channel('authentication')->info('Login success', [
                'device_id' => $validated['device_id'],
                'user_id' => $user->id ?? 'not found',
                'email' => $validated['email'],
                'device' => $validated['device'] ?? 'not found',
                'browser' => $validated['browser'] ?? 'not found',
                'date' => date('Y-m-d H:i:s'),
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'password' => $validated['password'], 'device_id' => $validated['device_id'],
                    'device' =>  $validated['device'] ?? 'not found', 'browser' => $validated['browser'] ?? 'not found', 'email' => $validated['email']
                ])
                ->log('login_success');

            //add to log separately
            return ResponseHelper::success(trans('messages.login_success'), [
                'token' => $user_token,
                'user_type' => $userType,
                'user' => $user
            ]);
        } else {
            Log::channel('authentication')->info('Password not matched', [
                'device_id' => $validated['device_id'],
                'user_id' => $user->id ?? 'not found',
                'email' => $validated['email'],
                'device' => $validated['device'] ?? 'not found',
                'browser' => $validated['browser'] ?? 'not found',
                'date' => date('Y-m-d H:i:s'),
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties(['password' => $validated['password'], 'device_id' => $validated['device_id'], 'device' => $validated['device'] ?? 'Device not found', 'browser' => $validated['browser'] ?? 'Browser not found', 'email' => $validated['email']])
                ->log('login_failed');
            return ResponseHelper::error(trans('messages.authentication_failed'), [], 401);
        }

        //check login and return token
        if (!$token = auth()->attempt($validated)) {
            Log::channel('authentication')->info('Token not found.', [
                'device_id' => $validated['device_id'],
                'user_id' => $user->id ?? 'not found',
                'email' => $validated['email'],
                'device' => $validated['device'] ?? 'not found',
                'browser' => $validated['browser'] ?? 'not found',
                'date' => date('Y-m-d H:i:s'),
            ]);
            return ResponseHelper::error(trans('messages.Unauthorized'), [], 401);
        }

        //return token
        return $this->respondWithToken($token);
        // } else {
        //     return ResponseHelper::error(trans('authentication_failed'), [], 401);
        // }
    }

    public function respondWithToken($token)
    {
        return ResponseHelper::success(trans('messages.login_success'), [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()
        ]);
    }

    /**
     * Admin login
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * login a user
     */

    public function adminLogin(AuthRequest $request)
    {
        //validate the request
        $validated = $request->validated();


        $user = $this->userService->getUserByEmail($validated['email']);

        $userRegistration = $this->userService->checkUserType($user);

        if ($userRegistration == AppConstants::ADMIN_ROLE) {

            if (auth()->attempt($validated)) {

                $user = User::find(Auth::user()->id);

                //check user is active or not
                if ($user->is_active != 1) {
                    return ResponseHelper::error(trans('authentication_failed'), [], 401);
                }

                $user_token['token'] = $user->createToken('appToken')->accessToken;

                return ResponseHelper::success(trans('messages.login_success'), [
                    'token' => $user_token,
                    'user' => $user,
                ]);
            } else {
                return ResponseHelper::error(trans('messages.authentication_failed'), [], 401);
            }

            //check login and return token
            if (!$token = auth()->attempt($validated)) {
                return ResponseHelper::error(trans('messages.Unauthorized'), [], 401);
            }
            //return token
            return $this->respondWithToken($token);
        } else {
            return ResponseHelper::error(trans('messages.authentication_failed'), [], 401);
        }
    }

    //logout

    /**
     * logout
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * logout a user
     */

    public function logout()
    {
        $logout = $this->PasswordRestTokenService->logout();

        if ($logout) {
            return ResponseHelper::success(trans('messages.logout_success'));
        } else {
            return ResponseHelper::error(trans('messages.logout_failed'));
        }
    }

    /**
     * Email Verify
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * Email Verify
     */

    public function emailVerify(EmailVerifyRequest $request)
    {
        $validated = $request->validated();
        $verify = $this->userService->emailVerify($validated['email']);
        if ($verify['status']) {
            return ResponseHelper::success($verify['message'], $verify['data']);
        } else {
            return ResponseHelper::error(trans('messages.logout_failed'));
        }
    }
}
