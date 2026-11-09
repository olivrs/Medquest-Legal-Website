<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Users Medquest (wajib Email Pribadi & Kode Singkatan Nama, contoh: FJR)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('nama_lengkap', 120);
                $table->string('kode_singkatan', 20)->unique()->comment('Singkatan nama Medquest, contoh: FJR');
                $table->string('email', 150)->unique()->comment('Email pribadi untuk notifikasi deadline');
                $table->string('password');
                $table->string('departemen_default', 30)->default('ALL');
                $table->text('allowed_departments')->nullable()->comment('Daftar kode departemen yang diizinkan untuk User (dipisah koma)');
                $table->enum('role', ['superadmin', 'admin', 'user'])->default('user');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'allowed_departments')) {
                    $table->text('allowed_departments')->nullable();
                }
                if (!Schema::hasColumn('users', 'remember_token')) {
                    $table->rememberToken();
                }
                if (!Schema::hasColumn('users', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
