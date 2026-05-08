<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Periksa jika user sudah login dan akunnya dihapus (soft delete)
        if ($request->user() && $request->user()->trashed()) {
            // Hapus semua token akses
            $request->user()->tokens()->delete();
            
            return response()->json([
                'status' => false,
                'message' => 'Your account has been deactivated'
            ], 403);
        }

        return $next($request);
    }
}