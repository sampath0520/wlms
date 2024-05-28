<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\VideoAccess;
use App\Http\Controllers\VideoController;

class CheckVideoAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {

        $token = $request->token;

        if (!$token || !$this->isValidToken($token)) {
            abort(403, 'Unauthorized access to the video.');
        }

        return $next($request);
    }

    private function isValidToken($token)
    {
        $videoAccess = VideoAccess::where('token', $token)->Where('is_used', 0)->first();

        if ($videoAccess) {
            // Mark the token as used
            $videoAccess->update(['is_used' => 1]);
            return true;
        }

        return false;
    }
}
