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
        Schema::create('riwayat_best_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koleksi_id')->constrained('koleksi')->onDelete('cascade');
            $table->string('action'); // 'added' atau 'removed'
            $table->text('metadata')->nullable(); // Data tambahan jika diperlukan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_best_collection');
    }
};
