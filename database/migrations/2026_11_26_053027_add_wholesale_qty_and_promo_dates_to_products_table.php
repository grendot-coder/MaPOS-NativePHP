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
            $table->unsignedInteger('min_qty_grosir_1')->nullable()->after('harga_grosir_1');
            $table->unsignedInteger('min_qty_grosir_2')->nullable()->after('harga_grosir_2');
            $table->unsignedInteger('min_qty_grosir_3')->nullable()->after('harga_grosir_3');
            $table->date('promo_mulai')->nullable()->after('diskon');
            $table->date('promo_sampai')->nullable()->after('promo_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'min_qty_grosir_1',
                'min_qty_grosir_2',
                'min_qty_grosir_3',
                'promo_mulai',
                'promo_sampai',
            ]);
        });
    }
};
