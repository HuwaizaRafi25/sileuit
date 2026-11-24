<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lot', 30)->unique();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->date('tgl_panen')->nullable();
            $table->date('tgl_terima');
            $table->date('batas_simpan');
            $table->string('status', 15)->default('TERSEDIA');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE lots ADD CONSTRAINT chk_lot_status CHECK (status IN ('TERSEDIA','KARANTINA','KEDALUWARSA','HABIS'))");
        DB::statement("ALTER TABLE lots ADD CONSTRAINT chk_lot_grade CHECK (status = 'KARANTINA' OR grade_id IS NOT NULL)");

        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('jenis_mutasi', 20);
            $table->decimal('qty_kg', 12, 3);
            $table->string('ref_tipe', 20);
            $table->unsignedBigInteger('ref_id');
            $table->dateTime('waktu', 3);
            $table->foreignId('oleh')->constrained('users')->restrictOnDelete();
            $table->index(['lot_id', 'location_id', 'waktu']);
        });
        DB::statement("ALTER TABLE stock_ledgers ADD CONSTRAINT chk_ledger_jenis CHECK (jenis_mutasi IN ('TERIMA','TRANSFER_KELUAR','TRANSFER_MASUK','TERIMA_CABANG','KIRIM','JUAL','RETUR_MASUK','PENYESUAIAN','SALDO_AWAL'))");
        DB::statement("ALTER TABLE stock_ledgers ADD CONSTRAINT chk_ledger_ref CHECK (ref_tipe IN ('RECEIPT','TRANSFER','SHIPMENT','SALE','RETURN','ADJUSTMENT'))");
        DB::statement('ALTER TABLE stock_ledgers ADD CONSTRAINT chk_ledger_qty CHECK (qty_kg <> 0)');
        DB::unprepared("CREATE TRIGGER stock_ledgers_no_update BEFORE UPDATE ON stock_ledgers FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledgers append-only (IR-07)'");
        DB::unprepared("CREATE TRIGGER stock_ledgers_no_delete BEFORE DELETE ON stock_ledgers FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledgers append-only (IR-07)'");

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->foreignId('lot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->decimal('on_hand_kg', 12, 3)->default(0);
            $table->decimal('reserved_kg', 12, 3)->default(0);
            $table->timestamps();
            $table->primary(['lot_id', 'location_id']);
        });
        DB::statement('ALTER TABLE stock_balances ADD CONSTRAINT chk_bal_onhand CHECK (on_hand_kg >= 0)');
        DB::statement('ALTER TABLE stock_balances ADD CONSTRAINT chk_bal_reserved CHECK (reserved_kg >= 0 AND reserved_kg <= on_hand_kg)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('lots');
    }
};
