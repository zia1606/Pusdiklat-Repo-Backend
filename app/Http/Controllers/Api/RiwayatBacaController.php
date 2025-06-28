<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiwayatBaca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiwayatBacaController extends Controller
{
    // Menampilkan riwayat baca user yang sedang login
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $riwayatBaca = RiwayatBaca::with('koleksi')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
             
            if ($riwayatBaca->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Belum ada koleksi riwayat',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data riwayat berhasil diambil',
                'data' => $riwayatBaca
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Menyimpan riwayat baca baru
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

            // Cek apakah sudah ada di riwayat
            $existing = RiwayatBaca::where('user_id', $user->id)
                ->where('koleksi_id', $request->koleksi_id)
                ->first();

            if ($existing) {
                $existing->touch();
                return response()->json([
                    'success' => true,
                    'message' => 'Riwayat baca diperbarui',
                    'data' => $existing
                ], 200);
            }

            $riwayatBaca = RiwayatBaca::create([
                'user_id' => $user->id,
                'koleksi_id' => $request->koleksi_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Koleksi berhasil ditambahkan ke riwayat',
                'data' => $riwayatBaca
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Menghapus riwayat baca
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
            
            $riwayatBaca = RiwayatBaca::where('user_id', $user->id)
                ->where('id', $id)
                ->first();
                
            if (!$riwayatBaca) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data riwayat tidak ditemukan'
                ], 404);
            }

            $riwayatBaca->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Koleksi berhasil dihapus dari riwayat'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Menghapus semua riwayat baca user
    public function clearAll(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            RiwayatBaca::where('user_id', $user->id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Semua riwayat baca berhasil dihapus'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}