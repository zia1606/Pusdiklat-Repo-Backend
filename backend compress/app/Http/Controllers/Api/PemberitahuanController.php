<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemberitahuan;
use Illuminate\Http\Request;

class PemberitahuanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pemberitahuan::query();
        // Return all if admin, else only active
        if (!$request->user() || !$request->user()->tokenCan('admin')) {
            $query->where('is_active', true);
        }
        $query->orderBy('date', 'desc')->orderBy('id', 'desc');
        return response()->json([
            'data' => $query->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'nullable|date',
            'icon_type' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $pemberitahuan = Pemberitahuan::create($validated);
        return response()->json([
            'message' => 'Pemberitahuan created successfully',
            'data' => $pemberitahuan
        ], 201);
    }

    public function show($id)
    {
        $pemberitahuan = Pemberitahuan::findOrFail($id);
        return response()->json([
            'data' => $pemberitahuan
        ]);
    }

    public function update(Request $request, $id)
    {
        $pemberitahuan = Pemberitahuan::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date' => 'nullable|date',
            'icon_type' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $pemberitahuan->update($validated);
        return response()->json([
            'message' => 'Pemberitahuan updated successfully',
            'data' => $pemberitahuan
        ]);
    }

    public function destroy($id)
    {
        $pemberitahuan = Pemberitahuan::findOrFail($id);
        $pemberitahuan->delete();
        return response()->json([
            'message' => 'Pemberitahuan deleted successfully'
        ]);
    }
}
