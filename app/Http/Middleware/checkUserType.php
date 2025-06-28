<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class checkUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, String $name): Response
    {
        $user = $request->user();
        if($user && $user->currentAccessToken()->name=$name) {
            return $next($request);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }
}
