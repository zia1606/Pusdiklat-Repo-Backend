<?php

namespace Database\Seeders;

use App\Models\KategoriBangKom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriBangKomSeeder extends Seeder
{
    public function run()
    {
        $kategori = [
            ['nama' => 'Pelatihan Dasar'],
            ['nama' => 'PKA'],
            ['nama' => 'PKP'],
            ['nama' => 'TB/IB'],
        ];

        foreach ($kategori as $item) {
            KategoriBangKom::create($item);
        }
    }
}
