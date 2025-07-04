<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminRegistrationMail;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register User
    public function registerUser(Request $request) {
        $validate = validator($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string'
        ]);
        
        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 400);
        }
        
        try {
            $userRole = Role::where('name', 'user')->first();
            
            if (!$userRole) {
                return response()->json([
                    'status' => false,
                    'message' => 'User role not found'
                ], 500);
            }
            
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->role_id = $userRole->id;
            $user->save();
            
            return response()->json([
                'status' => true,
                'message' => 'User created successfully'
            ], 200);   
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Register Admin
    // Register Admin
// Update the registerAdmin method in AuthController
public function registerAdmin(Request $request) {
    $validate = validator($request->all(), [
        'name' => 'required',
        'email' => 'required|email|unique:users,email',
        // Remove password from validation since we'll generate it
    ]);
    
    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validate->errors()
        ], 400);
    }
    
    try {
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            return response()->json([
                'status' => false,
                'message' => 'Admin role not found'
            ], 500);
        }
        
        // Generate random 6-character password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = substr(str_shuffle($chars), 0, 6);
        
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($password);
        $user->role_id = $adminRole->id;
        $user->save();
        
        // Send email with registration details
        Mail::to($user->email)->send(new AdminRegistrationMail(
            $user->name,
            $user->email,
            $password
        ));
        
        return response()->json([
            'status' => true,
            'message' => 'Admin created successfully. Registration details have been sent to the email.',
            'data' => [
                'email' => $user->email,
                'password' => $password // Return the plain password (only this time)
            ]
        ], 200);   
    }
    catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    // Login (untuk admin dan user)
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
            $user = User::with('role')->where('email', $request->email)->first();
            
            if(!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email'
                ], 404);
            }
            
            if(!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Password'
                ], 404);
            }
            
            $user->tokens()->delete();
            
            // Buat token berdasarkan role
            $tokenName = $user->role->name;
            $abilities = $user->isAdmin() ? ['admin:access', 'user:access'] : ['user:access'];
            
            $token = $user->createToken($tokenName, $abilities, now()->addHours(24));
            
            return response()->json([
                'status' => true,
                'message' => 'Login successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token->plainTextToken
                ],
                'role' => $user->role->name
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
                'message' => 'Logout berhasil'
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
            $user = $request->user()->load('role');
            
            return response()->json([
                'status' => true,
                'message' => 'User is authenticated',
                'data' => $user,
                'role' => $user->role->name
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'User is not authenticated'
            ], 401);
        }
    }

    public function getUserCount()
    {
        try {
            $count = User::where('role_id', '!=', 1)->count(); // Exclude admin
            
            return response()->json([
                'status' => true,
                'message' => 'User count retrieved successfully',
                'data' => $count
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get user count: ' . $e->getMessage()
            ], 500);
        }
    }
}