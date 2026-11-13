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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_produk')->unique();
            $table->string('nama');
            $table->foreignUuid('golongan_id')->constrained('golongans')->cascadeOnDelete();
            $table->string('satuan')->default('pcs');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_retail', 15, 2)->default(0);
            $table->decimal('harga_grosir_1', 15, 2)->nullable();
            $table->decimal('harga_grosir_2', 15, 2)->nullable();
            $table->decimal('harga_grosir_3', 15, 2)->nullable();
            $table->decimal('diskon', 5, 2)->default(0);
            $table->integer('stok_etalase')->default(0);
            $table->integer('stok_gudang')->default(0);
            $table->integer('stok')->default(0);
            $table->integer('min_stok')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

