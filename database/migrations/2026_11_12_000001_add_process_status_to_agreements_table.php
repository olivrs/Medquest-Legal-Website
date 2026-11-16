<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agreements')) {
            Schema::table('agreements', function (Blueprint $table) {
                if (!Schema::hasColumn('agreements', 'status_proses')) {
                    $table->string('status_proses', 30)->default('PENGAJUAN')->index()->after('doc_type');
                }
                if (!Schema::hasColumn('agreements', 'tanggal_selesai')) {
                    $table->date('tanggal_selesai')->nullable()->index()->after('status_proses');
                }
            });
        }

        if (Schema::hasTable('agreement_histories')) {
            try {
                DB::statement("ALTER TABLE agreement_histories MODIFY COLUMN aksi VARCHAR(50) NOT NULL DEFAULT 'CREATE'");
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agreements')) {
            Schema::table('agreements', function (Blueprint $table) {
                if (Schema::hasColumn('agreements', 'tanggal_selesai')) {
                    $table->dropColumn('tanggal_selesai');
                }
                if (Schema::hasColumn('agreements', 'status_proses')) {
                    $table->dropColumn('status_proses');
                }
            });
        }
    }
};

