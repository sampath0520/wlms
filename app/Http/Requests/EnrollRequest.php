<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class EnrollRequest extends BaseRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'is_free' => 'required|boolean',
            'card_no' => 'required_if:is_free,0|numeric|digits_between:12,19',
            'exp_month' => 'required_if:is_free,0|numeric|min:1|max:12',
            'exp_year' => 'required_if:is_free,0|numeric|digits:4',
            'cvc' => 'required_if:is_free,0|numeric|digits_between:3,4',

            'course_id' => 'required|numeric|exists:courses,id',
            'currency_id' => 'required_if:is_free,0|integer|exists:courses_currencies,currency_id',
            'promo_code' => 'nullable|string|exists:promo_codes,promo_code',
        ];
    }
}
