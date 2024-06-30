<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckExternalToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $validToken = env('EXTERNAL_SYSTEM_TOKEN');

        if ($token !== $validToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
