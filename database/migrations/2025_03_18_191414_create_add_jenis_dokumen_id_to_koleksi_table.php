<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            // Hapus foreign key jika sudah ada
            $table->dropForeign(['jenis_dokumen_id']);

            // Ubah kolom menjadi nullable jika diperlukan
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable()->change();

            // Tambahkan foreign key dengan onDelete('set null')
            $table->foreign('jenis_dokumen_id')
                  ->references('id')
                  ->on('jenis_dokumen')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            // Hapus foreign key
            $table->dropForeign(['jenis_dokumen_id']);

            // Ubah kolom menjadi tidak nullable jika diperlukan
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable(false)->change();
        });
    }
};