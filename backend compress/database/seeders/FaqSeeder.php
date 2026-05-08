<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqKategori;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Layanan' => [
                ['pertanyaan' => 'Bagaimana cara meminjam buku?', 'jawaban' => 'Anda bisa meminjam buku dengan mendaftar sebagai anggota terlebih dahulu.'],
                ['pertanyaan' => 'Berapa lama durasi peminjaman?', 'jawaban' => 'Durasi peminjaman adalah 14 hari.'],
            ],
            'Akun' => [
                ['pertanyaan' => 'Bagaimana cara reset password?', 'jawaban' => 'Klik menu "Lupa Password" pada halaman login.'],
                ['pertanyaan' => 'Apakah pendaftaran anggota gratis?', 'jawaban' => 'Ya, pendaftaran anggota tidak dipungut biaya.'],
            ],
            'Koleksi' => [
                ['pertanyaan' => 'Apakah tersedia buku digital?', 'jawaban' => 'Ya, kami menyediakan koleksi E-Book dalam format PDF.'],
            ]
        ];

        foreach ($categories as $catName => $faqs) {
            $cat = FaqKategori::firstOrCreate(
                ['name' => $catName],
                ['slug' => \Illuminate\Support\Str::slug($catName)]
            );
            foreach ($faqs as $faq) {
                Faq::updateOrCreate(
                    ['question' => $faq['pertanyaan']],
                    [
                        'faq_kategori_id' => $cat->id,
                        'answer' => $faq['jawaban'],
                        'is_active' => true
                    ]
                );
            }
        }
    }
}
