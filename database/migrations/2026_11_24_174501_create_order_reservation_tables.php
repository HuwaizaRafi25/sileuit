<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_dok', 30)->unique();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('lokasi_tujuan_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status', 15)->default('NEW');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE sales_orders ADD CONSTRAINT chk_so_status CHECK (status IN ('NEW','ALLOCATED','PICKED','SHIPPED','DELIVERED','CLOSED','PARTIAL','BACKORDER','CANCELLED'))");

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_kg', 12, 3);
            $table->decimal('qty_terpenuhi', 12, 3)->default(0);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE sales_order_lines ADD CONSTRAINT chk_sol_qty CHECK (qty_kg > 0)');
        DB::statement('ALTER TABLE sales_order_lines ADD CONSTRAINT chk_sol_penuhi CHECK (qty_terpenuhi >= 0 AND qty_terpenuhi <= qty_kg)');

        // ENT-17 + ENT-20: Reservation = Allocation, digabung 1:1 sesuai R-06.
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_kg', 12, 3);
            $table->string('status', 15)->default('AKTIF');
            $table->boolean('override_kedaluwarsa')->default(false);
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE reservations ADD CONSTRAINT chk_res_status CHECK (status IN ('AKTIF','TERPENUHI','DIBATALKAN'))");
        DB::statement('ALTER TABLE reservations ADD CONSTRAINT chk_res_qty CHECK (qty_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
