<?php

namespace App\Services;

use App\Constants\AppConstants;
use App\Helpers\ErrorLogger;
use App\Models\Course;
use App\Models\PasswordResetToken;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Providers\LogServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class PasswordRestTokenService
{

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * save password reset token
     */
    public function savePasswordResetToken($tokenRequest)
    {
        try {

            //create or update password reset token
            $passwordResetToken = PasswordResetToken::updateOrCreate(
                ['email' => $tokenRequest['email']],
                [
                    'token' => $tokenRequest['token'],
                    'created_at' => now(), // Manually set the created_at timestamp
                ]
            );

            return $passwordResetToken;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return false;
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * fetch password reset token
     */

    public function getPasswordResetToken($otp, $email)
    {

        try {
            $passwordResetToken = PasswordResetToken::where('email', $email)->where('token', $otp)->first();
            return $passwordResetToken;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * delete password reset token
     */

    public function deletePasswordResetToken($email)
    {

        try {
            $passwordResetToken = PasswordResetToken::where('email', $email)->delete();
            return $passwordResetToken;
        } catch (\Exception $e) {
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $data[]
     * @return string []
     * log Out from all devices
     */

    public function logout()
    {

        try {
            $user = auth()->user();
            if ($user) {
                DB::beginTransaction();
                $tokens =  $user->tokens->pluck('id');
                Token::whereIn('id', $tokens)->update(['revoked' => true]);
                RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => true]);
                DB::commit();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            DB::rollback();
            ErrorLogger::logError($e);
            return null; // Return null instead of false
        }
    }
}
