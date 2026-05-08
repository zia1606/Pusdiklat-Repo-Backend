<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::with('kategori');
        if (!$request->user() || !$request->user()->tokenCan('admin')) {
            $query->where('is_active', true);
        }
        $query->orderBy('order_number', 'asc')->orderBy('id', 'asc');
        return response()->json([
            'data' => $query->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'faq_kategori_id' => 'required|exists:faq_kategoris,id',
            'question' => 'required|string',
            'answer' => 'required|string',
            'order_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $faq = Faq::create($validated);
        return response()->json([
            'message' => 'FAQ created successfully',
            'data' => $faq->load('kategori')
        ], 201);
    }

    public function show($id)
    {
        $faq = Faq::with('kategori')->findOrFail($id);
        return response()->json([
            'data' => $faq
        ]);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        
        $validated = $request->validate([
            'faq_kategori_id' => 'sometimes|required|exists:faq_kategoris,id',
            'question' => 'sometimes|required|string',
            'answer' => 'sometimes|required|string',
            'order_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $faq->update($validated);
        return response()->json([
            'message' => 'FAQ updated successfully',
            'data' => $faq->load('kategori')
        ]);
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        return response()->json([
            'message' => 'FAQ deleted successfully'
        ]);
    }
}
