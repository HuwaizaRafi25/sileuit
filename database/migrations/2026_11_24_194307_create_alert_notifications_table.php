<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 30);
            $table->string('judul', 120);
            $table->text('isi');
            $table->string('entitas', 60)->nullable();
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('untuk_peran', 30)->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('dibaca_pada')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE alert_notifications ADD CONSTRAINT chk_notif_tipe CHECK (tipe IN ('UMUR_SIMPAN','INVOICE_OVERDUE','TRANSFER_SELIH','UMUM'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
    }
};
