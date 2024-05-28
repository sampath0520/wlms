<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthRequest extends BaseRequest
{
    // protected function failedValidation(Validator $validator)
    // {
    //     throw new HttpResponseException(
    //         response()->json([
    //             'errors' => $validator->errors(),
    //         ], 422)
    //     );
    // }

    public function authorize()
    {
        return true;
    }


    public function rules()
    {

        return [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8',
            'device_id' => 'nullable|string',
            "device" => 'nullable|string',
            "browser" => 'nullable|string',
        ];
    }
}
