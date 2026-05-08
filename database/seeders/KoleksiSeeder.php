<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\KategoriBangKom;
use App\Models\JenisDokumen;
use App\Models\FormatKoleksi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KoleksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $kategoriIds = KategoriBangKom::pluck('id')->toArray();
        $jenisDokumenIds = JenisDokumen::pluck('id')->toArray();
        $formatKoleksiIds = FormatKoleksi::pluck('id')->toArray();

        for ($i = 0; $i < 100; $i++) {
            $keywords = [];
            $keywordCount = $faker->optional(0.5)->numberBetween(1, 5) ?? 0;
            for ($j = 0; $j < $keywordCount; $j++) {
                $keywords[] = $faker->word;
            }

            DB::table('koleksi')->insert([
                'judul'               => $faker->sentence(4),
                'penulis'             => $faker->name,
                'ringkasan'           => $faker->optional()->paragraph(3),
                'kategori_bang_kom_id'=> $faker->optional()->randomElement($kategoriIds),
                'jenis_dokumen_id'    => $faker->optional()->randomElement($jenisDokumenIds),
                'format_koleksi_id'   => $faker->optional()->randomElement($formatKoleksiIds),
                'tahun_terbit'        => $faker->year,
                'penerbit'            => $faker->optional()->company,

                // ── Field baru ──────────────────────────────────────────────
                'edisi_cetak'         => $faker->optional(0.4)->randomElement(['1', '2', '3', '4', '5']),
                'tempat_terbit'       => $faker->optional(0.6)->city,
                'isbn'                => $faker->optional(0.5)->isbn13,
                'isbn_digital'        => $faker->optional(0.3)->isbn13,
                'issn'                => $faker->optional(0.3)->numerify('####-####'),
                'jml_hal_romawi'      => $faker->optional(0.5)->numberBetween(1, 20),
                'jml_hal_angka'       => $faker->optional(0.7)->numberBetween(50, 800),
                'Klasifikasi'         => $faker->optional(0.6)->bothify('### ###.##'),
                'kode'                => $faker->optional(0.6)->bothify('??-####'),
                'is_best_collection'  => $faker->boolean(15), // 15% chance true
                'preprocessing'       => $faker->optional(0.3)->text(200),
                'youtube_link'        => $faker->optional(0.2)->url,
                'thumbnail'           => null, // kosongkan atau isi URL jika ada
                // ────────────────────────────────────────────────────────────

                'dokumen_pdf'         => $faker->url,
                'keywords'            => implode(', ', $keywords),
                'views'               => $faker->numberBetween(0, 10),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}