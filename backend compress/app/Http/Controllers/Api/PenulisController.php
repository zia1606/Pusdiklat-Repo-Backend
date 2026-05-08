<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penulis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PenulisController extends Controller
{
    public function index(Request $request)
    {
        $query = Penulis::query();

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $penulis = $query->orderBy('nama', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $penulis
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:penulis,nama',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $penulis = Penulis::create([
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Penulis berhasil ditambahkan',
            'data' => $penulis
        ], 201);
    }

    public function show(Penulis $penulis)
    {
        return response()->json([
            'status' => 'success',
            'data' => $penulis
        ]);
    }

    public function update(Request $request, $id)
    {
        $penulis = Penulis::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:penulis,nama,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $penulis->update([
            'nama' => $request->nama
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Penulis berhasil diperbarui',
            'data' => $penulis
        ]);
    }

    public function destroy($id)
    {
        $penulis = Penulis::findOrFail($id);

        if ($penulis->koleksi()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penulis tidak bisa dihapus karena masih terhubung dengan koleksi'
            ], 400);
        }

        $penulis->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Penulis berhasil dihapus'
        ]);
    }
}
