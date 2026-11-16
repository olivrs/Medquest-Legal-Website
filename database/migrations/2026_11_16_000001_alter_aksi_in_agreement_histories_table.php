<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agreement_histories')) {
            try {
                DB::statement("ALTER TABLE `agreement_histories` MODIFY `aksi` VARCHAR(50) NOT NULL DEFAULT 'CREATE'");
            } catch (\Throwable $e) {
                // Ignore if not supported
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agreement_histories')) {
            try {
                DB::statement("ALTER TABLE `agreement_histories` MODIFY `aksi` ENUM('CREATE', 'ADD_NOTE', 'EDIT') NOT NULL DEFAULT 'CREATE'");
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
