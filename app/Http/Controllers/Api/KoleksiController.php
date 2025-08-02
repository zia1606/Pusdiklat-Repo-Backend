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
use Symfony\Component\Process\Process;
use App\Http\Resources\KoleksiResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class KoleksiController extends Controller
{

    public function index(Request $request)
    {
        // Ambil parameter sorting dari request
        $sortBy = $request->query('sort_by', 'terbaru'); // Default: terbaru
        $perPage = $request->query('per_page', 10); // Default: 10 item per halaman

        // Query dasar dengan eager loading untuk menghindari N+1
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
            // File disimpan di: storage/app/public/dokumen_pdf/nama-file-random.pdf
            // Bisa diakses via URL: /storage/dokumen_pdf/nama-file-random.pdf
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
            //enampilkan data yang dihapus
            'data' => new KoleksiResource($koleksi), 
        ], 200);
    }

    public function filter(Request $request)
    {
        $query = Koleksi::query();

        // Pencarian
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                ->orWhere('penulis', 'like', '%' . $search . '%')
                ->orWhere('keywords', 'like', '%' . $search . '%');
            });
        }

        // Filter kategori (OR jika multiple)
        if ($request->has('kategori')) {
            $kategori = $request->input('kategori');
            if (is_array($kategori)) {
                $query->whereIn('kategori_bang_kom_id', $kategori);
            }
        }

        // Filter jenis dokumen (OR jika multiple)
        if ($request->has('jenisDokumen')) {
            $jenisDokumen = $request->input('jenisDokumen');
            if (is_array($jenisDokumen)) {
                $query->whereIn('jenis_dokumen_id', $jenisDokumen);
            }
        }

        // Filter tahun
        if ($request->has('tahun')) {
            $query->where('tahun_terbit', '>=', $request->tahun);
        }

        // Filter rentang tahun
        if ($request->has('customStart') && $request->has('customEnd')) {
            $customStart = $request->input('customStart');
            $customEnd = $request->input('customEnd');
            
            if (is_numeric($customStart) && is_numeric($customEnd)) {
                $query->whereBetween('tahun_terbit', [$customStart, $customEnd]);
            }
        }

        // Sorting dengan pengurutan sekunder
        $sortBy = $request->query('sort_by', 'terbaru');
        switch ($sortBy) {
            case 'terlama':
                $query->orderBy('tahun_terbit', 'asc')->orderBy('id', 'asc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc')->orderBy('id', 'asc');
                break;
            default: // Terbaru
                $query->orderBy('tahun_terbit', 'desc')->orderBy('id', 'desc');
                break;
        }

        // Pagination
        $perPage = $request->query('per_page', 10);
        $koleksi = $query->paginate($perPage);

        return response()->json([
            'data' => KoleksiResource::collection($koleksi),
            'current_page' => $koleksi->currentPage(),
            'per_page' => $koleksi->perPage(),
            'total' => $koleksi->total(),
            'last_page' => $koleksi->lastPage(),
            'next_page_url' => $koleksi->nextPageUrl(),
            'prev_page_url' => $koleksi->previousPageUrl(),
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

        return response()->json(['message' => 'Koleksi berhasil dihapus dari best collection']);
    }

    public function markAsBestCollection($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->update(['is_best_collection' => true]);

        return response()->json(['message' => 'Koleksi berhasil ditandai sebagai best collection']);
    }

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

    // public function showAdminPdf($id, Request $request)
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

    //         // Tidak perlu menambahkan ke riwayat baca atau increment views untuk admin

    //         $pdfContent = file_get_contents($pdfPath);

    //         return Response::make($pdfContent, 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'inline; filename="' . $koleksi->judul . '.pdf"',
    //             'Cache-Control' => 'no-cache, no-store, must-revalidate',
    //             'Pragma' => 'no-cache',
    //             'Expires' => '0',
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Error serving admin PDF: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Terjadi kesalahan saat memuat dokumen',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

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

}