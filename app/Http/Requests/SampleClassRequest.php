<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SampleClassRequest extends BaseRequest
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
            'title' => 'required|string|unique:sample_classes,title,' . $this->id,
            'sub_title' => 'nullable|string',
            'video' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:200000',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
