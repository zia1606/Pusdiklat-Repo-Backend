<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BukuTamu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BukuTamuController extends Controller
{
    /**
     * Display a listing of the resource for admin.
     */
    public function index(Request $request)
    {
        $query = BukuTamu::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('tujuan', 'like', "%{$search}%")
              ->orWhere('tujuan_lain', 'like', "%{$search}%");
        }

        $bukuTamus = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bukuTamus
        ]);
    }

    /**
     * Store a newly created resource in storage (for user).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tujuan' => 'required|string|in:membaca,bekerja/belajar,diskusi,lain-lain',
            'tujuan_lain' => 'required_if:tujuan,lain-lain|nullable|string',
        ], [
            'tujuan.in' => 'Pilihan tujuan tidak valid.',
            'tujuan_lain.required_if' => 'Harap isi keterangan tujuan lainnya.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $bukuTamu = BukuTamu::create([
            'user_id' => auth()->id(),
            'tujuan' => $request->tujuan,
            'tujuan_lain' => $request->tujuan_lain,
        ]);

        \App\Models\ActivityLog::record(
            auth()->id(),
            'Buku Tamu',
            'Mengisi Buku Tamu',
            "Mengisi buku tamu dengan tujuan: {$request->tujuan}",
            ['tujuan' => $request->tujuan, 'tujuan_lain' => $request->tujuan_lain]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengisi buku tamu.',
            'data' => $bukuTamu
        ], 201);
    }

    /**
     * Update the specified resource in storage (for admin).
     */
    public function update(Request $request, $id)
    {
        $bukuTamu = BukuTamu::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tujuan' => 'required|string|in:membaca,bekerja/belajar,diskusi,lain-lain',
            'tujuan_lain' => 'required_if:tujuan,lain-lain|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $bukuTamu->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data buku tamu berhasil diupdate.',
            'data' => $bukuTamu
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy($id)
    {
        $bukuTamu = BukuTamu::findOrFail($id);
        $bukuTamu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data buku tamu berhasil dihapus (soft delete).'
        ]);
    }
}
