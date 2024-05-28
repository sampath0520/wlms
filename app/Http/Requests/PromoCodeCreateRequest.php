<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;

class PromoCodeCreateRequest extends BaseRequest
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
            'promo_code' => 'required|string|unique:promo_codes,promo_code|regex:/^[A-Za-z0-9]{5,9}$/',
            'start_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'expiration_date' => 'required|date|date_format:Y-m-d|after:start_date',
            'discount_type' => 'required|integer|in:1,2',
            // 'amount' => 'required|numeric',
            // 'course_id' => 'required|integer|exists:courses,id',
            'is_one_time' => 'required|integer|in:0,1',
            'price' => 'required|array',
            'price.*.amount' => 'required|numeric',
            'price.*.currency_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if (!Currency::find($value) && $value != 0) {
                        $fail('The ' . $attribute . ' is invalid.');
                    }
                },
            ],
        ];
    }
}
