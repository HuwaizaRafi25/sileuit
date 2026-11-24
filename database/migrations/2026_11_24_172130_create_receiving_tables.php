<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('disk', 20)->default('s3');
            $table->string('mime', 60)->nullable();
            $table->unsignedInteger('ukuran_byte')->nullable();
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('harvest_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('bruto_kg', 12, 3)->nullable();
            $table->decimal('tara_kg', 12, 3)->nullable();
            $table->decimal('netto_kg', 12, 3)->nullable();
            $table->unsignedBigInteger('harga_per_kg')->nullable();
            $table->unsignedBigInteger('nilai_bayar')->nullable();
            $table->string('status', 25)->default('DRAFT');
            $table->decimal('selisih_persen', 6, 2)->nullable();
            $table->text('selisih_alasan')->nullable();
            $table->foreignId('selisih_disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterima_oleh')->constrained('users')->restrictOnDelete();
            $table->dateTime('waktu_timbang');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->index(['location_id', 'status']);
        });
        DB::statement("ALTER TABLE receipts ADD CONSTRAINT chk_receipt_status CHECK (status IN ('DRAFT','DIAJUKAN','INSPECTED','MENUNGGU_PERSETUJUAN','CONFIRMED','PAID','REJECTED'))");
        DB::statement('ALTER TABLE receipts ADD CONSTRAINT chk_receipt_timbang CHECK (bruto_kg IS NULL OR tara_kg IS NULL OR bruto_kg >= tara_kg)');
        DB::statement('ALTER TABLE receipts ADD CONSTRAINT chk_receipt_netto CHECK (netto_kg IS NULL OR bruto_kg IS NULL OR tara_kg IS NULL OR netto_kg = bruto_kg - tara_kg)');

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->decimal('kadar_air', 4, 1);
            $table->decimal('kadar_hampa', 4, 1);
            $table->decimal('butir_patah', 4, 1)->nullable();
            $table->string('varietas', 60);
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->string('keputusan', 12);
            $table->text('alasan')->nullable();
            $table->foreignId('foto_attachment_id')->constrained('attachments')->restrictOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE inspections ADD CONSTRAINT chk_insp_keputusan CHECK (keputusan IN ('TERIMA','KARANTINA','TOLAK'))");
        DB::statement("ALTER TABLE inspections ADD CONSTRAINT chk_insp_alasan CHECK (keputusan = 'TERIMA' OR (alasan IS NOT NULL AND alasan <> ''))");

        // IR-08 / BR-13: isi dokumen final tidak boleh diubah; transisi status tetap boleh.
        DB::unprepared("
            CREATE TRIGGER receipts_lock_final BEFORE UPDATE ON receipts FOR EACH ROW
            BEGIN
                IF OLD.status IN ('CONFIRMED','PAID','REJECTED') AND (
                    NOT (NEW.bruto_kg <=> OLD.bruto_kg) OR NOT (NEW.tara_kg <=> OLD.tara_kg)
                    OR NOT (NEW.netto_kg <=> OLD.netto_kg) OR NOT (NEW.contract_id <=> OLD.contract_id)
                    OR NOT (NEW.location_id <=> OLD.location_id) OR NOT (NEW.harga_per_kg <=> OLD.harga_per_kg)
                    OR NOT (NEW.nilai_bayar <=> OLD.nilai_bayar)
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Dokumen final terkunci (BR-13)';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('attachments');
    }
};
