<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('arah', 8);
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->string('metode', 10);
            $table->unsignedBigInteger('jumlah');
            $table->string('referensi', 80)->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_pay_arah CHECK (arah IN ('MASUK','KELUAR'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_pay_metode CHECK (metode IN ('TUNAI','TRANSFER','GATEWAY'))");
        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_pay_jumlah CHECK (jumlah > 0)');

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreignId('receipt_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('jumlah_alokasi');
            $table->timestamps();
            // IR-15: UNIQUE pada kolom nullable = padanan partial unique index (MySQL mengizinkan banyak NULL).
            $table->unique('receipt_id');
        });
        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT chk_alloc_target CHECK ((invoice_id IS NOT NULL) + (receipt_id IS NOT NULL) + (sale_id IS NOT NULL) = 1)');
        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT chk_alloc_jumlah CHECK (jumlah_alokasi > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
