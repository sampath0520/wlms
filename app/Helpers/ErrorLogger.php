<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class ErrorLogger
{
    public static function logError(\Exception $e)
    {
        $errorMessage = 'An error occurred: ' . $e->getMessage();
        $fileAndLine = 'File: ' . $e->getFile() . ' Line: ' . $e->getLine();
        Log::error($errorMessage . ' ' . $fileAndLine);
    }
}
