<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Koleksi;
use App\Http\Resources\KoleksiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RecommendationController extends Controller
{
    public function getRecommendations(Request $request)
    {
        try {
            $request->validate([
                'koleksi_id' => 'required|integer|exists:koleksi,id',
                'top_n' => 'nullable|integer|min:1|max:10'
            ]);

            $referenceId = $request->koleksi_id;
            $topN = $request->top_n ?? 5;

            // 1. Coba dapatkan rekomendasi dari Python
            $pythonRecommendations = $this->getPythonRecommendations($referenceId, $topN);
            
            if ($pythonRecommendations['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $pythonRecommendations['data'],
                    'recommendation_type' => 'content_based'
                ]);
            }

            // 2. Fallback ke rekomendasi berdasarkan kategori/jenis dokumen
            $fallbackRecommendations = $this->getCategoryBasedRecommendations($referenceId, $topN);
            
            return response()->json([
                'success' => true,
                'data' => $fallbackRecommendations,
                'recommendation_type' => 'category_based',
                'message' => 'Menggunakan rekomendasi berdasarkan kategori/jenis dokumen'
            ]);

        } catch (\Exception $e) {
            Log::error('Recommendation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    private function getPythonRecommendations($referenceId, $topN)
    {
        $collections = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
            ->whereNotNull('preprocessing')
            ->get();

        if ($collections->count() < 2) {
            return [
                'success' => false,
                'message' => 'Tidak cukup data untuk rekomendasi'
            ];
        }

        $payload = [
            'reference_id' => $referenceId,
            'top_n' => $topN,
            'collections' => $collections->map(function ($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'penulis' => $item->penulis,
                    'kategori' => $item->kategoriBangKom->nama ?? '',
                    'jenis_dokumen' => $item->jenisDokumen->nama ?? '',
                    'tahun_terbit' => $item->tahun_terbit,
                    'preprocessing' => $item->preprocessing
                ];
            })->toArray()
        ];

        $response = Http::timeout(30)->post(
            env('FLASK_RECOMMENDATION_URL', 'https://pusdiklat-repo-rekomendasi.zeabur.app/recommend'),
            $payload
        );

        if ($response->successful()) {
            $result = $response->json();
            return [
                'success' => $result['status'] === 'success',
                'data' => $result,
                'message' => $result['status'] === 'error' ? $result['message'] : null
            ];
        }

        return [
            'success' => false,
            'message' => 'Tidak bisa terhubung ke service rekomendasi'
        ];
    }

    private function getCategoryBasedRecommendations($referenceId, $topN)
    {
        $reference = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
            ->findOrFail($referenceId);

        $recommendations = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
            ->where('id', '!=', $referenceId)
            ->where(function($query) use ($reference) {
                $query->where('kategori_bang_kom_id', $reference->kategori_bang_kom_id)
                      ->orWhere('jenis_dokumen_id', $reference->jenis_dokumen_id);
            })
            ->orderByDesc('tahun_terbit')
            ->limit($topN)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'penulis' => $item->penulis,
                    'kategori' => $item->kategoriBangKom->nama ?? '',
                    'jenis_dokumen' => $item->jenisDokumen->nama ?? '',
                    'tahun_terbit' => $item->tahun_terbit,
                    'similarity_score' => 0.7 // Nilai default untuk fallback
                ];
            });

        return [
            'reference_id' => $referenceId,
            'recommendations' => $recommendations
        ];
    }
}