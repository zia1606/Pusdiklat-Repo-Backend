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
            // New fields
            $table->string('no_induk')->nullable()->after('id');
            $table->unsignedBigInteger('kategori_id')->nullable()->after('ringkasan');
            $table->unsignedBigInteger('sub_kategori_id')->nullable()->after('kategori_id');
            $table->string('ddc')->nullable()->after('sub_kategori_id');
            $table->string('cutter')->nullable()->after('ddc');
            $table->string('call_number')->nullable()->after('cutter');

            // Set penulis as nullable because it will be moved to pivot table
            $table->string('penulis')->nullable()->change();

            // Foreign keys
            $table->foreign('kategori_id')->references('id')->on('kategori')->onDelete('set null');
            $table->foreign('sub_kategori_id')->references('id')->on('sub_kategori')->onDelete('set null');

            // Drop old columns (must drop foreign keys first)
            $table->dropForeign(['kategori_bang_kom_id']);
            $table->dropForeign(['jenis_dokumen_id']);
            $table->dropColumn(['kategori_bang_kom_id', 'jenis_dokumen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('koleksi', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropForeign(['sub_kategori_id']);
            $table->dropColumn(['no_induk', 'kategori_id', 'sub_kategori_id', 'ddc', 'cutter', 'call_number']);

            $table->unsignedBigInteger('kategori_bang_kom_id')->nullable();
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable();
            $table->foreign('kategori_bang_kom_id')->references('id')->on('kategori_bang_kom')->onDelete('set null');
            $table->foreign('jenis_dokumen_id')->references('id')->on('jenis_dokumen')->onDelete('set null');
        });
    }
};
