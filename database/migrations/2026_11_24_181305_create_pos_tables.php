<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DEV-023: POS walk-in tidak punya mitra terdaftar.
        DB::statement('ALTER TABLE payments MODIFY partner_id BIGINT UNSIGNED NULL');

        Schema::create('het_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('harga_per_kg');
            $table->string('mode', 12)->default('BLOKIR');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE het_configs ADD CONSTRAINT chk_het_mode CHECK (mode IN ('PERINGATAN','BLOKIR'))");
        DB::statement('ALTER TABLE het_configs ADD CONSTRAINT chk_het_harga CHECK (harga_per_kg > 0)');

        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota', 30)->unique();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('total');
            $table->string('metode', 10)->default('TUNAI');
            $table->boolean('peringatan_het')->default(false);
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE pos_sales ADD CONSTRAINT chk_pos_metode CHECK (metode IN ('TUNAI','TRANSFER','GATEWAY'))");

        Schema::create('pos_sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_kg', 12, 3);
            $table->unsignedBigInteger('harga_per_kg');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE pos_sale_lines ADD CONSTRAINT chk_posl_qty CHECK (qty_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_lines');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('het_configs');
    }
};
