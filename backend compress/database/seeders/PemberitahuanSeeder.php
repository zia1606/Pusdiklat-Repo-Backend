<?php

namespace Database\Seeders;

use App\Models\Pemberitahuan;
use Illuminate\Database\Seeder;

class PemberitahuanSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = [
            [
                'judul' => 'Perpustakaan Tutup Libur Nasional',
                'isi' => 'Diberitahukan bahwa perpustakaan akan tutup pada tanggal 17 Agustus 2024.',
                'is_active' => true
            ],
            [
                'judul' => 'Koleksi Buku Baru Tersedia',
                'isi' => 'Kami baru saja menambahkan 50 judul buku baru di kategori Ilmu Komputer.',
                'is_active' => true
            ],
            [
                'judul' => 'Pemeliharaan Sistem',
                'isi' => 'Aplikasi akan tidak dapat diakses pada hari Minggu jam 22.00 - 02.00.',
                'is_active' => true
            ],
        ];

        foreach ($notifications as $n) {
            Pemberitahuan::updateOrCreate(
                ['title' => $n['judul']],
                [
                    'description' => $n['isi'],
                    'is_active' => $n['is_active'],
                    'date' => now()
                ]
            );
        }
    }
}
