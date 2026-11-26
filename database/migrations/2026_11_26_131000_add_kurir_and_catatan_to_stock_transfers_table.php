<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('kurir_id')->nullable()->after('ke_lokasi')->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable()->after('waktu_terima');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kurir_id');
            $table->dropColumn('catatan');
        });
    }
};
