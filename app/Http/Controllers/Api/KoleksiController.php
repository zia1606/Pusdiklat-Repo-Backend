<?php

namespace App\Http\Controllers\Api;

use App\Models\Koleksi;
use setasign\Fpdi\Fpdi;
use App\Models\RiwayatBaca;
use App\Models\JenisDokumen;
use Illuminate\Http\Request;
use App\Jobs\PreprocessingJob;
use App\Models\KategoriBangKom;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\RiwayatBestCollection;
use Symfony\Component\Process\Process;
use App\Http\Resources\KoleksiResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class KoleksiController extends Controller
{

    // ✅ PERBAIKAN: Tambahkan method untuk memastikan preprocessing sebelum rekomendasi
    public function recommendContentBased(Request $request)
    {
        $request->validate([
            'current_id' => 'required|integer|exists:koleksi,id',
            'limit' => 'sometimes|integer|min:1|max:20'
        ]);

        $currentId = $request->input('current_id');
        $limit = $request->input('limit', 5);

        try {
            // ✅ PERBAIKAN: Cek dulu apakah dokumen current memiliki preprocessing
            $currentDoc = Koleksi::find($currentId);
            if (!$currentDoc || empty($currentDoc->preprocessing) || $currentDoc->preprocessing === '0') {
                return response()->json([
                    'message' => 'Dokumen ini belum memiliki data preprocessing yang diperlukan untuk rekomendasi',
                    'data' => [],
                    'current_document' => [
                        'id' => $currentId,
                        'judul' => $currentDoc ? $currentDoc->judul : 'Unknown',
                        'has_preprocessing' => false
                    ],
                    'suggestion' => 'Silakan tunggu proses preprocessing selesai atau pilih dokumen lain'
                ], 400);
            }

            // Ambil semua dokumen dengan data preprocessing VALID
            $documents = Koleksi::select('id', 'judul', 'penulis', 'preprocessing')
                ->whereNotNull('preprocessing')
                ->where('preprocessing', '!=', '')
                ->where('preprocessing', '!=', '0') // ✅ PERBAIKAN: Hindari nilai "0"
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'judul' => $item->judul,
                        'penulis' => $item->penulis,
                        'preprocessing' => $item->preprocessing
                    ];
                });

            if ($documents->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data preprocessing tersedia untuk rekomendasi',
                    'data' => []
                ], 200);
            }

            // Pastikan dokumen current ada dalam data
            $currentDocInSet = $documents->firstWhere('id', $currentId);
            if (!$currentDocInSet) {
                return response()->json([
                    'message' => 'Dokumen dengan ID tersebut tidak memiliki data preprocessing yang valid',
                    'data' => []
                ], 404);
            }

            // Execute Python script untuk Content-Based Filtering
            $process = new Process([
                'python',
                base_path('app/Services/recommendation_engine.py'),
                $documents->toJson(),
                (string)$currentId,
                (string)$limit
            ]);
            
            $process->setTimeout(300);
            $process->run();
            
            if (!$process->isSuccessful()) {
                Log::error("Recommendation process failed: " . $process->getErrorOutput());
                return $this->fallbackContentBasedRecommendation($currentId, $limit);
            }
            
            $recommendations = json_decode($process->getOutput(), true);
            
            if (!is_array($recommendations) || empty($recommendations)) {
                return $this->fallbackContentBasedRecommendation($currentId, $limit);
            }

            // Ambil data lengkap dokumen yang direkomendasikan
            $recommendedIds = array_column($recommendations, 'id');
            
            // ✅ PERBAIKAN: Pastikan hasil akhir juga memiliki preprocessing
            $recommendedDocs = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
                ->whereIn('id', $recommendedIds)
                ->whereNotNull('preprocessing')
                ->where('preprocessing', '!=', '')
                ->where('preprocessing', '!=', '0')
                ->get()
                ->keyBy('id');

            // Gabungkan dengan similarity scores dan urutkan
            $finalRecommendations = [];
            foreach ($recommendations as $rec) {
                $doc = $recommendedDocs->get($rec['id']);
                if ($doc) {
                    $docArray = new KoleksiResource($doc);
                    $docArray = $docArray->toArray($request);
                    $docArray['similarity_score'] = round($rec['similarity_score'], 4);
                    $finalRecommendations[] = $docArray;
                }
            }

            return response()->json([
                'message' => 'Rekomendasi berhasil dibuat menggunakan Content-Based Filtering',
                'current_document' => [
                    'id' => $currentId,
                    'judul' => $currentDoc->judul
                ],
                'method' => 'Content-Based Filtering',
                'total_recommendations' => count($finalRecommendations),
                'data' => $finalRecommendations
            ], 200);

        } catch (\Exception $e) {
            Log::error("Content-Based Recommendation error: " . $e->getMessage());
            return $this->fallbackContentBasedRecommendation($currentId, $limit);
        }
    }
    // Tambahkan method ini ke KoleksiController yang sudah ada

    private function fallbackContentBasedRecommendation($currentId, $limit)
    {
        try {
            $currentDoc = Koleksi::find($currentId);
            
            if (!$currentDoc) {
                return response()->json([
                    'message' => 'Dokumen tidak ditemukan',
                    'data' => []
                ], 404);
            }

            // ✅ PERBAIKAN: Tambahkan filter preprocessing di semua fallback
            $baseQuery = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
                ->where('id', '!=', $currentId)
                ->whereNotNull('preprocessing')
                ->where('preprocessing', '!=', '');

            // Fallback 1: Berdasarkan keywords jika ada
            if (!empty($currentDoc->keywords)) {
                $keywords = array_filter(array_map('trim', explode(',', $currentDoc->keywords)));
                
                $query = clone $baseQuery; // ✅ Gunakan base query yang sudah difilter
                
                foreach ($keywords as $keyword) {
                    $query->orWhere(function($subQuery) use ($keyword, $currentId) {
                        $subQuery->where('keywords', 'like', '%' . $keyword . '%')
                                ->where('id', '!=', $currentId)
                                ->whereNotNull('preprocessing')
                                ->where('preprocessing', '!=', '');
                    });
                }

                $recommendations = $query->orderBy('views', 'desc')
                    ->limit($limit)
                    ->get();

                if ($recommendations->isNotEmpty()) {
                    return response()->json([
                        'message' => 'Rekomendasi berdasarkan keywords (fallback)',
                        'method' => 'Keywords-Based (Fallback)',
                        'data' => KoleksiResource::collection($recommendations)
                    ], 200);
                }
            }

            // Fallback 2: Berdasarkan kategori atau penulis
            $query = clone $baseQuery; // ✅ Gunakan base query yang sudah difilter

            if ($currentDoc->kategori_bang_kom_id) {
                $query->where('kategori_bang_kom_id', $currentDoc->kategori_bang_kom_id);
            } elseif ($currentDoc->penulis) {
                $query->where('penulis', 'like', '%' . $currentDoc->penulis . '%');
            } else {
                // Fallback terakhir: dokumen terpopuler (masih dengan filter preprocessing)
                $query->orderBy('views', 'desc');
            }

            $recommendations = $query->limit($limit)->get();

            if ($recommendations->isNotEmpty()) {
                return response()->json([
                    'message' => 'Rekomendasi berdasarkan kategori/penulis (fallback)',
                    'method' => 'Category/Author-Based (Fallback)',
                    'data' => KoleksiResource::collection($recommendations)
                ], 200);
            }

            // ✅ PERBAIKAN: Jika tidak ada rekomendasi dengan preprocessing
            return response()->json([
                'message' => 'Tidak ada dokumen dengan data preprocessing yang cukup untuk rekomendasi',
                'method' => 'No Recommendations Available',
                'data' => [],
                'suggestion' => 'Silakan tunggu hingga lebih banyak dokumen telah diproses atau coba lagi nanti'
            ], 200);

        } catch (\Exception $e) {
            Log::error("Fallback recommendation error: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Gagal membuat rekomendasi',
                'error' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

// Tambahkan method ini ke KoleksiController
    public function getPreprocessingStatus($id)
    {
        try {
            $koleksi = Koleksi::find($id);
            
            if (!$koleksi) {
                return response()->json([
                    'message' => 'Koleksi tidak ditemukan'
                ], 404);
            }

            $hasPreprocessing = !empty($koleksi->preprocessing);
            $preprocessingLength = $hasPreprocessing ? strlen($koleksi->preprocessing) : 0;

            return response()->json([
                'id' => $koleksi->id,
                'judul' => $koleksi->judul,
                'has_preprocessing' => $hasPreprocessing,
                'preprocessing_length' => $preprocessingLength,
                'preprocessing_status' => $koleksi->preprocessing_status ?? 'unknown',
                'can_recommend' => $hasPreprocessing,
                'preprocessing_sample' => $hasPreprocessing ? 
                    Str::limit($koleksi->preprocessing, 1000) : null
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting preprocessing status: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengecek status preprocessing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function performPreprocessing($koleksi)
    {
        try {
            // Siapkan data untuk preprocessing (hanya judul dan ringkasan)
            $inputData = [
                'id' => $koleksi->id,
                'judul' => $koleksi->judul ?? '',
                'ringkasan' => $koleksi->ringkasan ?? ''
            ];

            // Panggil script Python untuk preprocessing
            $process = new Process([
                'python',
                base_path('app/Services/text_preprocessing.py'),
                json_encode($inputData)
            ]);

            $process->mustRun();
            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            // Update koleksi dengan hasil preprocessing gabungan
            $koleksi->update([
                'preprocessing' => $result['preprocessing'] // Simpan langsung sebagai string
            ]);

        } catch (\Exception $e) {
            // Log error tapi jangan gagalkan proses utama
            Log::error('Preprocessing error: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        // Ambil parameter sorting dari request
        $sortBy = $request->query('sort_by', 'terbaru'); // Default: terbaru
        $perPage = $request->query('per_page', 10); // Default: 10 item per halaman

        // Query dasar dengan eager loading
        $query = Koleksi::with(['kategoriBangKom', 'jenisDokumen']);

        // Sorting berdasarkan parameter
        switch ($sortBy) {
            case 'terlama':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default: // Terbaru
                $query->orderBy('tahun_terbit', 'desc');
                break;
        }

        // Pagination
        $koleksi = $query->paginate($perPage);

        if ($koleksi) {
            return KoleksiResource::collection($koleksi);
        } else {
            return response()->json(['message' => 'Tidak ada koleksi tersedia'], 200);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string',
            'penulis' => 'required|string',
            'ringkasan' => 'nullable|string',
            'kategori_bang_kom_id' => 'nullable|exists:kategori_bang_kom,id',
            'jenis_dokumen_id' => 'nullable|exists:jenis_dokumen,id',
            'tahun_terbit' => 'required|string',
            'penerbit' => 'nullable|string',
            'keywords' => 'nullable|string',
            'dokumen_pdf' => [
            'nullable',
            'file',
            'mimes:pdf',
            'max:10240' // 10MB dalam KB
        ],
            'youtube_link' => 'nullable|url',
            'content_type' => 'required|in:pdf,youtube',
        ]);

        if($validator->fails())
        {
            $errors = $validator->errors();
        
            if ($errors->has('dokumen_pdf')) {
                return response()->json([
                    'message' => 'Ukuran file terlalu besar. Maksimal 10MB',
                    'error' => $errors
                ], 422);
            }

            return response()->json([
                'message' => 'Validasi gagal',
                'error' => $validator->errors(),
            ], 422);
        }

        // Validasi tambahan: pastikan hanya satu jenis konten yang diisi
        if ($request->content_type === 'pdf' && !$request->hasFile('dokumen_pdf')) {
            return response()->json([
                'message' => 'Validasi gagal',
                'error' => ['dokumen_pdf' => ['File PDF harus diupload untuk tipe konten PDF']],
            ], 422);
        }

        if ($request->content_type === 'youtube' && !$request->youtube_link) {
            return response()->json([
                'message' => 'Validasi gagal',
                'error' => ['youtube_link' => ['Link YouTube harus diisi untuk tipe konten YouTube']],
            ], 422);
        }

        // Upload dokumen PDF hanya jika tipe konten adalah PDF
        $pdfPath = null;
        if ($request->content_type === 'pdf' && $request->hasFile('dokumen_pdf')) {
            $pdfPath = $request->file('dokumen_pdf')->store('dokumen_pdf', 'public');
        }

        // Simpan YouTube link hanya jika tipe konten adalah YouTube
        $youtubeLink = null;
        if ($request->content_type === 'youtube' && $request->youtube_link) {
            $youtubeLink = $request->youtube_link;
        }

        $koleksi = Koleksi::create([
            'judul' => $request->judul,
            'penulis' => $request->penulis,
            'ringkasan' => $request->ringkasan,
            'kategori_bang_kom_id' => $request->kategori_bang_kom_id,
            'jenis_dokumen_id' => $request->jenis_dokumen_id,
            'tahun_terbit' => $request->tahun_terbit,
            'penerbit' => $request->penerbit,
            'keywords' => $request->keywords,
            'dokumen_pdf' => $pdfPath,
            'youtube_link' => $youtubeLink,
            'preprocessing_status' => 'pending', // Status awal
        ]);

        // Dispatch job untuk preprocessing secara asynchronous
        PreprocessingJob::dispatch($koleksi->id);

        return response()->json([
            // 'message' => 'Koleksi berhasil ditambahkan. Preprocessing sedang dilakukan di background.',
            'message' => 'Koleksi berhasil ditambahkan.',
            'data' => new KoleksiResource($koleksi),
            'preprocessing_status' => 'pending'
        ], 200);
    }

    private function addToReadingHistory($userId, $koleksiId)
    {
        try {
            // Cek apakah sudah ada di riwayat dalam 24 jam terakhir
            $existing = RiwayatBaca::where('user_id', $userId)
                ->where('koleksi_id', $koleksiId)
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if (!$existing) {
                RiwayatBaca::create([
                    'user_id' => $userId,
                    'koleksi_id' => $koleksiId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info("Created new reading history entry for user: {$userId}, koleksi: {$koleksiId}");
            } else {
                // Update timestamp jika sudah ada
                $existing->touch();
                Log::info("Updated existing reading history entry for user: {$userId}, koleksi: {$koleksiId}");
            }
        } catch (\Exception $e) {
            Log::error('Error adding to reading history: ' . $e->getMessage());
            throw $e; // Re-throw exception untuk penanganan lebih lanjut
        }
    }

    public function show(Koleksi $koleksi)
    {

        // Cek session untuk menghindari pembacaan berulang
        // if (!session()->has('koleksi_dibaca_' . $koleksi->id)) {
        //     $koleksi->increment('views');
        //     session()->put('koleksi_dibaca_' . $koleksi->id, true);
        // }
        // Update jumlah pembacaan
        // $koleksi->increment('views'); // Menambah nilai views sebanyak 1

        return new KoleksiResource($koleksi);
    }

    public function edit($id) 
    {
        $koleksi = Koleksi::find($id);
        if($koleksi){
            return response()->json([
                'status' => 200,
                'koleksi' => $koleksi
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'koleksi' => "Data koleksi tidak ditemukan"
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $koleksi = Koleksi::find($id);
        
        if (!$koleksi) {
            return response()->json([
                'message' => 'Koleksi tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string',
            'penulis' => 'sometimes|required|string',
            'ringkasan' => 'nullable|string',
            'kategori_bang_kom_id' => 'nullable|exists:kategori_bang_kom,id',
            'jenis_dokumen_id' => 'nullable|exists:jenis_dokumen,id',
            'tahun_terbit' => 'sometimes|required|string',
            'penerbit' => 'nullable|string',
            'keywords' => 'nullable|string',
            'dokumen_pdf' => [
            'nullable',
            'file',
            'mimes:pdf',
            'max:10240' // 10MB dalam KB
        ],
            'youtube_link' => 'nullable|url',
            'content_type' => 'sometimes|required|in:pdf,youtube',
        ]);

        if($validator->fails()) {
            $errors = $validator->errors();
        
            if ($errors->has('dokumen_pdf')) {
                return response()->json([
                    'message' => 'Ukuran file terlalu besar. Maksimal 10MB',
                    'error' => $errors
                ], 422);
            }

            return response()->json([
                'message' => 'Validasi gagal',
                'error' => $validator->errors(),
            ], 422);
        }

        // Validasi konten
        if ($request->has('content_type')) {
            if ($request->content_type === 'pdf' && !$request->hasFile('dokumen_pdf') && !$koleksi->dokumen_pdf) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'error' => ['dokumen_pdf' => ['File PDF harus diupload untuk tipe konten PDF']],
                ], 422);
            }

            if ($request->content_type === 'youtube' && !$request->youtube_link && !$koleksi->youtube_link) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'error' => ['youtube_link' => ['Link YouTube harus diisi untuk tipe konten YouTube']],
                ], 422);
            }
        }

        // Handle file upload jika ada
        $pdfPath = $koleksi->dokumen_pdf;
        if ($request->hasFile('dokumen_pdf')) {
            // Hapus file lama jika ada
            if ($koleksi->dokumen_pdf) {
                Storage::disk('public')->delete($koleksi->dokumen_pdf);
            }
            $pdfPath = $request->file('dokumen_pdf')->store('dokumen_pdf', 'public');
        }

        // Handle youtube link
        $youtubeLink = $koleksi->youtube_link;
        if ($request->has('youtube_link')) {
            $youtubeLink = $request->youtube_link;
            // Jika mengubah ke youtube, hapus file pdf jika ada
            if ($request->content_type === 'youtube' && $koleksi->dokumen_pdf) {
                Storage::disk('public')->delete($koleksi->dokumen_pdf);
                $pdfPath = null;
            }
        }

        // Update data
        $updateData = [
            'judul' => $request->has('judul') ? $request->judul : $koleksi->judul,
            'penulis' => $request->has('penulis') ? $request->penulis : $koleksi->penulis,
            'ringkasan' => $request->has('ringkasan') ? $request->ringkasan : $koleksi->ringkasan,
            'kategori_bang_kom_id' => $request->has('kategori_bang_kom_id') ? $request->kategori_bang_kom_id : $koleksi->kategori_bang_kom_id,
            'jenis_dokumen_id' => $request->has('jenis_dokumen_id') ? $request->jenis_dokumen_id : $koleksi->jenis_dokumen_id,
            'tahun_terbit' => $request->has('tahun_terbit') ? $request->tahun_terbit : $koleksi->tahun_terbit,
            'penerbit' => $request->has('penerbit') ? $request->penerbit : $koleksi->penerbit,
            'keywords' => $request->has('keywords') ? $request->keywords : $koleksi->keywords,
            'dokumen_pdf' => $pdfPath,
            'youtube_link' => $youtubeLink,
            'preprocessing_status' => 'pending', // Set status pending untuk preprocessing ulang
        ];

        $koleksi->update($updateData);

        // Dispatch job untuk preprocessing ulang
        PreprocessingJob::dispatch($koleksi->id);

        return response()->json([
            'message' => 'Koleksi berhasil diperbarui.',
            'data' => new KoleksiResource($koleksi),
            'preprocessing_status' => 'pending'
        ], 200);
    }

    public function destroy(Koleksi $koleksi)
    {
        $koleksi->delete();

        return response()->json([
            'message' => 'Koleksi berhasil dihapus',
            //gunakan ini jika ingin menampilkan data yang dihapus
            'data' => new KoleksiResource($koleksi), 
        ], 200);
    }

    public function search(Request $request)
    {
        $query = Koleksi::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                ->orWhere('penulis', 'like', '%' . $search . '%')
                ->orWhere('keywords', 'like', '%' . $search . '%');
            });
        }

        $koleksi = $query->paginate(10);

        if ($koleksi) {
            return KoleksiResource::collection($koleksi);
        } else {
            return response()->json(['message' => 'Tidak ada koleksi yang ditemukan'], 200);
        }
    }

    public function filter(Request $request)
    {
        $query = Koleksi::query();

        // Pencarian berdasarkan judul, penulis, atau keywords
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                ->orWhere('penulis', 'like', '%' . $search . '%')
                ->orWhere('keywords', 'like', '%' . $search . '%');
            });
        }

        // Filter berdasarkan kategori (AND)
        if ($request->has('kategori')) {
            $kategori = $request->input('kategori');
            if (is_array($kategori)) {
                $query->where(function ($q) use ($kategori) {
                    foreach ($kategori as $k) {
                        $q->orWhere('kategori_bang_kom_id', $k);
                    }
                });
            }
        }

        // Filter berdasarkan jenis dokumen (AND)
        if ($request->has('jenisDokumen')) {
            $jenisDokumen = $request->input('jenisDokumen');
            if (is_array($jenisDokumen)) {
                $query->where(function ($q) use ($jenisDokumen) {
                    foreach ($jenisDokumen as $j) {
                        $q->orWhere('jenis_dokumen_id', $j);
                    }
                });
            }
        }

        // Filter berdasarkan kategori ATAU jenis dokumen
        if ($request->has('kategori') || $request->has('jenisDokumen')) {
            $query->where(function ($q) use ($request) {
                // Filter kategori (jika ada)
                if ($request->has('kategori')) {
                    $kategori = $request->input('kategori');
                    if (is_array($kategori)) {
                        $q->orWhereIn('kategori_bang_kom_id', $kategori);
                    }
                }

                // Filter jenis dokumen (jika ada)
                if ($request->has('jenisDokumen')) {
                    $jenisDokumen = $request->input('jenisDokumen');
                    if (is_array($jenisDokumen)) {
                        $q->orWhereIn('jenis_dokumen_id', $jenisDokumen);
                    }
                }
            });
        }

        // Filter berdasarkan tahun (single year)
        if ($request->has('tahun')) {
            $query->where('tahun_terbit', '>=', $request->tahun);
        }

        // Filter berdasarkan rentang tahun (custom range)
        if ($request->has('customStart') && $request->has('customEnd')) {
            $customStart = $request->input('customStart');
            $customEnd = $request->input('customEnd');
            
            // Pastikan customStart dan customEnd adalah angka
            if (is_numeric($customStart) && is_numeric($customEnd)) {
                $query->whereBetween('tahun_terbit', [$customStart, $customEnd]);
            }
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'terbaru'); // Default: terbaru
        switch ($sortBy) {
            case 'terlama':
                $query->orderBy('tahun_terbit', 'asc'); // Urutkan terlama
                break;
            case 'popular':
                $query->orderBy('views', 'desc'); // Urutkan berdasarkan popularitas (views)
                break;
            default: // Terbaru
                $query->orderBy('tahun_terbit', 'desc'); // Urutkan terbaru
                break;
        }

        // Pagination
        $perPage = $request->query('per_page', 10); // Default: 10 item per halaman
        $koleksi = $query->paginate($perPage);

        // Kembalikan respons dengan data dan informasi pagination
        return response()->json([
            'data' => KoleksiResource::collection($koleksi)->items(), // Data koleksi
            'current_page' => KoleksiResource::collection($koleksi)->currentPage(), // Halaman saat ini
            'prev_page_url' => KoleksiResource::collection($koleksi)->previousPageUrl(), // URL halaman sebelumnya
            'next_page_url' => KoleksiResource::collection($koleksi)->nextPageUrl(), // URL halaman selanjutnya
            'total' => KoleksiResource::collection($koleksi)->total(), // Total data
            'per_page' => KoleksiResource::collection($koleksi)->perPage(),
            'last_page' => KoleksiResource::collection($koleksi)->lastPage(),
        ]);
    }

    public function getYearRange()
    {
        $minYear = Koleksi::min('tahun_terbit');
        $maxYear = Koleksi::max('tahun_terbit');
        
        return response()->json([
            'min_year' => $minYear,
            'max_year' => $maxYear
        ]);
    }

    public function getDistribusiKategori()
    {
        // Ambil semua kategori
        $allCategories = KategoriBangKom::all();
        
        // Hitung distribusi
        $distribution = Koleksi::selectRaw('kategori_bang_kom_id, count(*) as total')
            ->groupBy('kategori_bang_kom_id')
            ->get()
            ->keyBy('kategori_bang_kom_id');
        
        // Gabungkan dengan semua kategori
        $result = $allCategories->map(function ($category) use ($distribution) {
            $count = $distribution->has($category->id) ? $distribution[$category->id]->total : 0;
            return [
                'id' => $category->id,
                'nama' => $category->nama,
                'total' => $count
            ];
        });
        
        // Tambahkan kategori "Lainnya" (null) jika ada
        $nullCount = $distribution->has(null) ? $distribution[null]->total : 0;
        if ($nullCount > 0) {
            $result->push([
                'id' => null,
                'nama' => 'Lainnya',
                'total' => $nullCount
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result->sortByDesc('total')->values()
        ]);
    }

    public function getDistribusiJenis()
    {
        // Ambil semua jenis dokumen
        $allJenis = JenisDokumen::all();
        
        // Hitung distribusi
        $distribution = Koleksi::selectRaw('jenis_dokumen_id, count(*) as total')
            ->groupBy('jenis_dokumen_id')
            ->get()
            ->keyBy('jenis_dokumen_id');
        
        // Gabungkan dengan semua jenis
        $result = $allJenis->map(function ($jenis) use ($distribution) {
            $count = $distribution->has($jenis->id) ? $distribution[$jenis->id]->total : 0;
            return [
                'id' => $jenis->id,
                'nama' => $jenis->nama,
                'total' => $count
            ];
        });
        
        // Tambahkan jenis "Lainnya" (null) jika ada
        $nullCount = $distribution->has(null) ? $distribution[null]->total : 0;
        if ($nullCount > 0) {
            $result->push([
                'id' => null,
                'nama' => 'Lainnya',
                'total' => $nullCount
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result->sortByDesc('total')->values()
        ]);
    }

    public function getBestCollections()
    {
        $bestCollections = Koleksi::where('is_best_collection', true)
            ->select('id', 'judul', 'penulis', 'tahun_terbit', 'dokumen_pdf') // hanya ambil field yang diperlukan
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bestCollections
        ]);
    }

    public function unmarkAsBestCollection($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->update(['is_best_collection' => false]);
        
        // Catat riwayat
        // RiwayatBestCollection::create([
        //     'koleksi_id' => $id,
        //     'action' => 'added',
        //     'metadata' => [
        //         'added_at' => now()->toDateTimeString()
        //     ]
        // ]);

        return response()->json(['message' => 'Koleksi berhasil dihapus dari best collection']);
    }

    public function markAsBestCollection($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->update(['is_best_collection' => true]);
        
        // Catat riwayat
        // RiwayatBestCollection::create([
        //     'koleksi_id' => $id,
        //     'action' => 'removed',
        //     'metadata' => now()->toDateTimeString()
        // ]);

        return response()->json(['message' => 'Koleksi berhasil ditandai sebagai best collection']);
    }


    public function getMostFavoritedCollections()
    {
        $mostFavorited = Koleksi::withCount('favorits')
            ->orderBy('favorits_count', 'desc')
            ->limit(10) // Ambil 5 teratas
            ->get();

        return response()->json([
            'success' => true,
            'data' => $mostFavorited
        ]);
    }

    public function showAdminPdf($id, Request $request)
    {
        try {
            $koleksi = Koleksi::find($id);
            
            if (!$koleksi) {
                return response()->json(['error' => 'Koleksi tidak ditemukan'], 404);
            }

            if (!$koleksi->dokumen_pdf) {
                return response()->json(['error' => 'File PDF tidak tersedia'], 404);
            }

            $pdfPath = storage_path('app/public/' . $koleksi->dokumen_pdf);
            
            if (!file_exists($pdfPath)) {
                return response()->json(['error' => 'File PDF tidak ditemukan di server'], 404);
            }

            // Tidak perlu menambahkan ke riwayat baca atau increment views untuk admin

            $pdfContent = file_get_contents($pdfPath);

            return Response::make($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $koleksi->judul . '.pdf"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (\Exception $e) {
            Log::error('Error serving admin PDF: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat dokumen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

// public function showPublicPdf($id, Request $request)
// {
//     try {
//         $koleksi = Koleksi::find($id);
        
//         if (!$koleksi) {
//             return response()->json(['error' => 'Koleksi tidak ditemukan'], 404);
//         }

//         if (!$koleksi->dokumen_pdf) {
//             return response()->json(['error' => 'File PDF tidak tersedia'], 404);
//         }

//         $pdfPath = storage_path('app/public/' . $koleksi->dokumen_pdf);
        
//         if (!file_exists($pdfPath)) {
//             return response()->json(['error' => 'File PDF tidak ditemukan di server'], 404);
//         }

//         // Tambahkan ke riwayat baca jika user terautentikasi
//         $user = $request->user();
//         if ($user) {
//             $this->addToReadingHistory($user->id, $koleksi->id);
//         }

//         // Increment views dengan session untuk mencegah double increment
//         $sessionKey = 'koleksi_viewed_' . $koleksi->id;
//         if (!$request->session()->has($sessionKey)) {
//             $koleksi->increment('views');
//             $request->session()->put($sessionKey, true);
//         }

//         $pdfContent = file_get_contents($pdfPath);

//         return Response::make($pdfContent, 200, [
//             'Content-Type' => 'application/pdf',
//             'Content-Disposition' => 'inline; filename="' . $koleksi->judul . '.pdf"',
//             'Cache-Control' => 'no-cache, no-store, must-revalidate',
//             'Pragma' => 'no-cache',
//             'Expires' => '0',
//         ]);

//     } catch (\Exception $e) {
//         Log::error('Error serving public PDF: ' . $e->getMessage());
//         return response()->json([
//             'error' => 'Terjadi kesalahan saat memuat dokumen',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

    /**
     * Method untuk menambahkan ke riwayat baca
     */
    

    // 2. Method showPdf - untuk akses dengan autentikasi dan riwayat

    public function showPdf($id, Request $request)
    {
        try {
            $koleksi = Koleksi::find($id);
            
            if (!$koleksi || !$koleksi->dokumen_pdf) {
                return response()->json(['error' => 'File tidak ditemukan'], 404);
            }

            $pdfPath = storage_path('app/public/' . $koleksi->dokumen_pdf);
            
            if (!file_exists($pdfPath)) {
                return response()->json(['error' => 'File tidak ditemukan di server'], 404);
            }

            // Increment views counter
            $koleksi->increment('views');

            // Pastikan user terautentikasi
            $user = $request->user();
            if ($user) {
                $this->addToReadingHistory($user->id, $koleksi->id);
            }

            $pdfContent = file_get_contents($pdfPath);

            return Response::make($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $koleksi->judul . '.pdf"',
            ]);

        } catch (\Exception $e) {
            Log::error('Error serving PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function getTotalViews()
    {
        try {
            $totalViews = Koleksi::sum('views');
            
            return response()->json([
                'status' => true,
                'message' => 'Total views retrieved successfully',
                'data' => $totalViews
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get total views: ' . $e->getMessage()
            ], 500);
        }
    }

    public function load(Request $request)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'document' => 'required|string'
        ]);

        $filePath = storage_path('app/public/' . $request->document);
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"'
        ]);
    }
    
// public function recommend(Request $request)
// {
//     $request->validate([
//         'current_id' => 'required|integer|exists:koleksi,id',
//         'limit' => 'sometimes|integer|min:1|max:10'
//     ]);

//     $currentId = $request->input('current_id');
//     $limit = $request->input('limit', 5);

//     try {
//         // Get all documents with minimal fields
//         $documents = Koleksi::select('id', 'judul', 'keywords', 'penulis')
//             ->get()
//             ->map(function($item) {
//                 return $item->toArray();
//             });

//         // Execute Python script
//         $process = new Process([
//             'python', // atau 'python' di Windows
//             base_path('app/Services/recommendation_engine.py'),
//             $documents->toJson(),
//             $currentId,
//             $limit
//         ]);
        
//         $process->run();
        
//         if (!$process->isSuccessful()) {
//             throw new ProcessFailedException($process);
//         }
        
//         $recommendations = json_decode($process->getOutput(), true);
        
//         // Urutkan berdasarkan similarity_score (descending)
//         usort($recommendations, function($a, $b) {
//             return $b['similarity_score'] <=> $a['similarity_score'];
//         });

//         $recommendedIds = array_column($recommendations, 'id');
//         $recommendedDocs = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//             ->whereIn('id', $recommendedIds)
//             ->get()
//             ->sortBy(function($item) use ($recommendedIds) {
//                 return array_search($item->id, $recommendedIds);
//             });

//         // Tambahkan similarity_score ke hasil akhir
//         $response = KoleksiResource::collection($recommendedDocs)->additional([
//             'similarity_scores' => array_column($recommendations, 'similarity_score')
//         ]);

//         return $response;

//     } catch (\Exception $e) {
//         Log::error("Recommendation error: ".$e->getMessage());
//         return $this->fallbackRecommendation($currentId, $limit);
//     }
// }

// private function fallbackRecommendation($currentId, $limit)
// {
//     $currentDoc = Koleksi::find($currentId);
    
//     if (empty($currentDoc->keywords)) {
//         // Jika keywords kosong, ambil berdasarkan kategori/penulis
//         return KoleksiResource::collection(
//             Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//                 ->where('id', '!=', $currentId)
//                 ->where(function($query) use ($currentDoc) {
//                     if ($currentDoc->kategori_bang_kom_id) {
//                         $query->orWhere('kategori_bang_kom_id', $currentDoc->kategori_bang_kom_id);
//                     }
//                     if ($currentDoc->penulis) {
//                         $query->orWhere('penulis', 'like', '%'.$currentDoc->penulis.'%');
//                     }
//                 })
//                 ->orderBy('views', 'desc')
//                 ->limit($limit)
//                 ->get()
//         );
//     }

//     $keywords = array_filter(explode(',', $currentDoc->keywords));
    
//     $query = Koleksi::with(['kategoriBangKom', 'jenisDokumen'])
//         ->where('id', '!=', $currentId);

//     foreach ($keywords as $keyword) {
//         $query->orWhere('keywords', 'like', '%'.trim($keyword).'%');
//     }

//     return KoleksiResource::collection(
//         $query->orderBy('views', 'desc')
//             ->limit($limit)
//             ->get()
//     );
// }

}
