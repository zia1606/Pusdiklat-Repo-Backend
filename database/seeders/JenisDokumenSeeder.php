<?php

namespace Database\Seeders;

use App\Models\JenisDokumen;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JenisDokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $jenisDokumen = [
            ['nama' => 'Skripsi'],
            ['nama' => 'Tesis'],
            ['nama' => 'Disertasi'],
            ['nama' => 'Laporan'],
        ];

        foreach ($jenisDokumen as $jenis) {
            JenisDokumen::create($jenis);
        }
    }
}
