<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscountPriceRequest extends BaseRequest
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
            'promo_code' => 'required|exists:promo_codes,promo_code',
            'course_id' => 'required|exists:courses,id',
            'currency_id' => 'required|exists:courses_currencies,currency_id',
            'course_type' => 'required|in:1,2',
        ];
    }
}
