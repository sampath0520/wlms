<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Models\QuizStatus;
use Illuminate\Foundation\Http\FormRequest;

class StartQuizRequest  extends BaseRequest
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
            'attempt' => 'required|integer',
        ];
    }
}
