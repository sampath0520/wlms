<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizUpdateRequest extends BaseRequest
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
            'quiz_id' => 'required|exists:quizzes,id',
            'name' => 'required|string|unique:quizzes,name,' . $this->quiz_id . ',id,course_id,' . $this->course_id,
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
