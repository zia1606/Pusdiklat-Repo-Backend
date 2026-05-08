<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::with('subKategori')->orderBy('nama', 'asc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $kategori
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:kategori,nama',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $kategori = Kategori::create([
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $kategori
        ], 201);
    }

    public function show(Kategori $kategori)
    {
        return response()->json([
            'status' => 'success',
            'data' => $kategori->load('subKategori')
        ]);
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:kategori,nama,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $kategori->update([
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $kategori
        ]);
    }

    public function destroy($id)
    {
        $kategori = Kategori::findOrFail($id);
        
        // Check collections that use this category
        $collectionsUsingCategory = $kategori->koleksi()->get();
        
        foreach ($collectionsUsingCategory as $koleksi) {
            // Check if this is the ONLY category for this collection
            if ($koleksi->kategoris()->count() <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak bisa dihapus karena masih digunakan sebagai satu-satunya kategori pada koleksi: ' . $koleksi->judul
                ], 400);
            }
        }

        $kategori->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
