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
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('waktu_buka');
            $table->timestamp('waktu_tutup')->nullable();
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo_akhir_sistem', 15, 2)->default(0);
            $table->decimal('saldo_akhir_fisik', 15, 2)->nullable();
            $table->decimal('selisih', 15, 2)->default(0);
            $table->string('status')->default('open'); // 'open', 'closed'
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('cash_shift_id')->nullable()->after('kasir_id')->constrained('cash_shifts')->nullOnDelete();
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignUuid('cash_shift_id')->nullable()->after('user_id')->constrained('cash_shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::dropIfExists('cash_shifts');
    }
};
