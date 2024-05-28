<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RulesPassword;

class PasswordVerificationRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            "email" => "required|email|exists:users,email",
            // "password" => "required|string|min:8",
            // "confirm_password" => "required|string|min:8|same:password",
            'password' => ['required', 'string', 'same:confirm_password', RulesPassword::min(8)->mixedCase()->numbers()->symbols()],
            "otp" => "required|exists:password_reset_tokens,token,email," . $this->email,

        ];
    }
}
