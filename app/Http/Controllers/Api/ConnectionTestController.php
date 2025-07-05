<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Koleksi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConnectionTestController extends Controller
{
    public function count(Request $request)
    {
        try {
            $request->validate([
                'koleksi_id' => 'required|integer|exists:koleksi,id'
            ]);

            $referenceId = $request->koleksi_id;
            $collections = Koleksi::all();

            $response = Http::post(env('FLASK_RECOMMENDATION_URL', 'https://pusdiklat-repo-rekomendasi.zeabur.app') . '/count-by-year', [
                'reference_id' => $referenceId,
                'collections' => $collections->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'judul' => $item->judul,
                        'tahun_terbit' => $item->tahun_terbit
                    ];
                })->toArray()
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error dari Flask service',
                'flask_response' => $response->json()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
