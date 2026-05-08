<?php

namespace App\Http\Controllers\Api;
use App\Models\FormatKoleksi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormatKoleksiController extends Controller
{
    public function index()
    {
        $koleksiController = FormatKoleksi::all();
        return response()->json([
            'data' => $koleksiController,
        ]);
    }

    public function show($id)
    {
        $koleksiController = FormatKoleksi::find($id);
        if ($koleksiController) {
            return response()->json($koleksiController, 200);
        } else {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }
    }
}
