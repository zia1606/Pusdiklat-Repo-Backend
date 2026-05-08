<?php

namespace Database\Seeders;

use App\Models\BukuTamu;
use App\Models\User;
use Illuminate\Database\Seeder;

class BukuTamuSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(3)->get();
        $purposes = ['Penelitian', 'Tugas Kuliah', 'Membaca Santai'];

        foreach ($users as $index => $user) {
            BukuTamu::firstOrCreate(
                ['user_id' => $user->id, 'tujuan' => $purposes[$index]],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
