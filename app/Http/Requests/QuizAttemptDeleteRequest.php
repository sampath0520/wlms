<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizAttemptDeleteRequest extends BaseRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'quiz_id' => 'required|integer|exists:quizzes,id',
            //check attempt is integer and exists in quiz_attempts table for this quiz and user
            'attempt' => 'required|integer|exists:quiz_status,attempts,quiz_id,' . $this->quiz_id . ',user_id,' . $this->user_id,
        ];
    }
}
