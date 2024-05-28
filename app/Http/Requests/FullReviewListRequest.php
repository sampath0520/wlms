<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FullReviewListRequest extends BaseRequest
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
            'course_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== 'All' && !\App\Models\Course::where('id', $value)->exists()) {
                        $fail('The selected ' . $attribute . ' is invalid.');
                    }
                },
            ],
            'approved_status' => 'required|in:0,1,All',
        ];
    }
}
