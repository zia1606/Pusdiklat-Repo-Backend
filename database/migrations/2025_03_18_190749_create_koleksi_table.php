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
        Schema::create('koleksi', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('penulis');
            $table->longText('ringkasan')->nullable();
            $table->unsignedBigInteger('kategori_bang_kom_id')->nullable();
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable();
            $table->unsignedBigInteger('format_koleksi_id')->nullable();
            $table->string('tahun_terbit');
            $table->string('penerbit')->nullable();
            $table->string('edisi_cetak')->nullable();
            $table->string('tempat_terbit')->nullable();
            $table->string('isbn')->nullable();
            $table->string('isbn_digital')->nullable();
            $table->string('issn')->nullable();
            $table->string('jml_hal_romawi')->nullable();
            $table->string('jml_hal_angka')->nullable();
            $table->string('Klasifikasi')->nullable();
            $table->string('kode')->nullable();
            $table->string('id_rak')->nullable();
            $table->string('dokumen_pdf')->nullable();
            $table->text('keywords')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('is_best_collection')->default(false);
            $table->longText('preprocessing')->nullable();
            $table->string('youtube_link')->nullable();
            $table->timestamps();

            $table->foreign('kategori_bang_kom_id')
                ->references('id')
                ->on('kategori_bang_kom')
                ->onDelete('set null');

            $table->foreign('jenis_dokumen_id')
                ->references('id')
                ->on('jenis_dokumen')
                ->onDelete('set null');

            $table->foreign('format_koleksi_id')
                ->references('id')
                ->on('format_koleksi')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            $table->dropForeign(['kategori_bang_kom_id']);
            $table->dropForeign(['jenis_dokumen_id']);
            $table->dropForeign(['format_koleksi_id']);
        });

        Schema::dropIfExists('koleksi');
    }
};