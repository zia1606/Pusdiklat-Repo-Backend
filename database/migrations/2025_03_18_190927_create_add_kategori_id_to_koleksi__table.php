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
            $table->dropForeign(['kategori_bang_kom_id']);

            // Ubah kolom menjadi nullable jika diperlukan
            $table->unsignedBigInteger('kategori_bang_kom_id')->nullable()->change();

            // Tambahkan foreign key dengan onDelete('set null')
            $table->foreign('kategori_bang_kom_id')->references('id')->on('kategori_bang_kom')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            // Hapus foreign key
            $table->dropForeign(['kategori_bang_kom_id']);

            // Ubah kolom menjadi tidak nullable jika diperlukan
            $table->unsignedBigInteger('kategori_bang_kom_id')->nullable(false)->change();
        });
    }
    
};
