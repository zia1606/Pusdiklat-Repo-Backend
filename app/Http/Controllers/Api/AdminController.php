<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index() {
        return response()->json([
            'status' => true,
            'message' => 'Multiguard Working in Admin',
        ], 200);
    }

    public function register(Request $request) {
        $validate = validator($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string'
        ]);
        
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 400);
        }
        
        try {
            $data = new Admin();
            $data->name = $request->name;
            $data->email = $request->email;
            $data->password = Hash::make($request->password);
            $data->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Admin created successfully'
            ], 200);   
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request) {
        $validate = Validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 400);
        }
        
        try {
            $data = Admin::where('email', $request->email)->first();
            
            if(!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email'
                ], 404);
            }
            
            if(!Hash::check($request->password, $data->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Password'
                ], 404);
            }
            
            $data->tokens()->delete();
            $data['token'] = $data->createToken('admin', ['admin:access'], now()->addHours(24))->plainTextToken;
            
            return response()->json([
                'status' => true,
                'message' => 'Login successfully',
                'data' => $data
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Logout Admin berhasil'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal logout: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkAuthStatus(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Admin is authenticated',
                'data' => $request->user()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Admin is not authenticated'
            ], 401);
        }
    }

    public function getDashboardStats()
    {
        try {
            $today = \Carbon\Carbon::today();
            
            return response()->json([
                'status' => true,
                'data' => [
                    'counts' => [
                        'total_users_active' => \App\Models\User::count(),
                        'total_koleksi_elektronik' => \App\Models\Koleksi::count(),
                        'total_koleksi_aktif' => \App\Models\Koleksi::where('is_active', 1)->count(),
                        'total_koleksi_inactive' => \App\Models\Koleksi::where('is_active', 0)->count(),
                    ],
                    'today_visitors' => \App\Models\BukuTamu::with('user')
                        ->whereDate('created_at', $today)
                        ->orderBy('created_at', 'desc')
                        ->get(),
                    'most_read' => \App\Models\Koleksi::orderBy('views', 'desc')
                        ->limit(5)
                        ->get()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get dashboard stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
