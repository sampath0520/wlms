<?php

namespace App\Exceptions;
use Illuminate\Validation\ValidationException;

trait CustomValidationHandler
{
    protected function convertValidationExceptionToResponse(ValidationException $e, $request, $message = 'Validation Error')
    {
        $errors = $e->validator->getMessageBag()->toArray();

        $customResponse = [
            'status_code' => 0,
            'status' => false,
            'message' => $message,
            'data' => []
        ];

        return response()->json(['errors' => $customResponse], 422);
    }
}
