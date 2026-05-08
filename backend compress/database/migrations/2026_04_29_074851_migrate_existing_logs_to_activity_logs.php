<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate Login Logs
        $loginLogs = DB::table('login_logs')->get();
        foreach ($loginLogs as $log) {
            DB::table('activity_logs')->insert([
                'user_id' => $log->user_id,
                'category' => 'Login',
                'action' => 'Login',
                'description' => 'Pengguna berhasil login ke sistem',
                'properties' => json_encode(['ip' => $log->ip_address, 'user_agent' => $log->user_agent]),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ]);
        }

        // Migrate Buku Tamu
        $bukuTamus = DB::table('buku_tamus')->get();
        foreach ($bukuTamus as $log) {
            DB::table('activity_logs')->insert([
                'user_id' => $log->user_id,
                'category' => 'Buku Tamu',
                'action' => 'Mengisi Buku Tamu',
                'description' => "Mengisi buku tamu dengan tujuan: {$log->tujuan}",
                'properties' => json_encode(['tujuan' => $log->tujuan, 'tujuan_lain' => $log->tujuan_lain]),
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ]);
        }

        // Migrate Riwayat Baca
        $riwayatBaca = DB::table('riwayat_baca')
            ->leftJoin('koleksi', 'riwayat_baca.koleksi_id', '=', 'koleksi.id')
            ->select('riwayat_baca.*', 'koleksi.judul')
            ->get();
        foreach ($riwayatBaca as $log) {
            DB::table('activity_logs')->insert([
                'user_id' => $log->user_id,
                'category' => 'Riwayat Baca',
                'action' => 'Membaca Koleksi',
                'description' => "Membaca koleksi: " . ($log->judul ?? 'Koleksi Terhapus'),
                'properties' => json_encode(['koleksi_id' => $log->koleksi_id, 'judul' => $log->judul]),
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ]);
        }

        // Migrate Favorit
        $favorit = DB::table('favorit')
            ->leftJoin('koleksi', 'favorit.koleksi_id', '=', 'koleksi.id')
            ->select('favorit.*', 'koleksi.judul')
            ->get();
        foreach ($favorit as $log) {
            DB::table('activity_logs')->insert([
                'user_id' => $log->user_id,
                'category' => 'Favorit',
                'action' => 'Menambah Favorit',
                'description' => "Menambahkan koleksi ke favorit: " . ($log->judul ?? 'Koleksi Terhapus'),
                'properties' => json_encode(['koleksi_id' => $log->koleksi_id, 'judul' => $log->judul]),
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('activity_logs')->truncate();
    }
};
