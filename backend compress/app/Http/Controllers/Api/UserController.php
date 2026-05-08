<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Dotenv\Validator;
use App\Models\Favorit;
use App\Models\RiwayatBaca;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::withTrashed()->with('role')->get();
            
            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trashed()
{
    try {
        $users = User::onlyTrashed()->with('role')->get();
        
        return response()->json([
            'status' => true,
            'message' => 'Trashed users retrieved successfully',
            'data' => $users
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to get trashed users: ' . $e->getMessage()
        ], 500);
    }
}

public function showProfile(Request $request)
{
    try {
        $user = $request->user();
        
        return response()->json([
            'status' => true,
            'message' => 'User profile retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar,
                'account_created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'role' => $user->role->name ?? null
            ]
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to get user profile: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update user profile
 */
public function updateProfile(Request $request)
{
    try {
        // Validasi langsung di controller
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$request->user()->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Update data yang divalidasi
        $user->fill($validated);
        
        // Jika email diubah, reset verifikasi email
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            // Kirim email verifikasi baru jika diperlukan
        }
        
        $user->save();
        
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar,
                'account_created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'role' => $user->role->name ?? null
            ]
        ], 200);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to update profile: ' . $e->getMessage()
        ], 500);
    }
}

    public function updateRole(Request $request, User $user)
    {
        try {
            // Prevent changing role of current admin
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot change your own role'
                ], 403);
            }
            
            $user->role_id = $request->role_id;
            $user->save();
            
            return response()->json([
                'status' => true,
                'message' => 'User role updated successfully',
                'data' => $user
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user role: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function destroy(Request $request, User $user)
{
    try {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete yourself'
            ], 403);
        }
        
        DB::transaction(function () use ($user) {
            // Soft delete related data
            $user->favorites()->delete();
            $user->readingHistories()->delete();
            
            // Soft delete user
            $user->delete();
        });
        
        return response()->json([
            'status' => true,
            'message' => 'User berhasil dinonaktifkan'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to delete user: ' . $e->getMessage()
        ], 500);
    }
}

    public function bulkDestroy(Request $request)
{
    try {
        $userIds = $request->user_ids;
        
        if (in_array($request->user()->id, $userIds)) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete yourself'
            ], 403);
        }
        
        DB::transaction(function () use ($userIds) {
            // Soft delete related data
            Favorit::whereIn('user_id', $userIds)->delete();
            RiwayatBaca::whereIn('user_id', $userIds)->delete();
            
            // Soft delete users
            User::whereIn('id', $userIds)->delete();
        });
        
        return response()->json([
            'status' => true,
            'message' => 'Users deleted successfully'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to delete users: ' . $e->getMessage()
        ], 500);
    }
}

public function restore($userId)
{
    try {
        DB::transaction(function () use ($userId) {
            // Restore user
            User::withTrashed()->where('id', $userId)->restore();
            
            // Restore related data
            Favorit::withTrashed()->where('user_id', $userId)->restore();
            RiwayatBaca::withTrashed()->where('user_id', $userId)->restore();
        });
        
        return response()->json([
            'status' => true,
            'message' => 'User berhasil di-restore'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to restore user: ' . $e->getMessage()
        ], 500);
    }
}

    public function register(Request $request) 
    {
        $validate=validator($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string'
        ]);
        if ($validate -> fails()) {
            return response() ->json([
                'status' => false,
                'message' => $validate->errors()
            ], 400);
        }
        try {
            $data=new User();
            $data->name=$request->name;
            $data->email=$request->email;
            $data->password=Hash::make($request->password);
            $data->save();
            return response() -> json([
                'status' => true,
                'message' => 'User created successfully'
            ], 200);   
        }
        catch (\Exception $e) {
            return response() -> json([
                'status' => false,
                'message' => $e -> getMessage()
            ], 500);
        }
    }

    // public function login(Request $request) 
    // {
    //     $validate = Validator($request->all(), [
    //         'email' => 'required|email',
    //         'password' => 'required|string'
    //     ]);
        
    //     if ($validate -> fails()) {
    //         return response() -> json([
    //             'status' => false,
    //             'message' => $validate->errors()
    //         ], 400);
    //     }
    //     try {
    //         $data = User::where('email', $request->email)->first();
    //         if(!$data) {
    //             return response() -> json([
    //                 'status' => false,
    //                 'message' => 'Invalid email'
    //             ], 404);
    //         }
    //         if(!Hash::check($request->password, $data->password)) {
    //             return response() -> json([
    //                 'status' => false,
    //                 'message' => 'Invalid Password',
    //             ], 404);
    //         }
    //         $data->tokens()->delete();
    //         // $data['token'] = $data->createToken('user')->plainTextToken;
    //         $data['token'] = $data->createToken('user', ['*'], now()->addHours(24))->plainTextToken;
    //         return response() -> json([
    //             'status' => true,
    //             'message' => 'Login succesfully',
    //             'data' =>$data
    //         ], 200);
    //     }
    //     catch (\Exception $e) {
    //         return response() -> json([
    //             'status' => false,
    //             'message' => $e -> getMessage()
    //         ], 500);
    //     }
    // }

    // public function logout(Request $request)
    // {
    //     try {
    //         // Hapus token yang sedang digunakan
    //         $request->user()->currentAccessToken()->delete();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Logout berhasil'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Gagal logout: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }
 
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                $existingUser->update([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
                $user = $existingUser;
            } else {
                $user = User::create([
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(24)),
                    'role_id' => 2 // Default role user
                ]);
            }

            $user->tokens()->delete();
            $token = $user->createToken('google-token', ['*'], now()->addHours(24));

            return redirect(env('FRONTEND_URL').'/auth/callback?'.http_build_query([
                'token' => $token->plainTextToken,
                'user' => json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role_id' => $user->role_id,
                    'role' => $user->role // Make sure this relation is loaded
                ])
            ]));
        } catch (\Exception $e) {
            Log::error('Google Auth Error:', ['error' => $e->getMessage()]);
            return redirect(env('FRONTEND_URL').'/login?error='.urlencode('Google authentication failed'));
        }
    }

    public function checkAuthStatus(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'User is authenticated',
                'data' => $request->user()
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
            $count = User::count();
            
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

    public function forgot_password() 
    {
    // Ini hanya menampilkan view (jika diperlukan)
    // Untuk API, mungkin tidak diperlukan karena frontend menangani tampilan
    return response()->json([
        'status' => true,
        'message' => 'Halaman lupa password'
    ]);
    }

    public function forgot_password_act(Request $request) 
{
    $validate = validator($request->all(), [
        'email' => 'required|email|exists:users,email'
    ]);
    
    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->first()
        ], 400);
    }

    try {
        // Check if token already exists and is still valid
        $existingToken = PasswordResetToken::where('email', $request->email)
            ->where('created_at', '>', now()->subHour())
            ->first();
            
        if ($existingToken) {
            return response()->json([
                'status' => false,
                'message' => 'Link reset password sudah dikirim. Silakan cek email Anda.'
            ], 429);
        }

        // Generate new token - pastikan tidak mengandung karakter problematik
        $token = Str::random(60);
        
        // Simpan token ke database
        PasswordResetToken::updateOrCreate(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        // Kirim email dengan URL yang di-encode dengan benar
        $resetUrl = env('FRONTEND_URL').'/reset-password/'.urlencode($token);
        Mail::to($request->email)->send(new ResetPasswordMail($token, $resetUrl));

        return response()->json([
            'status' => true,
            'message' => 'Silakan cek email Anda untuk link reset password.'
        ], 200);
        
    } catch (\Exception $e) {
        Log::error('Forgot password error:', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

    public function reset_password_act(Request $request) 
    {
        Log::info('Request Data:', $request->all());
        
        $validate = validator($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed'
        ]);
        
        if ($validate->fails()) {
            Log::error('Validation Errors:', $validate->errors()->toArray());
            return response()->json([
                'status' => false,
                'message' => $validate->errors()->first(),
                'errors' => $validate->errors()
            ], 400);
        }

        try {
            // Validasi token
            $tokenData = PasswordResetToken::where('email', $request->email)
                ->where('token', $request->token)
                ->first();
                
            if (!$tokenData) {
                Log::error('Token not found for email:', ['email' => $request->email, 'token' => $request->token]);
                return response()->json([
                    'status' => false,
                    'message' => 'Token tidak valid atau sudah kadaluarsa'
                ], 404);
            }

            // Cek apakah token sudah kadaluarsa (1 jam)
            if ($tokenData->created_at < now()->subHour()) {
                Log::error('Token expired:', ['created_at' => $tokenData->created_at]);
                PasswordResetToken::where('email', $request->email)->delete();
                return response()->json([
                    'status' => false,
                    'message' => 'Token sudah kadaluarsa'
                ], 404);
            }

            // Update password user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                Log::error('User not found:', ['email' => $request->email]);
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Hapus token yang sudah digunakan
            PasswordResetToken::where('email', $request->email)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Reset Password Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // public function validasi_forgot_password($token) 
    // {
    //     try {
    //         $tokenData = PasswordResetToken::where('token', $token)->first();
            
    //         if (!$tokenData) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Token tidak valid atau sudah kadaluarsa'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Token valid',
    //             'data' => [
    //                 'email' => $tokenData->email,
    //                 'token' => $tokenData->token
    //             ]
    //         ], 200);
            
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function validasi_forgot_password($token) 
{
    try {
        $decodedToken = urldecode($token);
        $tokenData = PasswordResetToken::select(['email', 'token']) // Explicit select
            ->whereRaw('BINARY token = ?', [trim($decodedToken)])
            ->where('created_at', '>=', now()->subHour())
            ->first();
        
        if (!$tokenData) {
            return response()->json([
                'status' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Token valid',
            'data' => [
                'email' => $tokenData->email, // Pastikan ini string
                'token' => $tokenData->token
            ]
        ], 200);
        
    } catch (\Exception $e) {
        Log::error('Token validation error:', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}
    
}
