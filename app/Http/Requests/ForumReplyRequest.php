<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForumReplyRequest extends BaseRequest
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
            'forum_id' => 'required|integer|exists:forums,id',
            'reply' => 'nullable|string',
            'images' => 'nullable|array|max:5',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'forum_id.required' => 'The forum id field is required',
            'forum_id.integer' => 'The forum id must be an integer',
            'forum_id.exists' => 'The forum id must exist in the forums table',
            'images.*.image' => 'The file must be an image (jpeg, png, jpg, gif, svg)',
            'images.*.mimes' => 'The file must be a file of type: jpeg, png, jpg, gif, svg',
            'images.*.max' => 'The file may not be greater than 2048 kilobytes',
            'images.*.required' => 'The file field is required',
            'images.*.array' => 'The file must be an array',
            'images.max' => 'The number of files may not be greater than 5',
        ];
    }
}
