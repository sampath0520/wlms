<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageCreateRequest extends BaseRequest
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
            'attachment*' => 'nullable|array|max:5|required_without:message|mimes:jpeg,jpg,png,gif,doc,docx,pdf,txt',
            'message' => 'required|string',
            'to_user' => 'nullable|exists:users,id',
            // 'attachment.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,doc,docx,pdf,txt|max:2048|array|max:5',
        ];
    }
}
