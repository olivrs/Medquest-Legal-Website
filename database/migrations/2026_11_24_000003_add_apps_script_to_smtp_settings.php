<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('smtp_settings') && !Schema::hasColumn('smtp_settings', 'apps_script_url')) {
            Schema::table('smtp_settings', function (Blueprint $table) {
                $table->string('apps_script_url', 500)->nullable()->after('sender_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('smtp_settings') && Schema::hasColumn('smtp_settings', 'apps_script_url')) {
            Schema::table('smtp_settings', function (Blueprint $table) {
                $table->dropColumn('apps_script_url');
            });
        }
    }
};
