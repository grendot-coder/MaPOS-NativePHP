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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->string('jenis'); // penjualan | penyesuaian | pembelian | retur
            $table->integer('jumlah'); // positif = masuk, negatif = keluar
            $table->integer('saldo_sesudah');
            $table->string('keterangan')->nullable();
            $table->uuid('referensi_id')->nullable();
            $table->string('referensi_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
