<?php

namespace App\Http\Controllers\Api;

use App\Models\Favorit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FavoritController extends Controller
{
    // Menampilkan favorit user yang sedang login
    public function index(Request $request)
    {
        try {
            // Gunakan request->user() untuk mendapatkan user yang terautentikasi
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $favorit = Favorit::with('koleksi')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            if ($favorit->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Belum ada koleksi favorit',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data favorit berhasil diambil',
                'data' => $favorit
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Menyimpan favorit baru
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            $request->validate([
                'koleksi_id' => 'required|exists:koleksi,id',
            ]);

            // Cek apakah sudah ada di favorit
            $existing = Favorit::where('user_id', $user->id)
                ->where('koleksi_id', $request->koleksi_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koleksi sudah ada di daftar favorit'
                ], 200);
            }

            $favorit = Favorit::create([
                'user_id' => $user->id,
                'koleksi_id' => $request->koleksi_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Koleksi berhasil ditambahkan ke favorit',
                'data' => $favorit
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Menghapus favorit
    public function destroy($id, Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            $favorit = Favorit::where('user_id', $user->id)
                ->where('id', $id)
                ->first();
                
            if (!$favorit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data favorit tidak ditemukan'
                ], 404);
            }

            $favorit->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Favorit berhasil dihapus'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Menghapus favorit berdasarkan koleksi_id
    public function removeByKoleksi($koleksi_id, Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            $favorit = Favorit::where('user_id', $user->id)
                ->where('koleksi_id', $koleksi_id)
                ->first();
                
            if (!$favorit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data favorit tidak ditemukan'
                ], 404);
            }
                
            $favorit->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Favorit berhasil dihapus berdasarkan koleksi'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Cek apakah koleksi sudah difavoritkan
    public function checkFavorite($koleksi_id, Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                    'is_favorite' => false
                ], 401);
            }
            
            $isFavorite = Favorit::where('user_id', $user->id)
                ->where('koleksi_id', $koleksi_id)
                ->exists();
                
            return response()->json([
                'success' => true,
                'message' => 'Status favorit berhasil diperiksa',
                'is_favorite' => $isFavorite
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'is_favorite' => false
            ], 500);
        }
    }
}