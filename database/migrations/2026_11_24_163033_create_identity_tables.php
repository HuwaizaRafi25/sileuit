<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 80);
            $table->string('tipe', 10);
            $table->string('alamat', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE locations ADD CONSTRAINT chk_locations_tipe CHECK (tipe IN ('GUDANG','CABANG'))");

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 50);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('hp', 500)->nullable()->after('email');
            $table->string('hp_hash', 64)->nullable()->unique()->after('hp');
            $table->string('pin_hash')->nullable()->after('hp_hash');
            $table->string('status', 10)->default('AKTIF')->after('password');
            $table->foreignId('active_location_id')->nullable()->after('status')->constrained('locations')->nullOnDelete();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('user_location_scopes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_location_scopes');
        Schema::dropIfExists('role_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_location_id');
            $table->dropColumn(['hp', 'hp_hash', 'pin_hash', 'status']);
        });
        Schema::dropIfExists('roles');
        Schema::dropIfExists('locations');
    }
};
