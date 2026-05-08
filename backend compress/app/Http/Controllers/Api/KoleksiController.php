<?php

namespace App\Http\Controllers\Api;

use App\Models\Koleksi;
use App\Models\Penulis;
use App\Models\Kategori;
use App\Models\SubKategori;
use App\Models\RiwayatBaca;
use Illuminate\Http\Request;
use App\Jobs\PreprocessingJob;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\KoleksiResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KoleksiController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->query('sort_by', 'terbaru');
        $perPage = $request->query('per_page', 10);

        $query = Koleksi::with(['penulisList', 'kategoris', 'subKategoris'])->activeForUser();

        switch ($sortBy) {
            case 'terlama':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->orderBy('tahun_terbit', 'desc');
                break;
        }

        $koleksi = $query->paginate($perPage);

        return KoleksiResource::collection($koleksi);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string',
            'no_induk' => 'nullable|string', // Nullable specifically for books as per user request
            'penulis_ids' => 'required|array',
            'penulis_ids.*' => 'exists:penulis,id',
            'kategori_ids' => 'required|array',
            'kategori_ids.*' => 'exists:kategori,id',
            'sub_kategori_ids' => 'nullable|array',
            'sub_kategori_ids.*' => 'exists:sub_kategori,id',
            'ringkasan' => 'nullable|string',
            'tahun_terbit' => 'required|string',
            'penerbit' => 'nullable|string',
            'keywords' => 'nullable|string',
            'dokumen_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'youtube_link' => 'nullable|url',
            'content_type' => 'required|in:pdf,youtube',
            'format_koleksi_id' => 'nullable|exists:format_koleksi,id',
            'edisi_cetak' => 'nullable|string',
            'tempat_terbit' => 'nullable|string',
            'isbn' => 'nullable|string',
            'isbn_digital' => 'nullable|string',
            'issn' => 'nullable|string',
            'jml_hal_romawi' => 'nullable|string',
            'jml_hal_angka' => 'nullable|string',
            'ddc' => 'nullable|string',
            'cutter' => 'nullable|string',
            'call_number' => 'nullable|string',
            'Klasifikasi' => 'nullable|string',
            'kode' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'error' => $validator->errors(),
            ], 422);
        }

        // Upload PDF to NAS logic... (keeping existing logic)
        $pdfPath = null;
        if ($request->content_type === 'pdf' && $request->hasFile('dokumen_pdf')) {
            $file = $request->file('dokumen_pdf');
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.nas.api_key'),
                    'Accept' => 'application/json',
                ])->attach(
                    'file', 
                    fopen($file->getRealPath(), 'r'), 
                    $file->getClientOriginalName()
                )->post(config('services.nas.base_url') . config('services.nas.endpoint_upload'));

                if ($response->successful()) {
                    $pdfPath = $response->json('path');
                }
            } catch (\Exception $e) {
                Log::error('NAS Upload Error: ' . $e->getMessage());
            }
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $koleksi = Koleksi::create([
            'no_induk' => $request->no_induk,
            'judul' => $request->judul,
            'ringkasan' => $request->ringkasan,
            'tahun_terbit' => $request->tahun_terbit,
            'penerbit' => $request->penerbit,
            'edisi_cetak' => $request->edisi_cetak,
            'tempat_terbit' => $request->tempat_terbit,
            'isbn' => $request->isbn,
            'isbn_digital' => $request->isbn_digital,
            'issn' => $request->issn,
            'jml_hal_romawi' => $request->jml_hal_romawi,
            'jml_hal_angka' => $request->jml_hal_angka,
            'ddc' => $request->ddc,
            'cutter' => $request->cutter,
            'call_number' => $request->call_number,
            'Klasifikasi' => $request->Klasifikasi,
            'kode' => $request->kode,
            'thumbnail' => $thumbnailPath,
            'keywords' => $request->keywords,
            'dokumen_pdf' => $pdfPath,
            'youtube_link' => $request->youtube_link,
            'is_active' => $request->is_active ?? 0,
            'preprocessing' => 'pending',
        ]);

        // Sync relationships
        $koleksi->penulisList()->sync($request->penulis_ids);
        $koleksi->kategoris()->sync($request->kategori_ids);
        if ($request->has('sub_kategori_ids')) {
            $koleksi->subKategoris()->sync($request->sub_kategori_ids);
        }

        return response()->json([
            'message' => 'Koleksi berhasil ditambahkan.',
            'data' => new KoleksiResource($koleksi->load(['penulisList', 'kategoris', 'subKategoris'])),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $koleksi = Koleksi::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string',
            'penulis_ids' => 'sometimes|array',
            'penulis_ids.*' => 'exists:penulis,id',
            'kategori_ids' => 'sometimes|array',
            'kategori_ids.*' => 'exists:kategori,id',
            'sub_kategori_ids' => 'nullable|array',
            'sub_kategori_ids.*' => 'exists:sub_kategori,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'dokumen_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'youtube_link' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'error' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['penulis_ids', 'kategori_ids', 'sub_kategori_ids', 'thumbnail', 'dokumen_pdf']);

        // Handle Thumbnail
        if ($request->hasFile('thumbnail')) {
            if ($koleksi->thumbnail) {
                Storage::disk('public')->delete($koleksi->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        // Handle PDF Upload to NAS
        if ($request->hasFile('dokumen_pdf')) {
            $file = $request->file('dokumen_pdf');
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.nas.api_key'),
                    'Accept' => 'application/json',
                ])->attach(
                    'file', 
                    fopen($file->getRealPath(), 'r'), 
                    $file->getClientOriginalName()
                )->post(config('services.nas.base_url') . config('services.nas.endpoint_upload'));

                if ($response->successful()) {
                    $data['dokumen_pdf'] = $response->json('path');
                }
            } catch (\Exception $e) {
                Log::error('NAS Update Error: ' . $e->getMessage());
            }
        }

        $koleksi->update($data);

        // Sync relationships
        if ($request->has('penulis_ids')) {
            $koleksi->penulisList()->sync($request->penulis_ids);
        }
        if ($request->has('kategori_ids')) {
            $koleksi->kategoris()->sync($request->kategori_ids);
        }
        if ($request->has('sub_kategori_ids')) {
            $koleksi->subKategoris()->sync($request->sub_kategori_ids);
        }

        return response()->json([
            'message' => 'Koleksi berhasil diperbarui.',
            'data' => new KoleksiResource($koleksi->load(['penulisList', 'kategoris', 'subKategoris'])),
        ]);
    }

    public function filter(Request $request)
    {
        $query = Koleksi::with(['penulisList', 'kategoris', 'subKategoris']);

        // Admin can see all; public only sees active
        if (!Auth::guard('sanctum')->user() || !Auth::guard('sanctum')->user()->hasRole('admin')) {
            $query->where('is_active', 1);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%$search%")
                  ->orWhereHas('penulisList', function($pq) use ($search) {
                      $pq->where('nama', 'like', "%$search%");
                  });
            });
        }

        if ($request->has('kategori')) {
            $kategoriIds = array_filter((array) $request->kategori);
            if (!empty($kategoriIds)) {
                $query->whereHas('kategoris', function($q) use ($kategoriIds) {
                    $q->whereIn('kategori.id', $kategoriIds);
                });
            }
        }

        // BPS vs Non-BPS filter
        if ($request->filled('penerbit_type')) {
            if ($request->penerbit_type === 'BPS') {
                $query->where('penerbit', 'like', '%BPS%');
            } elseif ($request->penerbit_type === 'Non-BPS') {
                $query->where('penerbit', 'not like', '%BPS%');
            }
        }

        // is_active filter: accept '0', '1', or '' (all)
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (int) $request->is_active);
        }

        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort_by', 'terbaru');
        switch ($sortBy) {
            case 'terlama': $query->orderBy('created_at', 'asc'); break;
            case 'popular': $query->orderBy('views', 'desc'); break;
            default: $query->orderBy('created_at', 'desc'); break;
        }

        $koleksi = $query->paginate($perPage);
        return KoleksiResource::collection($koleksi);
    }

    public function show(Koleksi $koleksi)
    {
        return new KoleksiResource($koleksi->load(['penulisList', 'kategoris', 'subKategoris']));
    }

    public function destroy(Koleksi $koleksi)
    {
        $koleksi->delete();
        return response()->json(['message' => 'Koleksi berhasil dihapus']);
    }

    public function getDistribusiKategori()
    {
        $allCategories = Kategori::all();
        $distribution = Koleksi::join('koleksi_kategori', 'koleksi.id', '=', 'koleksi_kategori.koleksi_id')
            ->selectRaw('kategori_id, count(*) as total')
            ->groupBy('kategori_id')
            ->get()
            ->keyBy('kategori_id');
        
        $result = $allCategories->map(function ($category) use ($distribution) {
            return [
                'id' => $category->id,
                'nama' => $category->nama,
                'total' => $distribution->has($category->id) ? $distribution[$category->id]->total : 0
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function markAsBestCollection($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->is_best_collection = 1;
        $koleksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil ditandai sebagai Best Collection'
        ]);
    }

    public function unmarkAsBestCollection($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->is_best_collection = 0;
        $koleksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil dihapus dari Best Collection'
        ]);
    }

    public function markAsActive($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->is_active = 1;
        $koleksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil diaktifkan'
        ]);
    }

    public function unmarkAsActive($id)
    {
        $koleksi = Koleksi::findOrFail($id);
        $koleksi->is_active = 0;
        $koleksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil dinonaktifkan'
        ]);
    }

    public function edit($id)
    {
        $koleksi = Koleksi::with(['penulisList', 'kategoris', 'subKategoris'])->findOrFail($id);
        return new KoleksiResource($koleksi);
    }

    public function getDistribusiJenis()
    {
        // This was likely removed or moved, implementing dummy or checking if needed
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getMostFavoritedCollections()
    {
        $koleksi = Koleksi::withCount('favorits')
            ->orderBy('favorits_count', 'desc')
            ->take(5)
            ->get();
        return KoleksiResource::collection($koleksi);
    }

    public function getBestCollections()
    {
        $koleksi = Koleksi::with(['penulisList', 'kategoris'])
            ->where('is_best_collection', 1)
            ->where('is_active', 1)
            ->get();
        return KoleksiResource::collection($koleksi);
    }

    public function getTotalViews()
    {
        $total = Koleksi::sum('views');
        return response()->json(['success' => true, 'total' => $total]);
    }

    public function getYearRange()
    {
        $min = Koleksi::min('tahun_terbit') ?? date('Y');
        $max = Koleksi::max('tahun_terbit') ?? date('Y');
        return response()->json([
            'min_year' => $min,
            'max_year' => $max
        ]);
    }
}