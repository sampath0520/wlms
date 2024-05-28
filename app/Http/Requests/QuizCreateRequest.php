<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizCreateRequest extends BaseRequest
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
            'name' => 'required|string|max:255|unique:quizzes',
            'course_id' => 'required|exists:courses,id',
            'description' => 'nullable|string',
            'week' => 'required|string',
            'no_of_questions' => 'required|integer',
            'duration' => 'required|integer',
            'no_of_attempts' => 'required|integer',
            //check course id and week is unique
            // 'week' => Rule::unique('quizzes')->where(function ($query) {
            //     return $query->where('week', $this->week)->where('course_id', $this->course_id);
            // }),
        ];
    }
}
