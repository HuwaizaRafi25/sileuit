<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lot saldo awal cutover tidak punya receipt (J.3.2, DEV-018).
        DB::statement('ALTER TABLE lots MODIFY receipt_id BIGINT UNSIGNED NULL');

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->foreignId('dari_lokasi')->constrained('locations')->restrictOnDelete();
            $table->foreignId('ke_lokasi')->constrained('locations')->restrictOnDelete();
            $table->string('status', 15)->default('DRAFT');
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('waktu_kirim')->nullable();
            $table->dateTime('waktu_terima')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE stock_transfers ADD CONSTRAINT chk_trf_status CHECK (status IN ('DRAFT','SHIPPED','RECEIVED','DISCREPANCY','CANCELLED'))");
        DB::statement('ALTER TABLE stock_transfers ADD CONSTRAINT chk_trf_lokasi CHECK (dari_lokasi <> ke_lokasi)');

        Schema::create('transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->decimal('kg_kirim', 12, 3);
            $table->decimal('kg_terima', 12, 3)->nullable();
            $table->decimal('selisih_kg', 12, 3)->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE transfer_lines ADD CONSTRAINT chk_trfl_kirim CHECK (kg_kirim > 0)');
        DB::statement('ALTER TABLE transfer_lines ADD CONSTRAINT chk_trfl_terima CHECK (kg_terima IS NULL OR kg_terima >= 0)');

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->string('tipe', 15)->default('PENYESUAIAN');
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('commodity_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('qty_kg', 12, 3);
            $table->date('tanggal');
            $table->text('alasan');
            $table->string('status', 15)->default('MENUNGGU');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT chk_adj_tipe CHECK (tipe IN ('PENYESUAIAN','SALDO_AWAL'))");
        DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT chk_adj_status CHECK (status IN ('MENUNGGU','DISETUJUI','DITOLAK'))");
        DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_adj_qty CHECK (qty_kg <> 0)');
        DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT chk_adj_target CHECK (lot_id IS NOT NULL OR (tipe = 'SALDO_AWAL' AND commodity_id IS NOT NULL AND grade_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
