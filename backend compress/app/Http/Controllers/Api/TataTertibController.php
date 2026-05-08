<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TataTertib;
use Illuminate\Http\Request;

class TataTertibController extends Controller
{
    public function index(Request $request)
    {
        $query = TataTertib::query();
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
            'rule_text' => 'required|string',
            'order_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $tataTertib = TataTertib::create($validated);
        return response()->json([
            'message' => 'Tata Tertib created successfully',
            'data' => $tataTertib
        ], 201);
    }

    public function show($id)
    {
        $tataTertib = TataTertib::findOrFail($id);
        return response()->json([
            'data' => $tataTertib
        ]);
    }

    public function update(Request $request, $id)
    {
        $tataTertib = TataTertib::findOrFail($id);
        
        $validated = $request->validate([
            'rule_text' => 'sometimes|required|string',
            'order_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $tataTertib->update($validated);
        return response()->json([
            'message' => 'Tata Tertib updated successfully',
            'data' => $tataTertib
        ]);
    }

    public function destroy($id)
    {
        $tataTertib = TataTertib::findOrFail($id);
        $tataTertib->delete();
        return response()->json([
            'message' => 'Tata Tertib deleted successfully'
        ]);
    }
}
