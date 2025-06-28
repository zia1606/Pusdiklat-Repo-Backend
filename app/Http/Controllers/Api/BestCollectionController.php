<?php

namespace App\Http\Controllers;

use App\Models\BestCollection;
use App\Models\RiwayatBestCollection;
use Illuminate\Http\Request;

class BestCollectionController extends Controller
{
    // Menampilkan semua best collection
    public function index()
    {
        $bestCollection = BestCollection::with('koleksi')->get();
        return response()->json($bestCollection);
    }

    // Menyimpan best collection baru
    public function store(Request $request)
    {
        $request->validate([
            'koleksi_id' => 'required|exists:koleksi,id',
        ]);

        $bestCollection = BestCollection::create($request->all());
        
        // Catat riwayat
        RiwayatBestCollection::create([
            'koleksi_id' => $request->koleksi_id,
            'action' => 'added',
            'metadata' => [
                'added_at' => now()->toDateTimeString()
            ]
        ]);

        return response()->json($bestCollection, 201);
    }

    // Menghapus best collection
    public function destroy($id)
    {
        // BestCollection::destroy($id);
        // return response()->json(null, 204);
        $bestCollection = BestCollection::findOrFail($id);
        
        // Catat riwayat sebelum dihapus
        // RiwayatBestCollection::create([
        //     'koleksi_id' => $bestCollection->koleksi_id,
        //     'action' => 'removed',
        //     'metadata' => [
        //         'removed_at' => now()->toDateTimeString()
        //     ]
        // ]);

        $bestCollection->delete();
        return response()->json(null, 204);
    }

    // Menampilkan riwayat best collection
    public function history()
    {
        $history = RiwayatBestCollection::with('koleksi')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}