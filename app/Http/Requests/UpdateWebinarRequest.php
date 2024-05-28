<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebinarRequest extends BaseRequest
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
            'id' => 'required|numeric|exists:webinars,id',
            'date' => 'nullable|date|after:today',
            'time' => 'nullable|date_format:H:i',
            'time_ext' => 'nullable|string|in:AM,PM',
            'duration' => 'nullable|numeric',
            // 'course' => 'nullable|numeric|exists:courses,id',
        ];
    }
}
