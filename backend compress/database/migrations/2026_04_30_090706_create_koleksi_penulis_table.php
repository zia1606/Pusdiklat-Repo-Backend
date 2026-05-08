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
        Schema::create('koleksi_penulis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koleksi_id')->constrained('koleksi')->onDelete('cascade');
            $table->foreignId('penulis_id')->constrained('penulis')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koleksi_penulis');
    }
};
