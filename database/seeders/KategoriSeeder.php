<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\SubKategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Ilmu Komputer' => ['IT', 'Sistem Informasi', 'Data Science', 'Programming'],
            'Ekonomi & Bisnis' => ['Ekonomi', 'Akuntansi', 'Manajemen', 'Keuangan', 'Pemasaran'],
            'Sosial & Politik' => ['Sosial', 'Ilmu Sosial', 'Politik', 'Hukum', 'Sosiologi'],
            'Sains & Teknik' => ['Matematika', 'Statistik', 'Teknik Sipil', 'Fisika', 'Kimia'],
            'Kesehatan' => ['Kedokteran', 'Keperawatan', 'Farmasi', 'Gizi'],
            'Pendidikan' => ['Kurikulum', 'Metode Pengajaran', 'Psikologi Pendidikan'],
        ];

        foreach ($categories as $catName => $subs) {
            $cat = Kategori::firstOrCreate(['nama' => $catName]);
            foreach ($subs as $subName) {
                SubKategori::firstOrCreate([
                    'nama' => $subName,
                    'kategori_id' => $cat->id
                ]);
            }
        }
    }
}
