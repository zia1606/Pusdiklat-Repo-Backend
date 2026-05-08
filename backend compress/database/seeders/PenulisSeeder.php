<?php

namespace Database\Seeders;

use App\Models\Penulis;
use Illuminate\Database\Seeder;

class PenulisSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            ['nama' => 'BPS RI'],
            ['nama' => 'M. Agus Setiawan'],
            ['nama' => 'Sri Wahyuni'],
            ['nama' => 'BPS Provinsi Gorontalo'],
            ['nama' => 'Dwi Handayani'],
            ['nama' => 'Robert K. Yin'],
            ['nama' => 'Sugiyono'],
            ['nama' => 'Andi Wijaya'],
            ['nama' => 'Siti Aminah'],
            ['nama' => 'Prof. Dr. Meidiana'],
        ];

        foreach ($authors as $author) {
            Penulis::firstOrCreate(['nama' => $author['nama']]);
        }
    }
}
