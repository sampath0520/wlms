<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ErrorLogger;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailValidateRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Http\Requests\PasswordVerificationRequest;
use App\Services\PasswordRestTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OTPEmail;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{

    protected $passwordRestTokenService;
    protected $userService;


    public function __construct(PasswordRestTokenService $passwordRestTokenService, UserService $userService)
    {
        $this->passwordRestTokenService = $passwordRestTokenService;
        $this->userService = $userService;
    }

    /**
     * Send reset password link to the user's email
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function sendResetLink(EmailValidateRequest $request)
    {
        $validated = $request->validated();
        try {
            $email = $request->email;
            $otp = rand(100000, 999999); // Generate a random 6-digit OTP

            $tokenRequest = [
                'email' => $email,
                'token' => $otp,
            ];

            $tokenDetails = $this->passwordRestTokenService->savePasswordResetToken($tokenRequest);

            if (!$tokenDetails) {
                return ResponseHelper::error(trans('messages.otp_generation_failed'));
            }

            // Send the OTP to the user's email
            // Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
            //     $message->to($email)->subject('OTP for Password Reset');
            // });

            // Send the OTP email
            $email = Mail::to($email)->send(new OTPEmail($otp));

            return ResponseHelper::success(trans('messages.otp_sent'));
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return ResponseHelper::error('OTP sending failed');
        }
    }

    /**
     * Verify the OTP sent to the user's email
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function verifyOtp(OtpVerifyRequest $request)
    {
        $validated = $request->validated();
        $otp = $request->otp;
        $email = $request->email;

        $passwordResetToken = $this->passwordRestTokenService->getPasswordResetToken($otp, $email);


        if ($passwordResetToken) {
            // Check if the OTP is within the valid time frame (1 hour)
            //current time

            $expirationTime = Carbon::parse($passwordResetToken->created_at)->addMinutes(60);

            if (Carbon::now()->lte($expirationTime)) {
                // OTP is valid
                return ResponseHelper::success(trans('messages.otp_verified'), ['email' => $passwordResetToken->email, 'otp' => $passwordResetToken->token]);
            } else {
                // OTP is expired
                return ResponseHelper::error(trans('messages.otp_expired'));
            }
        } else {
            // Invalid OTP
            return ResponseHelper::error(trans('messages.otp_invalid'));
        }
    }


    /**
     * Reset the user's password
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function resetPassword(PasswordVerificationRequest $request)
    {

        $validated = $request->validated();

        try {
            $password = $request->password;
            $email = $request->email;

            DB::beginTransaction();
            $this->userService->resetPassword($email, $password);

            $this->passwordRestTokenService->deletePasswordResetToken($email);
            DB::commit();


            return ResponseHelper::success(trans('messages.password_reset_success'));
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            DB::rollBack();
            // Return an error response to the user
            return ResponseHelper::error(trans('messages.password_reset_failed'));
        }
    }
}
