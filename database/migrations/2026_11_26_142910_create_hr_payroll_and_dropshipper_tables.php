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
        Schema::create('jabatans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_jabatan', 20)->unique();
            $table->string('nama_jabatan', 100);
            $table->decimal('gaji_pokok_default', 15, 2)->default(0);
            $table->decimal('tunjangan_default', 15, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_karyawan', 30)->unique();
            $table->foreignUuid('jabatan_id')->constrained('jabatans')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama', 150);
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('no_slip', 50)->unique();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('periode_bulan', 7)->index();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('potongan_kasbon', 15, 2)->default(0);
            $table->decimal('potongan_lain', 15, 2)->default(0);
            $table->decimal('total_diterima', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->date('tanggal_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dropshippers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_dropshipper', 30)->unique();
            $table->string('nama', 150);
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->decimal('diskon_khusus', 5, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignUuid('employee_id')->nullable()->after('nama_karyawan')->constrained('employees')->nullOnDelete();
            $table->foreignUuid('payroll_id')->nullable()->after('employee_id')->constrained('payrolls')->nullOnDelete();
        });

        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->foreignUuid('employee_id')->nullable()->after('user_id')->constrained('employees')->nullOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_dropship')->default(false)->after('potongan_poin');
            $table->foreignUuid('dropshipper_id')->nullable()->after('is_dropship')->constrained('dropshippers')->nullOnDelete();
            $table->string('dropship_penerima_nama', 150)->nullable()->after('dropshipper_id');
            $table->string('dropship_penerima_telepon', 50)->nullable()->after('dropship_penerima_nama');
            $table->text('dropship_penerima_alamat')->nullable()->after('dropship_penerima_telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['dropshipper_id']);
            $table->dropColumn([
                'is_dropship',
                'dropshipper_id',
                'dropship_penerima_nama',
                'dropship_penerima_telepon',
                'dropship_penerima_alamat',
            ]);
        });

        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['employee_id', 'payroll_id']);
        });

        Schema::dropIfExists('dropshippers');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('jabatans');
    }
};
