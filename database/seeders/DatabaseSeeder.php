<?php

namespace Database\Seeders;

use App\Models\FormatKoleksi;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder lainnya
        $this->call([
            RolesSeeder::class,
            UserSeeder::class,
            FormatKoleksiSeeder::class,
            PenulisSeeder::class,
            KategoriSeeder::class,
            FaqSeeder::class,
            TataTertibSeeder::class,
            PemberitahuanSeeder::class,
            SettingSeeder::class,
            BukuTamuSeeder::class,
            NewSystemSeeder::class, // Menambahkan data koleksi
        ]);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
