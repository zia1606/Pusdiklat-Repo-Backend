<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $token = $request->user()->currentAccessToken();
        
        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        if (Carbon::now()->gt($token->expires_at)) {
            $token->delete();
            return response()->json([
                'status' => false,
                'message' => 'Token expired'
            ], 401);
        }

        return $next($request);
    }
}