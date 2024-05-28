<?php

namespace App\Exceptions;

use App\Helpers\ResponseHelper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Exceptions\UnauthorizedException;
// use App\Exceptions\CustomValidationHandler;

class Handler extends ExceptionHandler
{
    // use CustomValidationHandler;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

   

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return ResponseHelper::error('Unauthenticated', [], 401);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof UnauthorizedException) {

            return ResponseHelper::error(trans('Unauthorized: Cannot perform this action, permission denied'), [], 401);
        }

        return parent::render($request, $exception);
    }
}
