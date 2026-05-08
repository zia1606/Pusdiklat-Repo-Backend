<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_name' => 'GoJags E-Library',
            'contact_email' => 'support@gojags.id',
            'contact_phone' => '021-1234567',
            'address' => 'Jl. Kebon Sirih No. 1, Jakarta Pusat',
            'running_text' => 'Selamat datang di Layanan E-Library GoJags. Selamat membaca!',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
