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

        // Ambil semua ID kategori, jenis dokumen, dan format koleksi yang ada
        $kategoriIds = KategoriBangKom::pluck('id')->toArray();
        $jenisDokumenIds = JenisDokumen::pluck('id')->toArray();
        $formatKoleksiIds = FormatKoleksi::pluck('id')->toArray();

        for ($i = 0; $i < 100; $i++) {
            // Generate kata kunci acak
            $keywords = [];
            for ($j = 0; $j < $faker->optional(0.5)->numberBetween(1, 5); $j++) {
                $keywords[] = $faker->word; // Menambahkan kata kunci acak
            }

            DB::table('koleksi')->insert([
                'judul' => $faker->sentence(4),
                'penulis' => $faker->name,
                'ringkasan' => $faker->optional()->paragraph(3),
                'kategori_bang_kom_id' => $faker->optional()->randomElement($kategoriIds), // Bisa null
                'jenis_dokumen_id' => $faker->optional()->randomElement($jenisDokumenIds), // Bisa null
                'format_koleksi_id' => $faker->optional()->randomElement($formatKoleksiIds), // Bisa null
                'tahun_terbit' => $faker->year,
                'penerbit' => $faker->optional()->company,
                'edisi_cetak' => $faker->optional()->numberBetween(1, 10) . ' th ed.',
                'tempat_terbit' => $faker->city,
                'isbn' => $faker->isbn13,
                'isbn_digital' => $faker->isbn13,
                'issn' => $faker->isbn10,
                'jml_hal_romawi' => 'xiv',
                'jml_hal_angka' => $faker->numberBetween(100, 500),
                'Klasifikasi' => $faker->randomElement(['000', '100', '200', '300', '400', '500', '600', '700', '800', '900']),
                'kode' => $faker->bothify('??-###'),
                'thumbnail' => 'https://picsum.photos/seed/' . $faker->word . '/200/300',
                'dokumen_pdf' => $faker->url, // Simulasi URL dokumen PDF
                'keywords' => implode(', ', $keywords), // Simpan sebagai string dipisahkan koma
                'views' => $faker->numberBetween(0, 10), // Simulasi jumlah views
                'is_active' => $faker->boolean(80), // 80% chance of being active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}