<?php

namespace App\Http\Controllers\Api;

use App\Models\JenisDokumen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JenisDokumenController extends Controller
{
    public function index()
    {
        $jenisDokumen = JenisDokumen::all();
        return response()->json([
            'data' => $jenisDokumen,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:jenis_dokumen,nama',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jenisDokumen = JenisDokumen::create([
            'nama' => $request->nama,
        ]);

        return response()->json([
            'message' => 'Jenis dokumen berhasil ditambahkan',
            'data' => $jenisDokumen,
        ], 201);
    }

    public function show($id)
    {
        $jenisDokumen = JenisDokumen::find($id);
        if ($jenisDokumen) {
            return response()->json($jenisDokumen, 200);
        } else {
            return response()->json(['message' => 'Jenis dokumen tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $jenisDokumen = JenisDokumen::find($id);
        if (!$jenisDokumen) {
            return response()->json(['message' => 'Jenis dokumen tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:jenis_dokumen,nama,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jenisDokumen->update([
            'nama' => $request->nama,
        ]);

        return response()->json([
            'message' => 'Jenis dokumen berhasil diperbarui',
            'data' => $jenisDokumen,
        ], 200);
    }

    public function destroy($id)
    {
        $jenisDokumen = JenisDokumen::find($id);
        if (!$jenisDokumen) {
            return response()->json(['message' => 'Jenis dokumen tidak ditemukan'], 404);
        }

        $jenisDokumen->delete();

        return response()->json([
            'message' => 'Jenis dokumen berhasil dihapus',
        ], 200);
    }
}