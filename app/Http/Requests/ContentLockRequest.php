<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentLockRequest extends BaseRequest
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
            'content_id' => 'required|exists:course_contents,id',
            'is_locked' => 'required|boolean',
        ];
    }
}
