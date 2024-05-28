<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddWebinarRequest extends BaseRequest
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
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:h:i|after:desired_time',
            'time_ext' => 'required|string|in:AM,PM',
            'duration' => 'required|numeric',
            // 'course' => 'required|numeric|exists:courses,id',
        ];
    }
}
