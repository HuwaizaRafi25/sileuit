<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('kurir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('MENUNGGU');
            $table->decimal('jumlah_dikirim_kg', 12, 3);
            $table->decimal('jumlah_diterima_kg', 12, 3)->nullable();
            $table->decimal('kekurangan_kg', 12, 3)->nullable();
            $table->foreignId('bukti_attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
            $table->dateTime('waktu_kirim')->nullable();
            $table->dateTime('waktu_terima')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT chk_ship_status CHECK (status IN ('MENUNGGU','DIKIRIM','DITERIMA','GAGAL'))");
        DB::statement('ALTER TABLE shipments ADD CONSTRAINT chk_ship_kirim CHECK (jumlah_dikirim_kg > 0)');
        DB::statement('ALTER TABLE shipments ADD CONSTRAINT chk_ship_terima CHECK (jumlah_diterima_kg IS NULL OR jumlah_diterima_kg >= 0)');

        Schema::create('return_docs', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->string('ref_tipe', 10);
            $table->unsignedBigInteger('ref_id');
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('diminta_oleh')->constrained('users')->restrictOnDelete();
            $table->decimal('kg', 12, 3);
            $table->string('status', 12)->default('REQUESTED');
            $table->string('hasil', 15)->nullable();
            $table->text('alasan')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['ref_tipe', 'ref_id']);
        });
        DB::statement("ALTER TABLE return_docs ADD CONSTRAINT chk_ret_ref CHECK (ref_tipe IN ('SHIPMENT','SALE'))");
        DB::statement("ALTER TABLE return_docs ADD CONSTRAINT chk_ret_status CHECK (status IN ('REQUESTED','APPROVED','RECEIVED','REJECTED'))");
        DB::statement("ALTER TABLE return_docs ADD CONSTRAINT chk_ret_hasil CHECK (hasil IS NULL OR hasil IN ('STOK_KEMBALI','KARANTINA','DIHAPUS'))");
        DB::statement('ALTER TABLE return_docs ADD CONSTRAINT chk_ret_kg CHECK (kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('return_docs');
        Schema::dropIfExists('shipments');
    }
};
