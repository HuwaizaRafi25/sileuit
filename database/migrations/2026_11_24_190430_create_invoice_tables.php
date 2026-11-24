<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('no', 30)->unique();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->date('tgl_invoice');
            $table->date('jatuh_tempo');
            $table->unsignedBigInteger('total');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->boolean('disetujui')->default(true);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT chk_inv_total CHECK (total > 0)');

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_kg', 12, 3);
            $table->unsignedBigInteger('harga_per_kg');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE invoice_lines ADD CONSTRAINT chk_invl_qty CHECK (qty_kg > 0)');

        // IR-14 lengkap: tambahkan FK invoice_id di payment_allocations (Fase 2 ditunda).
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
