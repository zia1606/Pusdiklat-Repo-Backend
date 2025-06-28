<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function unauthenticated($request, array $guard) {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401));
        }
    
        return redirect()->guest(route('login'));
    }
}
