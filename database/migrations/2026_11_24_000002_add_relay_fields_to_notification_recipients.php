<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_recipients')) {
            Schema::table('notification_recipients', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_recipients', 'formsubmit_token')) {
                    $table->string('formsubmit_token', 191)->nullable()->after('department');
                }
                if (!Schema::hasColumn('notification_recipients', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('notification_recipients', 'last_error')) {
                    $table->string('last_error', 255)->nullable()->after('is_verified');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_recipients')) {
            Schema::table('notification_recipients', function (Blueprint $table) {
                foreach (['formsubmit_token', 'is_verified', 'last_error'] as $col) {
                    if (Schema::hasColumn('notification_recipients', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
