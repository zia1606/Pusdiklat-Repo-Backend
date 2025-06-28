<?php

namespace App\Http\Controllers\Api;

use App\Models\KategoriBangKom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriBangKomController extends Controller
{
    // public function index() {
    //     $kategori = KategoriBangKom::all();
    //     return response()->json($kategori, 200);
    // }

    public function index()
    {
        $kategori = KategoriBangKom::all();
        return response()->json([
            'data' => $kategori,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:kategori_bang_kom,nama',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $kategori = KategoriBangKom::create([
            'nama' => $request->nama,
        ]);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $kategori,
        ], 201);
    }

    public function show($id)
    {
        $kategori = KategoriBangKom::find($id);
        if ($kategori) {
            return response()->json($kategori, 200);
        } else {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $kategori = KategoriBangKom::find($id);
        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:kategori_bang_kom,nama,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $kategori->update([
            'nama' => $request->nama,
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'data' => $kategori,
        ], 200);
    }

    public function destroy($id)
    {
        $kategori = KategoriBangKom::find($id);
        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus',
        ], 200);
    }
}
