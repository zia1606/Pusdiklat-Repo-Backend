<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Load role relationship jika belum di-load
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if (!$user->hasRole($role)) {
            return response()->json([
                'error' => 'Unauthorized - Insufficient permissions'
            ], 403);
        }

        return $next($request);
    }
}