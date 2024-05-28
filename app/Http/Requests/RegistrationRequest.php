<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class RegistrationRequest extends BaseRequest
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
            // 'first_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:users,email',
            // // 'password' => 'required|string|min:8|same:confirm_password',
            // 'password' => ['required', 'string', 'same:confirm_password', Password::min(8)->mixedCase()->numbers()->symbols()],

            // //if is_free is 1 then no need to validate card details
            // 'is_free' => 'required|boolean',

            // 'card_no' => 'required|numeric|digits_between:12,19',
            // 'exp_month' => 'required|numeric|min:1|max:12',
            // 'exp_year' => 'required|numeric|digits:4',
            // 'cvc' => 'required|numeric|digits_between:3,4',
            // 'course_id' => 'required|numeric|exists:courses,id',
            // 'device_id' => 'required|string',


            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'same:confirm_password', Password::min(8)->mixedCase()->numbers()->symbols()],
            'is_free' => 'required|boolean',
            'card_no' => 'required_if:is_free,0|numeric|digits_between:12,19',
            'exp_month' => 'required_if:is_free,0|numeric|min:1|max:12',
            'exp_year' => 'required_if:is_free,0|numeric|digits:4',
            'cvc' => 'required_if:is_free,0|numeric|digits_between:3,4',
            'course_id' => 'required|numeric|exists:courses,id',
            'device_id' => 'required|string',
            'type' => 'required|in:1,2', //dual course
            'currency_id' => 'nullable|required_if:is_free,0|integer|exists:courses_currencies,currency_id',
            'promo_code' => 'nullable|string|exists:promo_codes,promo_code',
        ];

        $validatedData['currency_id'] = 1;
    }
}
