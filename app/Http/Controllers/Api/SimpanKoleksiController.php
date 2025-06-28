<?php

namespace App\Http\Controllers\Api;

use App\Models\SimpanKoleksi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SimpanKoleksiController extends Controller
{
    // Menampilkan koleksi yang disimpan oleh user
    public function index()
    {
        $user = Auth::user();
        
        $simpanKoleksi = SimpanKoleksi::with(['koleksi' => function($query) {
                $query->select('id', 'judul', 'penulis', 'tahun_terbit', 'dokumen_pdf');
            }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'user_id', 'koleksi_id', 'created_at']);
            
        if ($simpanKoleksi->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada koleksi yang disimpan',
                'data' => []
            ], 200);
        }

        $transformedData = $simpanKoleksi->map(function($item) {
            return [
                'id' => $item->id,
                'koleksi_id' => $item->koleksi_id,
                'created_at' => $item->created_at,
                'koleksi' => $item->koleksi ? [
                    'judul' => $item->koleksi->judul,
                    'penulis' => $item->koleksi->penulis,
                    'tahun_terbit' => $item->koleksi->tahun_terbit,
                    'dokumen_pdf' => $item->koleksi->dokumen_pdf
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data koleksi yang disimpan berhasil diambil',
            'data' => $transformedData
        ], 200);
    }

    // Menyimpan koleksi
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'koleksi_id' => 'required|exists:koleksi,id',
        ]);

        // Cek apakah sudah disimpan
        $existing = SimpanKoleksi::where('user_id', $user->id)
            ->where('koleksi_id', $request->koleksi_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Koleksi sudah ada di daftar simpan'
            ], 200);
        }

        $simpanKoleksi = SimpanKoleksi::create([
            'user_id' => $user->id,
            'koleksi_id' => $request->koleksi_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil disimpan',
            'data' => $simpanKoleksi
        ], 201);
    }

    // Menghapus koleksi yang disimpan
    public function destroy($id)
    {
        $user = Auth::user();
        $simpanKoleksi = SimpanKoleksi::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$simpanKoleksi) {
            return response()->json([
                'success' => false,
                'message' => 'Data simpan koleksi tidak ditemukan'
            ], 404);
        }
            
        $simpanKoleksi->delete();
        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil dihapus dari simpan'
        ], 200);
    }
    
    // Menghapus berdasarkan koleksi_id
    public function removeByKoleksi($koleksi_id)
    {
        $user = Auth::user();
        $simpanKoleksi = SimpanKoleksi::where('user_id', $user->id)
            ->where('koleksi_id', $koleksi_id)
            ->first();

        if (!$simpanKoleksi) {
            return response()->json([
                'success' => false,
                'message' => 'Data simpan koleksi tidak ditemukan'
            ], 404);
        }
            
        $simpanKoleksi->delete();
        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil dihapus dari simpan'
        ], 200);
    }
    
    // Cek apakah koleksi sudah disimpan
    public function checkSaved($koleksi_id)
    {
        $user = Auth::user();
        $isSaved = SimpanKoleksi::where('user_id', $user->id)
            ->where('koleksi_id', $koleksi_id)
            ->exists();
            
        return response()->json([
            'success' => true,
            'message' => 'Status simpan berhasil diperiksa',
            'is_saved' => $isSaved
        ]);
    }
}