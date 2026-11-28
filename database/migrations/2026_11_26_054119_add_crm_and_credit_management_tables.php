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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_member', 50)->unique();
            $table->string('nama', 150);
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->unsignedInteger('poin')->default(0);
            $table->decimal('limit_kredit', 15, 2)->default(0);
            $table->decimal('piutang_berjalan', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('customer_id')->nullable()->after('cash_shift_id')->constrained('customers')->nullOnDelete();
            $table->string('metode_pembayaran', 20)->default('cash')->after('kembali');
            $table->string('status_pembayaran', 20)->default('lunas')->after('metode_pembayaran');
            $table->decimal('sisa_piutang', 15, 2)->default(0)->after('status_pembayaran');
            $table->unsignedInteger('poin_didapat')->default(0)->after('sisa_piutang');
            $table->unsignedInteger('poin_dipakai')->default(0)->after('poin_didapat');
            $table->decimal('potongan_poin', 15, 2)->default(0)->after('poin_dipakai');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->date('jatuh_tempo')->nullable()->after('status_pembayaran');
            $table->decimal('dibayar', 15, 2)->default(0)->after('jatuh_tempo');
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->string('metode', 20)->default('cash');
            $table->string('catatan', 255)->nullable();
            $table->timestamp('waktu');
            $table->timestamps();
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->string('metode', 20)->default('cash');
            $table->string('catatan', 255)->nullable();
            $table->timestamp('waktu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('sale_payments');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['jatuh_tempo', 'dibayar']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'customer_id',
                'metode_pembayaran',
                'status_pembayaran',
                'sisa_piutang',
                'poin_didapat',
                'poin_dipakai',
                'potongan_poin',
            ]);
        });

        Schema::dropIfExists('customers');
    }
};
