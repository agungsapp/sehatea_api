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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->integer('grand_total');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran')->onDelete('RESTRICT');
            $table->foreignId('metode_pembelian_id')->constrained('metode_pembelian')->onDelete('RESTRICT');
            $table->foreignId('user_id')->constrained('users')->onDelete('RESTRICT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
