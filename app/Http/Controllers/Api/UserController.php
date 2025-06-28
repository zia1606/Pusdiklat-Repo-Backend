<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Dotenv\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index() {
        return response() -> json([
            'status' => true,
            'message' => 'Multiguard Auth Working now',
        ], 200);
    }

    public function register(Request $request) {
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

    public function login(Request $request) {
        $validate = Validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        
        if ($validate -> fails()) {
            return response() -> json([
                'status' => false,
                'message' => $validate->errors()
            ], 400);
        }
        try {
            $data = User::where('email', $request->email)->first();
            if(!$data) {
                return response() -> json([
                    'status' => false,
                    'message' => 'Invalid email'
                ], 404);
            }
            if(!Hash::check($request->password, $data->password)) {
                return response() -> json([
                    'status' => false,
                    'message' => 'Invalid Password',
                ], 404);
            }
            $data->tokens()->delete();
            // $data['token'] = $data->createToken('user')->plainTextToken;
            $data['token'] = $data->createToken('user', ['*'], now()->addHours(24))->plainTextToken;
            return response() -> json([
                'status' => true,
                'message' => 'Login succesfully',
                'data' =>$data
            ], 200);
        }
        catch (\Exception $e) {
            return response() -> json([
                'status' => false,
                'message' => $e -> getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Hapus token yang sedang digunakan
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
    
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }
    
    public function handleGoogleCallback(Request $request)
{
    try {
        $googleUser = Socialite::driver('google')->user();
        
        // Debug: Lihat data yang diterima dari Google
        Log::debug('Google User Data:', [
            'id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'avatar' => $googleUser->getAvatar()
        ]);

        // Cek apakah user sudah ada
        $existingUser = User::where('email', $googleUser->getEmail())->first();
        
        if ($existingUser) {
            // User sudah ada - hanya update data Google, JANGAN update password
            $existingUser->update([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                // TIDAK update password - biarkan password yang sudah ada
            ]);
            
            $user = $existingUser;
            Log::debug('Updated existing user (password preserved):', $user->toArray());
        } else {
            // User baru - buat dengan password random
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(24)), // Hanya untuk user baru
                'role_id' => 2 // Default role user
            ]);
            
            Log::debug('Created new user:', $user->toArray());
        }

        // Pastikan data tersimpan
        Log::debug('Final User Data:', $user->toArray());

        $user->tokens()->delete();
        $token = $user->createToken('google-token', ['*'], now()->addHours(24));

        // Redirect ke frontend dengan token sebagai parameter
        return redirect(env('FRONTEND_URL').'/auth/callback?'.http_build_query([
            'token' => $token->plainTextToken,
            'user' => json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role_id' => $user->role_id
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

    public function forgot_password() {
    // Ini hanya menampilkan view (jika diperlukan)
    // Untuk API, mungkin tidak diperlukan karena frontend menangani tampilan
    return response()->json([
        'status' => true,
        'message' => 'Halaman lupa password'
    ]);
}

public function forgot_password_act(Request $request) {
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

            // Generate new token
            $token = Str::random(60);
            
            PasswordResetToken::updateOrCreate(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            // Send email
            $resetUrl = env('FRONTEND_URL').'/reset-password/'.$token;
            Mail::to($request->email)->send(new ResetPasswordMail($token, $resetUrl));

            return response()->json([
                'status' => true,
                'message' => 'Silakan cek email Anda untuk link reset password.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reset_password_act(Request $request) {
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

    public function validasi_forgot_password($token) {
        try {
            $tokenData = PasswordResetToken::where('token', $token)->first();
            
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
                    'email' => $tokenData->email,
                    'token' => $tokenData->token
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
}
