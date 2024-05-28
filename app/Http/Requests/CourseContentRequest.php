<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseContentRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return true;
    }

    public function rules(): array
    {
        return [
            '*.course_id' => 'required|numeric|exists:courses,id', // Assuming course_id is a numeric value and exists in courses table
            '*.week' => 'required|string', // Assuming week is a string
            '*.details' => 'required|array', // Assuming details is an array
            '*.details.*.content' => 'required|string', // Assuming content is a string
            '*.details.*.link' => 'required|url', // Assuming link is a valid URL
        ];
    }
}
