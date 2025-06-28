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
}
