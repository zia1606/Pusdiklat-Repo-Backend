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
            $koleksi = Koleksi::find($this->koleksiId);
            
            if (!$koleksi) {
                Log::error("Koleksi dengan ID {$this->koleksiId} tidak ditemukan");
                return;
            }

            // Siapkan data untuk preprocessing
            $inputData = [
                'id' => $koleksi->id,
                'judul' => $koleksi->judul ?? '',
                'ringkasan' => $koleksi->ringkasan ?? ''
            ];

            // Panggil script Python untuk preprocessing
            $process = new Process([
                'python',
                base_path('app/Services/text_preprocessing.py'),
                json_encode($inputData)
            ]);

            $process->mustRun();
            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            // Update koleksi dengan hasil preprocessing
            $koleksi->update([
                'preprocessing' => $result['preprocessing'],
                'preprocessing_status' => 'completed',
                'preprocessing_completed_at' => now()
            ]);

            Log::info("Preprocessing berhasil untuk koleksi ID: {$this->koleksiId}");

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