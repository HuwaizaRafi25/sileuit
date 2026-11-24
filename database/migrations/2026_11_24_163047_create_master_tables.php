<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 10);
            $table->string('nama', 100);
            $table->string('hp', 500);
            $table->string('hp_hash', 64)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kelompok', 80)->nullable();
            $table->unsignedBigInteger('batas_kredit')->default(0);
            $table->unsignedSmallInteger('termin_hari')->nullable();
            $table->text('no_rekening')->nullable();
            $table->string('status', 10)->default('AKTIF');
            $table->timestamps();
            $table->unique(['tipe', 'hp_hash']);
        });
        DB::statement("ALTER TABLE partners ADD CONSTRAINT chk_partners_tipe CHECK (tipe IN ('FARMER','RETAIL','CUSTOMER'))");
        DB::statement('ALTER TABLE partners ADD CONSTRAINT chk_partners_termin CHECK (termin_hari IS NULL OR termin_hari IN (7,14,30))');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('active_location_id')->constrained('partners')->nullOnDelete();
        });

        Schema::create('farm_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('nama_desa', 80);
            $table->decimal('luas_ha', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->string('satuan_dasar', 5)->default('kg');
            $table->unsignedInteger('umur_simpan_hari')->default(180);
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->string('kode', 5);
            $table->json('ambang_mutu')->nullable();
            $table->timestamps();
            $table->unique(['commodity_id', 'kode']);
        });

        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 30)->unique();
            $table->decimal('faktor_ke_kg', 8, 3);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE uom_conversions ADD CONSTRAINT chk_uom_faktor CHECK (faktor_ke_kg > 0)');

        Schema::create('standard_packages', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 80);
            $table->string('jenis_pupuk', 80)->nullable();
            $table->string('jendela_tanam', 80)->nullable();
            $table->json('parameter_mutu')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standard_package_id')->constrained()->restrictOnDelete();
            $table->string('musim', 20);
            $table->date('mulai');
            $table->date('selesai');
            $table->decimal('estimasi_kg', 12, 3);
            $table->string('status', 10)->default('DRAFT');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE contracts ADD CONSTRAINT chk_contracts_status CHECK (status IN ('DRAFT','AKTIF','SELESAI','BATAL'))");
        DB::statement('ALTER TABLE contracts ADD CONSTRAINT chk_contracts_tanggal CHECK (selesai >= mulai)');

        Schema::create('harvest_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->date('tgl_perkiraan');
            $table->decimal('kg_perkiraan', 12, 3);
            $table->string('status', 15)->default('RENCANA');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE harvest_plans ADD CONSTRAINT chk_hp_status CHECK (status IN ('RENCANA','TERPENUHI','BATAL'))");
        DB::statement('ALTER TABLE harvest_plans ADD CONSTRAINT chk_hp_kg CHECK (kg_perkiraan > 0)');

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('harga_per_kg');
            $table->date('berlaku_dari');
            $table->date('berlaku_sampai')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('disetujui_pada')->nullable();
            $table->timestamps();
            $table->index(['commodity_id', 'grade_id', 'berlaku_dari']);
        });
        DB::statement('ALTER TABLE price_list_items ADD CONSTRAINT chk_harga_positif CHECK (harga_per_kg > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('harvest_plans');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('standard_packages');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('commodities');
        Schema::dropIfExists('farm_plots');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('partner_id'));
        Schema::dropIfExists('partners');
    }
};
