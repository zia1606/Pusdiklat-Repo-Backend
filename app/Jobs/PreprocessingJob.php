<?php

// 1. Buat Job untuk Preprocessing
// File: app/Jobs/PreprocessingJob.php

namespace App\Jobs;

use App\Models\Koleksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PreprocessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $koleksiId;

    public function __construct($koleksiId)
    {
        $this->koleksiId = $koleksiId;
    }

    public function handle()
    {
        try {
            $koleksi = Koleksi::with('kategoriBangKom')->find($this->koleksiId);
            
            if (!$koleksi) {
                Log::error("Koleksi dengan ID {$this->koleksiId} tidak ditemukan");
                return;
            }

            // Kirim data ke Flask untuk preprocessing
            $response = Http::timeout(30)->post(
                env('FLASK_PREPROCESSING_URL', 'https://pusdiklat-repo-rekomendasi.zeabur.app/preprocess'),
                [
                    'id' => $koleksi->id,
                    'judul' => $koleksi->judul ?? '',
                    'ringkasan' => $koleksi->ringkasan ?? '',
                    'kategori' => $koleksi->kategoriBangKom->nama ?? ''
                ]
            );

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['status'] === 'success') {
                    $koleksi->update([
                        'preprocessing' => $result['preprocessing'],
                        'preprocessing_status' => 'completed',
                        'preprocessing_completed_at' => now()
                    ]);
                    
                    Log::info("Preprocessing berhasil untuk koleksi ID: {$this->koleksiId}");
                } else {
                    throw new \Exception($result['message'] ?? 'Error dari service preprocessing');
                }
            } else {
                throw new \Exception('Gagal terhubung ke service preprocessing');
            }

        } catch (\Exception $e) {
            Log::error("Preprocessing error untuk koleksi ID {$this->koleksiId}: " . $e->getMessage());
            
            // Update status error
            if ($koleksi = Koleksi::find($this->koleksiId)) {
                $koleksi->update([
                    'preprocessing_status' => 'failed',
                    'preprocessing_error' => $e->getMessage()
                ]);
            }
        }
    }
}