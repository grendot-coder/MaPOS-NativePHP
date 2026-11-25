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
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('no_referensi')->unique(); // Contoh: EXP-20261116-0001
            $table->string('kategori')->default('operasional'); // operasional, kasbon, gaji, lainnya
            $table->string('nama_karyawan')->nullable(); // Diisi jika kategori = kasbon / gaji
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Kasir/Admin yang mencatat
            $table->timestamp('waktu');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
