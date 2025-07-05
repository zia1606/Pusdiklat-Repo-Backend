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

// class RecommendationController extends Controller
// {
//     /**
//      * Get similar collections based on preprocessing content or category/type similarity
//      */
//     public function getSimilarCollections(Request $request): JsonResponse
// {
//     try {
//         // Validasi input
//         $request->validate([
//             'koleksi_id' => 'required|integer|exists:koleksi,id',
//             'top_n' => 'nullable|integer|min:1|max:50'
//         ]);

//         $koleksiId = $request->koleksi_id;
//         $topN = $request->top_n ?? 5;

//         // Ambil koleksi referensi dengan relasi
//         $referenceCollection = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//             ->findOrFail($koleksiId);

//         // =============================================
//         // 1. JIKA TIDAK ADA PREPROCESSING
//         // =============================================
//         if (empty($referenceCollection->preprocessing)) {
//             $similarByCategory = $this->getRecommendationsByCategory(
//                 $referenceCollection->kategori_bang_kom_id,
//                 $referenceCollection->jenis_dokumen_id,
//                 $koleksiId,
//                 $topN
//             );

//             return $this->formatSuccessResponse($referenceCollection, $similarByCategory);
//         }

//         // =============================================
//         // 2. COBA REKOMENDASI BERDASARKAN PREPROCESSING
//         // =============================================
//         $pythonRecommendations = $this->runPythonRecommendation(
//             $this->preparePythonData(Koleksi::all(), $koleksiId),
//             $topN
//         );

//         // Jika ada hasil dari Python, kembalikan
//         if ($pythonRecommendations && count($pythonRecommendations) > 0) {
//             return $this->formatSuccessResponse($referenceCollection, $pythonRecommendations);
//         }

//         // =============================================
//         // 3. FALLBACK: REKOMENDASI BERDASARKAN KATEGORI
//         // =============================================
//         $fallbackRecommendations = $this->getRecommendationsByCategory(
//             $referenceCollection->kategori_bang_kom_id,
//             $referenceCollection->jenis_dokumen_id,
//             $koleksiId,
//             $topN
//         );

//         return $this->formatSuccessResponse($referenceCollection, $fallbackRecommendations);

//     } catch (\Exception $e) {
//         Log::error('Recommendation error: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Terjadi kesalahan sistem'
//         ], 500);
//     }
// }

// /**
//  * Dapatkan rekomendasi berdasarkan kategori & jenis dokumen
//  */
// private function getRecommendationsByCategory(
//     $kategoriId, 
//     $jenisDokumenId, 
//     $excludeId, 
//     $limit = 5
// ) {
//     return Koleksi::where('id', '!=', $excludeId)
//         ->where('kategori_bang_kom_id', $kategoriId)
//         ->where('jenis_dokumen_id', $jenisDokumenId)
//         ->orderByDesc('tahun_terbit') // Prioritas dokumen terbaru
//         ->limit($limit)
//         ->get()
//         ->toArray();
// }

// /**
//  * Format response standar
//  */
// private function formatSuccessResponse($referenceCollection, $recommendations)
// {
//     return response()->json([
//         'success' => true,
//         'data' => [
//             'reference_collection' => new KoleksiResource($referenceCollection),
//             'similar_collections' => [
//                 'recommendations' => $recommendations,
//                 'recommendation_type' => isset($recommendations[0]['similarity_score']) 
//                     ? 'content_based' 
//                     : 'category_based'
//             ],
//             'total_found' => count($recommendations)
//         ]
//     ]);
// }

//     /**
//      * Get content-based recommendations using Python script
//      */
//     private function getContentBasedRecommendations($referenceCollection, $topN)
//     {
//         try {
//             // Ambil semua koleksi dengan preprocessing data
//             $allCollections = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//                 ->whereNotNull('preprocessing')
//                 ->where('preprocessing', '!=', '')
//                 ->where('preprocessing', '!=', ' ')
//                 ->get();

//             if ($allCollections->count() < 2) {
//                 return false;
//             }

//             // Siapkan data untuk Python script
//             $pythonData = $this->preparePythonData($allCollections, $referenceCollection->id);

//             // Jalankan Python script
//             $result = $this->runPythonRecommendation($pythonData, $topN);

//             return $result;

//         } catch (\Exception $e) {
//             Log::error('Content-based recommendation error: ' . $e->getMessage());
//             return false;
//         }
//     }

//     /**
//      * Get category-based recommendations (fallback method)
//      */
//     private function getCategoryBasedRecommendations($referenceCollection, $topN): array
//     {
//         try {
//             // Query builder untuk mencari koleksi serupa
//             $query = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//                 ->where('id', '!=', $referenceCollection->id);

//             // Prioritas 1: Kategori dan jenis dokumen sama
//             $exactMatches = (clone $query)
//                 ->where('kategori_bangkom_id', $referenceCollection->kategori_bangkom_id)
//                 ->where('jenis_dokumen_id', $referenceCollection->jenis_dokumen_id)
//                 ->orderBy('views', 'desc')
//                 ->orderBy('created_at', 'desc')
//                 ->limit($topN)
//                 ->get();

//             $recommendations = [];

//             // Format exact matches
//             foreach ($exactMatches as $collection) {
//                 $recommendations[] = [
//                     'id' => $collection->id,
//                     'judul' => $collection->judul,
//                     'penulis' => $collection->penulis,
//                     'kategori' => $collection->kategoriBangKom->nama ?? '',
//                     'jenis_dokumen' => $collection->jenisDokumen->nama ?? '',
//                     'tahun_terbit' => $collection->tahun_terbit,
//                     'views' => $collection->views,
//                     'similarity_score' => 1.0, // Perfect match untuk kategori dan jenis
//                     'match_type' => 'exact_match'
//                 ];
//             }

//             // Jika belum cukup, cari yang hanya kategori sama
//             if (count($recommendations) < $topN) {
//                 $remaining = $topN - count($recommendations);
//                 $usedIds = collect($recommendations)->pluck('id')->toArray();
//                 $usedIds[] = $referenceCollection->id;

//                 $categoryMatches = (clone $query)
//                     ->whereNotIn('id', $usedIds)
//                     ->where('kategori_bangkom_id', $referenceCollection->kategori_bangkom_id)
//                     ->orderBy('views', 'desc')
//                     ->orderBy('created_at', 'desc')
//                     ->limit($remaining)
//                     ->get();

//                 foreach ($categoryMatches as $collection) {
//                     $recommendations[] = [
//                         'id' => $collection->id,
//                         'judul' => $collection->judul,
//                         'penulis' => $collection->penulis,
//                         'kategori' => $collection->kategoriBangKom->nama ?? '',
//                         'jenis_dokumen' => $collection->jenisDokumen->nama ?? '',
//                         'tahun_terbit' => $collection->tahun_terbit,
//                         'views' => $collection->views,
//                         'similarity_score' => 0.7, // High similarity untuk kategori sama
//                         'match_type' => 'category_match'
//                     ];
//                 }
//             }

//             // Jika masih belum cukup, cari yang hanya jenis dokumen sama
//             if (count($recommendations) < $topN) {
//                 $remaining = $topN - count($recommendations);
//                 $usedIds = collect($recommendations)->pluck('id')->toArray();
//                 $usedIds[] = $referenceCollection->id;

//                 $typeMatches = (clone $query)
//                     ->whereNotIn('id', $usedIds)
//                     ->where('jenis_dokumen_id', $referenceCollection->jenis_dokumen_id)
//                     ->orderBy('views', 'desc')
//                     ->orderBy('created_at', 'desc')
//                     ->limit($remaining)
//                     ->get();

//                 foreach ($typeMatches as $collection) {
//                     $recommendations[] = [
//                         'id' => $collection->id,
//                         'judul' => $collection->judul,
//                         'penulis' => $collection->penulis,
//                         'kategori' => $collection->kategoriBangKom->nama ?? '',
//                         'jenis_dokumen' => $collection->jenisDokumen->nama ?? '',
//                         'tahun_terbit' => $collection->tahun_terbit,
//                         'views' => $collection->views,
//                         'similarity_score' => 0.5, // Medium similarity untuk jenis dokumen sama
//                         'match_type' => 'type_match'
//                     ];
//                 }
//             }

//             // Jika masih belum cukup, ambil yang paling populer
//             if (count($recommendations) < $topN) {
//                 $remaining = $topN - count($recommendations);
//                 $usedIds = collect($recommendations)->pluck('id')->toArray();
//                 $usedIds[] = $referenceCollection->id;

//                 $popularCollections = (clone $query)
//                     ->whereNotIn('id', $usedIds)
//                     ->orderBy('views', 'desc')
//                     ->orderBy('created_at', 'desc')
//                     ->limit($remaining)
//                     ->get();

//                 foreach ($popularCollections as $collection) {
//                     $recommendations[] = [
//                         'id' => $collection->id,
//                         'judul' => $collection->judul,
//                         'penulis' => $collection->penulis,
//                         'kategori' => $collection->kategoriBangKom->nama ?? '',
//                         'jenis_dokumen' => $collection->jenisDokumen->nama ?? '',
//                         'tahun_terbit' => $collection->tahun_terbit,
//                         'views' => $collection->views,
//                         'similarity_score' => 0.2, // Low similarity, just popular
//                         'match_type' => 'popular'
//                     ];
//                 }
//             }

//             return $recommendations;

//         } catch (\Exception $e) {
//             Log::error('Category-based recommendation error: ' . $e->getMessage());
//             return [];
//         }
//     }

//     /**
//      * Prepare data for Python script
//      */
//     private function preparePythonData($collections, $referenceId): array
//     {
//         $data = [];
//         $referenceIndex = -1;

//         foreach ($collections as $index => $collection) {
//             $data[] = [
//                 'id' => $collection->id,
//                 'judul' => $collection->judul,
//                 'penulis' => $collection->penulis,
//                 'kategori' => $collection->kategoriBangKom->nama ?? '',
//                 'jenis_dokumen' => $collection->jenisDokumen->nama ?? '',
//                 'tahun_terbit' => $collection->tahun_terbit,
//                 'preprocessing' => $collection->preprocessing,
//                 'views' => $collection->views
//             ];

//             // Simpan index dari koleksi referensi
//             if ($collection->id == $referenceId) {
//                 $referenceIndex = $index;
//             }
//         }

//         return [
//             'collections' => $data,
//             'reference_index' => $referenceIndex
//         ];
//     }

//     /**
//      * Run Python recommendation script
//      */
//     private function runPythonRecommendation($data, $topN)
//     {
        
//         try {
//             // Path ke Python script
//             $pythonScript = base_path('app/Services/recommendation_engine.py');
            
//             // Buat temporary file untuk data input
//             $tempFile = tempnam(sys_get_temp_dir(), 'recommendation_data_');
//             file_put_contents($tempFile, json_encode($data));

//             // Jalankan Python script
//             $process = new Process([
//                 'python', 
//                 $pythonScript, 
//                 $tempFile, 
//                 $topN
//             ]);

//             $process->run();

//             // Hapus temporary file
//             unlink($tempFile);

//             // Cek apakah berhasil
//             if (!$process->isSuccessful()) {
//                 Log::error('Python process failed: ' . $process->getErrorOutput());
//                 return false;
//             }

//             // Parse hasil dari Python
//             $output = $process->getOutput();
//             $result = json_decode($output, true);

//             if (json_last_error() !== JSON_ERROR_NONE) {
//                 Log::error('Invalid JSON from Python script: ' . $output);
//                 return false;
//             }

//             // Return hanya recommendations untuk konsistensi
//             return isset($result['recommendations']) ? $result : false;

//         } catch (ProcessFailedException $e) {
//             Log::error('Process failed: ' . $e->getMessage());
//             return false;
//         } catch (\Exception $e) {
//             Log::error('Python execution error: ' . $e->getMessage());
//             return false;
//         }
//     }

//     /**
//      * Get recommendation statistics
//      */
//     public function getRecommendationStats(): JsonResponse
//     {
//         try {
//             $totalCollections = Koleksi::count();
//             $collectionsWithPreprocessing = Koleksi::whereNotNull('preprocessing')
//                 ->where('preprocessing', '!=', '')
//                 ->where('preprocessing', '!=', ' ')
//                 ->count();

//             return response()->json([
//                 'success' => true,
//                 'data' => [
//                     'total_collections' => $totalCollections,
//                     'collections_with_preprocessing' => $collectionsWithPreprocessing,
//                     'content_based_ready' => $collectionsWithPreprocessing >= 2,
//                     'category_based_available' => $totalCollections >= 2
//                 ]
//             ]);

//         } catch (\Exception $e) {
//             Log::error('Stats error: ' . $e->getMessage());
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Gagal mengambil statistik'
//             ], 500);
//         }
//     }

//     /**
//      * GET method wrapper
//      */
//     public function getSimilarCollectionsGet(Request $request): JsonResponse
//     {
//         try {
//             $request->validate([
//                 'koleksi_id' => 'required|integer|exists:koleksi,id',
//                 'top_n' => 'nullable|integer|min:1|max:50'
//             ]);

//             $koleksiId = $request->query('koleksi_id');
//             $topN = $request->query('top_n', 5);

//             $request->merge([
//                 'koleksi_id' => $koleksiId,
//                 'top_n' => $topN
//             ]);

//             return $this->getSimilarCollections($request);

//         } catch (\Exception $e) {
//             Log::error('GET Recommendation error: ' . $e->getMessage());
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan sistem',
//                 'error' => config('app.debug') ? $e->getMessage() : null
//             ], 500);
//         }
//     }

//     /**
//      * RESTful style endpoint
//      */
//     public function getSimilarByKoleksiId($id, Request $request): JsonResponse
//     {
//         try {
//             $request->validate([
//                 'top_n' => 'nullable|integer|min:1|max:50'
//             ]);

//             $topN = $request->query('top_n',5);

//             $request->merge([
//                 'koleksi_id' => $id,
//                 'top_n' => $topN
//             ]);

//             return $this->getSimilarCollections($request);

//         } catch (\Exception $e) {
//             Log::error('GET by ID Recommendation error: ' . $e->getMessage());
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan sistem',
//                 'error' => config('app.debug') ? $e->getMessage() : null
//             ], 500);
//         }
//     }

//     /**
//      * Alias method
//      */
//     public function getRecommendations($id, Request $request): JsonResponse
//     {
//         return $this->getSimilarByKoleksiId($id, $request);
//     }

//     /**
//      * Simple GET method
//      */
//     public function getSimilarSimple($koleksiId, Request $request): JsonResponse
//     {
//         try {
//             $request->validate([
//                 'top_n' => 'nullable|integer|min:1|max:50'
//             ]);

//             $request->merge([
//                 'koleksi_id' => $koleksiId,
//                 'top_n' => $request->query('top_n',5)
//             ]);

//             return $this->getSimilarCollections($request);

//         } catch (\Exception $e) {
//             Log::error('Simple GET Recommendation error: ' . $e->getMessage());
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan sistem'
//             ], 500);
//         }
//     }

//     /**
//      * Advanced POST method
//      */
//     public function getSimilarAdvanced(Request $request): JsonResponse
//     {
//         try {
//             $request->validate([
//                 'koleksi_id' => 'required|integer|exists:koleksi,id',
//                 'top_n' => 'nullable|integer|min:1|max:50',
//                 'filters' => 'nullable|array',
//                 'filters.kategori' => 'nullable|array',
//                 'filters.tahun_min' => 'nullable|integer',
//                 'filters.tahun_max' => 'nullable|integer',
//                 'filters.exclude_ids' => 'nullable|array'
//             ]);

//             // Future: Implementasi filtering advanced bisa ditambahkan di sini
//             return $this->getSimilarCollections($request);

//         } catch (\Exception $e) {
//             Log::error('Advanced Recommendation error: ' . $e->getMessage());
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan sistem'
//             ], 500);
//         }
//     }
// }

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