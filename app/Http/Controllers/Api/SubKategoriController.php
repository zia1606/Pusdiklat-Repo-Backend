<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubKategoriController extends Controller
{
    public function index(Request $request)
    {
        $query = SubKategori::with('kategori');

        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        $subKategori = $query->orderBy('nama', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $subKategori
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $subKategori = SubKategori::create([
            'kategori_id' => $request->kategori_id,
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sub Kategori berhasil ditambahkan',
            'data' => $subKategori
        ], 201);
    }

    public function show(SubKategori $subKategori)
    {
        return response()->json([
            'status' => 'success',
            'data' => $subKategori->load('kategori')
        ]);
    }

    public function update(Request $request, $id)
    {
        $subKategori = SubKategori::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $subKategori->update([
            'kategori_id' => $request->kategori_id,
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sub Kategori berhasil diperbarui',
            'data' => $subKategori
        ]);
    }

    public function destroy($id)
    {
        $subKategori = SubKategori::findOrFail($id);
        
        $collectionsUsingSub = $subKategori->koleksi()->get();
        
        foreach ($collectionsUsingSub as $koleksi) {
            if ($koleksi->subKategoris()->count() <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub Kategori tidak bisa dihapus karena masih digunakan sebagai satu-satunya sub kategori pada koleksi: ' . $koleksi->judul
                ], 400);
            }
        }

        $subKategori->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sub Kategori berhasil dihapus'
        ]);
    }
}
