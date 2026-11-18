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
        Schema::table('products', function (Blueprint $table) {
            $table->index('nama', 'products_nama_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('waktu', 'sales_waktu_index');
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->index('waktu', 'pengeluarans_waktu_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('created_at', 'stock_movements_created_at_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('tanggal', 'purchases_tanggal_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_nama_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_waktu_index');
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropIndex('pengeluarans_waktu_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_tanggal_index');
        });
    }
};
