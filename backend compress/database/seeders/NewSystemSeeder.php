<?php

namespace Database\Seeders;

use App\Models\Koleksi;
use App\Models\Penulis;
use App\Models\Kategori;
use App\Models\SubKategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for clean slate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('koleksi_penulis')->truncate();
        DB::table('koleksi_kategori')->truncate();
        DB::table('koleksi_sub_kategori')->truncate();
        DB::table('sub_kategori')->truncate();
        DB::table('kategori')->truncate();
        DB::table('penulis')->truncate();
        DB::table('koleksi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create More Authors (Ditambah agar lebih variatif)
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
        foreach ($authors as $a) {
            Penulis::create($a);
        }

        // 2. Create Categories & Subcategories
        $categories = [
            'Ilmu Komputer' => ['IT', 'Sistem Informasi', 'Data Science'],
            'Ekonomi & Bisnis' => ['Ekonomi', 'Akuntansi', 'Manajemen', 'Keuangan'],
            'Sosial & Politik' => ['Sosial', 'Ilmu Sosial', 'Politik'],
            'Sains & Teknik' => ['Matematika', 'Statistik', 'Teknik Sipil'],
        ];

        foreach ($categories as $catName => $subs) {
            $cat = Kategori::create(['nama' => $catName]);
            foreach ($subs as $subName) {
                SubKategori::create([
                    'nama' => $subName,
                    'kategori_id' => $cat->id
                ]);
            }
        }

        // 3. Create Collections (Data Utama + Loop untuk mencapai 30)
        $bpsAuthors = Penulis::where('nama', 'like', '%BPS%')->pluck('id')->toArray();
        $otherAuthors = Penulis::where('nama', 'not like', '%BPS%')->pluck('id')->toArray();
        $allCats = Kategori::all();
        $allFormats = DB::table('format_koleksi')->pluck('id')->toArray();

        // Data manual (4 data awal Anda dengan tambahan field baru)
        $koleksiData = [
            [
                'no_induk' => 'BPS-001',
                'judul' => 'Analisis Statistik Ekonomi 2024',
                'penerbit' => 'BPS RI',
                'tahun_terbit' => '2024',
                'is_active' => 1,
                'is_best_collection' => 1,
                'ringkasan' => 'Analisis mendalam mengenai indikator ekonomi makro Indonesia tahun 2024.',
                'keywords' => 'ekonomi, statistik, makro',
                'isbn' => '978-602-123-001-0',
                'isbn_digital' => '978-602-123-001-0-D',
                'issn' => '-',
                'ddc' => '330.021',
                'cutter' => 'BPS',
                'call_number' => '330.021 BPS a',
                'Klasifikasi' => 'Ekonomi',
                'format_koleksi_id' => 1,
                'jml_hal_angka' => '250',
                'jml_hal_romawi' => 'xiv',
                'edisi_cetak' => 'Cetakan 1',
                'tempat_terbit' => 'Jakarta',
                'kode' => 'K-EKO-001',
                'thumbnail' => 'thumbnails/koleksi1.jpg',
                'id_rak' => 'R-01',
                'dokumen_pdf' => 'documents/koleksi1.pdf',
                'views' => 150,
                'preprocessing' => 'pending',
                'youtube_link' => null,
            ],
            [
                'no_induk' => 'BK-456',
                'judul' => 'Metode Penelitian Kualitatif',
                'penerbit' => 'Alfabeta',
                'tahun_terbit' => '2023',
                'is_active' => 1,
                'is_best_collection' => 0,
                'ringkasan' => 'Panduan lengkap metodologi penelitian kualitatif untuk akademisi.',
                'keywords' => 'penelitian, kualitatif, metodologi',
                'isbn' => '978-602-123-002-7',
                'isbn_digital' => '978-602-123-002-7-D',
                'issn' => '-',
                'ddc' => '001.42',
                'cutter' => 'SUG',
                'call_number' => '001.42 SUG m',
                'Klasifikasi' => 'Metodologi',
                'format_koleksi_id' => 1,
                'jml_hal_angka' => '450',
                'jml_hal_romawi' => 'xx',
                'edisi_cetak' => 'Cetakan 3',
                'tempat_terbit' => 'Bandung',
                'kode' => 'K-MET-456',
                'thumbnail' => 'thumbnails/koleksi2.jpg',
                'id_rak' => 'R-05',
                'dokumen_pdf' => 'documents/koleksi2.pdf',
                'views' => 85,
                'preprocessing' => 'pending',
                'youtube_link' => null,
            ],
            [
                'no_induk' => 'GTO-789',
                'judul' => 'Gorontalo Dalam Angka 2023',
                'penerbit' => 'BPS Provinsi Gorontalo',
                'tahun_terbit' => '2023',
                'is_active' => 1,
                'is_best_collection' => 1,
                'ringkasan' => 'Data statistik tahunan untuk wilayah Provinsi Gorontalo.',
                'keywords' => 'gorontalo, statistik, daerah',
                'isbn' => '978-602-123-003-4',
                'isbn_digital' => '978-602-123-003-4-D',
                'issn' => '2086-4523',
                'ddc' => '310',
                'cutter' => 'BPS',
                'call_number' => '310 BPS g',
                'Klasifikasi' => 'Statistik',
                'format_koleksi_id' => 1,
                'jml_hal_angka' => '320',
                'jml_hal_romawi' => 'viii',
                'edisi_cetak' => 'Cetakan 1',
                'tempat_terbit' => 'Gorontalo',
                'kode' => 'K-GTO-789',
                'thumbnail' => 'thumbnails/koleksi3.jpg',
                'id_rak' => 'R-01',
                'dokumen_pdf' => 'documents/koleksi3.pdf',
                'views' => 210,
                'preprocessing' => 'pending',
                'youtube_link' => null,
            ],
            [
                'no_induk' => 'IT-101',
                'judul' => 'Belajar Data Science dengan Python',
                'penerbit' => 'Gramedia',
                'tahun_terbit' => '2022',
                'is_active' => 1,
                'is_best_collection' => 0,
                'ringkasan' => 'Implementasi data science menggunakan bahasa pemrograman Python.',
                'keywords' => 'data science, python, coding',
                'isbn' => '978-602-123-004-1',
                'isbn_digital' => '978-602-123-004-1-D',
                'issn' => '-',
                'ddc' => '005.13',
                'cutter' => 'SET',
                'call_number' => '005.13 SET b',
                'Klasifikasi' => 'IT',
                'format_koleksi_id' => 1,
                'jml_hal_angka' => '180',
                'jml_hal_romawi' => 'x',
                'edisi_cetak' => 'Cetakan 2',
                'tempat_terbit' => 'Jakarta',
                'kode' => 'K-IT-101',
                'thumbnail' => 'thumbnails/koleksi4.jpg',
                'id_rak' => 'R-10',
                'dokumen_pdf' => 'documents/koleksi4.pdf',
                'views' => 320,
                'preprocessing' => 'pending',
                'youtube_link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];

        // 4. Generate data tambahan hingga total 30
        for ($i = 5; $i <= 30; $i++) {
            $cat = $allCats->random();
            $matchingSubs = SubKategori::where('kategori_id', $cat->id)->get();
            $subCatId = $matchingSubs->count() > 0 ? $matchingSubs->random()->id : null;
            $isBPS = rand(0, 1);

            $koleksiData[] = [
                'no_induk' => "KOL-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'judul' => "Koleksi Pengetahuan Volume $i",
                'penerbit' => ($isBPS ? 'BPS RI' : 'Penerbit Erlangga'),
                'tahun_terbit' => (string)rand(2018, 2024),
                'is_active' => 1,
                'is_best_collection' => rand(0, 1),
                'ringkasan' => "Ringkasan materi untuk buku referensi ke-$i yang mencakup berbagai topik.",
                'kategori_id' => $cat->id,
                'sub_kategori_id' => $subCatId,
                'keywords' => 'referensi, buku, umum',
                'isbn' => '978-602-' . rand(10000, 99999),
                'isbn_digital' => '978-602-' . rand(10000, 99999) . '-D',
                'issn' => rand(0, 1) ? rand(1000, 9999) . '-' . rand(1000, 9999) : '-',
                'ddc' => rand(100, 900) . '.' . rand(1, 99),
                'cutter' => Str::upper(Str::random(3)),
                'call_number' => rand(100, 900) . " " . Str::upper(Str::random(3)) . " " . strtolower(Str::random(1)),
                'Klasifikasi' => $cat->nama,
                'format_koleksi_id' => !empty($allFormats) ? $allFormats[array_rand($allFormats)] : 1,
                'jml_hal_angka' => (string)rand(100, 600),
                'jml_hal_romawi' => 'xviii',
                'edisi_cetak' => 'Cetakan ' . rand(1, 5),
                'tempat_terbit' => 'Jakarta',
                'kode' => 'KODE-' . $i,
                'thumbnail' => 'thumbnails/default.jpg',
                'id_rak' => 'R-' . str_pad(rand(1, 20), 2, '0', STR_PAD_LEFT),
                'dokumen_pdf' => 'documents/sample.pdf',
                'views' => rand(0, 500),
                'preprocessing' => 'pending',
                'youtube_link' => rand(0, 5) == 0 ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
            ];
        }

        // 5. Simpan dan Hubungkan Relasi
        foreach ($koleksiData as $data) {
            $k = Koleksi::create($data);
            
            // Assign Author (Pivot)
            if (str_contains($data['penerbit'], 'BPS')) {
                $k->penulisList()->attach($bpsAuthors[array_rand($bpsAuthors)]);
            } else {
                $k->penulisList()->attach($otherAuthors[array_rand($otherAuthors)]);
            }

            // Assign Category (Pivot)
            if (isset($data['kategori_id'])) {
                $k->kategoris()->attach($data['kategori_id']);
            } else {
                // For manual data that doesn't have kategori_id in array yet
                $cat = $allCats->random();
                $k->kategoris()->attach($cat->id);
                $k->update(['kategori_id' => $cat->id]);

                $matchingSubs = SubKategori::where('kategori_id', $cat->id)->get();
                if ($matchingSubs->count() > 0) {
                    $sub = $matchingSubs->random();
                    $k->subKategoris()->attach($sub->id);
                    $k->update(['sub_kategori_id' => $sub->id]);
                }
            }

            // Assign matching subcategory (Pivot)
            if (isset($data['sub_kategori_id']) && $data['sub_kategori_id']) {
                $k->subKategoris()->attach($data['sub_kategori_id']);
            }
        }
    }
}