<?php

namespace Database\Seeders;

use App\Models\TataTertib;
use Illuminate\Database\Seeder;

class TataTertibSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['konten' => 'Dilarang membawa makanan dan minuman ke dalam perpustakaan.'],
            ['konten' => 'Harap menjaga ketenangan di dalam ruang baca.'],
            ['konten' => 'Kembalikan buku tepat waktu untuk menghindari denda.'],
            ['konten' => 'Dilarang merusak atau mencoret-coret koleksi buku.'],
        ];

        foreach ($rules as $rule) {
            TataTertib::firstOrCreate(
                ['rule_text' => $rule['konten']],
                ['is_active' => true]
            );
        }
    }
}
