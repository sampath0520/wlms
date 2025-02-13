<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrientationCreateRequest extends BaseRequest
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
            'course_id' => 'required|integer|exists:courses,id',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'message' => 'required|string',
        ];
    }
}
