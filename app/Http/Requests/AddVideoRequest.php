<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddVideoRequest extends BaseRequest
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
            'title' => 'required|string|unique:videos',
            'video' => 'required|mimes:mp4,mov,ogg,qt|max:2097152',
            'thumbnail' => 'required|mimes:jpeg,jpg,png|max:20000',
        ];
    }
}
