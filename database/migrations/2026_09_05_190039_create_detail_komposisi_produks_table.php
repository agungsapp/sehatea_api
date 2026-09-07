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
        Schema::create('detail_komposisi_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komposisi_produk_id')->constrained('komposisi_produk')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $table->decimal('jumlah', 15, 4);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['komposisi_produk_id', 'bahan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_komposisi_produk');
    }
};
