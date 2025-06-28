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
        Schema::create('best_collection', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('koleksi_id');
            $table->timestamps();

            $table->foreign('koleksi_id')->references('id')->on('koleksi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('best_collection');
    }
};
