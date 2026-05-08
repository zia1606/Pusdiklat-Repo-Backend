<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaqKategori;
use Illuminate\Http\Request;

class FaqKategoriController extends Controller
{
    public function index()
    {
        $kategoris = FaqKategori::orderBy('order_number', 'asc')->orderBy('id', 'asc')->get();
        return response()->json([
            'data' => $kategoris
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:faq_kategoris',
            'order_number' => 'integer'
        ]);

        $kategori = FaqKategori::create($validated);
        return response()->json([
            'message' => 'FAQ Kategori created successfully',
            'data' => $kategori
        ], 201);
    }

    public function show($id)
    {
        $kategori = FaqKategori::findOrFail($id);
        return response()->json([
            'data' => $kategori
        ]);
    }

    public function update(Request $request, $id)
    {
        $kategori = FaqKategori::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:faq_kategoris,slug,' . $kategori->id,
            'order_number' => 'integer'
        ]);

        $kategori->update($validated);
        return response()->json([
            'message' => 'FAQ Kategori updated successfully',
            'data' => $kategori
        ]);
    }

    public function destroy($id)
    {
        $kategori = FaqKategori::findOrFail($id);
        $kategori->delete();
        return response()->json([
            'message' => 'FAQ Kategori deleted successfully'
        ]);
    }
}
