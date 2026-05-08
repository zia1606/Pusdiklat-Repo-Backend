<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\BukuTamu;
use App\Models\RiwayatBaca;
use App\Models\Favorit;
use Illuminate\Http\Request;

use App\Models\ActivityLog;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user' => function($query) {
            $query->select('id', 'name', 'email', 'role_id')->with('role:id,name');
        }]);

        // Filter by Category
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        // Filter by Search (User name or Description)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }

    public function getLoginLogs(Request $request) { $request->merge(['category' => 'Login']); return $this->index($request); }
    public function getBukuTamuLogs(Request $request) { $request->merge(['category' => 'Buku Tamu']); return $this->index($request); }
    public function getBacaLogs(Request $request) { $request->merge(['category' => 'Riwayat Baca']); return $this->index($request); }
    public function getFavoritLogs(Request $request) { $request->merge(['category' => 'Favorit']); return $this->index($request); }
}
