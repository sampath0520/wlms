<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class BaseRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $customResponse = [
            'status_code' => 0,
            'status' => false,
            'message' => $validator->errors()->first(), // Use the first validation error message
            'data' => []
        ];

        throw new HttpResponseException(response()->json($customResponse, JsonResponse::HTTP_BAD_REQUEST));
    }
}
