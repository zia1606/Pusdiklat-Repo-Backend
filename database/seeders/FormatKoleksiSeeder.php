<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormatKoleksi;

class FormatKoleksiSeeder extends Seeder
{
    public function run(): void
    {
        FormatKoleksi::firstOrCreate(['nama' => 'PDF']);
        FormatKoleksi::firstOrCreate(['nama' => 'Video']);
    }
}