<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseUpdateRequest extends BaseRequest
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
            'id' => 'required|numeric|exists:courses,id',
            'name' => 'required|string|max:255|unique:courses,name,' . $this->id,
            'description' => 'nullable|string',
            // 'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'lecturer' => 'required|string|max:255',
            'duration' => 'required|numeric',
            'is_free' => 'required|numeric|in:0,1',
            'currency_id.*' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if (!Currency::find($value) && $value != 0) {
                        $fail('The ' . $attribute . ' is invalid.');
                    }
                },
            ],
            'price.*' => 'required|numeric',
            'other_price.*' => 'required|numeric',
            'is_default.*' => 'required|numeric|in:0,1',
            // 'is_top_banner' => 'nullable|numeric|in:0,1',
            'is_invisible' => 'required|numeric|in:0,1',
        ];
    }
}
