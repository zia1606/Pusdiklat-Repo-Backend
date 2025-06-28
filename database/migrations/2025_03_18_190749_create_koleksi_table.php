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
            $table->unsignedBigInteger('format_koleksi_id')->nullable(); // Diatur sebagai nullable
            $table->string('tahun_terbit');
            $table->string('penerbit')->nullable();
            $table->string('dokumen_pdf')->nullable(); // Untuk menyimpan file PDF
            $table->text('keywords')->nullable(); // Tambahkan kolom keywords sebagai JSON
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('is_best_collection')->default(false);
            $table->longText('preprocessing')->nullable();
            $table->string('youtube_link')->nullable();
            $table->timestamps();

            // Foreign key untuk kategori_bang_kom_id
            $table->foreign('kategori_bang_kom_id')
                  ->references('id')
                  ->on('kategori_bang_kom')
                  ->onDelete('cascade');

            // Foreign key untuk jenis_dokumen_id
            $table->foreign('jenis_dokumen_id')
                  ->references('id')
                  ->on('jenis_dokumen')
                  ->onDelete('cascade');

            // Foreign key untuk format_koleksi_id
            $table->foreign('format_koleksi_id')
                  ->references('id')
                  ->on('format_koleksi')
                  ->onDelete('set null'); // Jika format koleksi dihapus, set null di koleksi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            // Hapus foreign key untuk format_koleksi_id
            $table->dropForeign(['format_koleksi_id']);
            // Hapus kolom format_koleksi_id
            $table->dropColumn('format_koleksi_id');
        });

        Schema::dropIfExists('koleksi');
    }
};