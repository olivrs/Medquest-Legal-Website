<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agreements') && !Schema::hasColumn('agreements', 'notif_h0_sent_at')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->dateTime('notif_h0_sent_at')->nullable()->after('notif_h1_sent_at');
            });
        }

        if (!Schema::hasTable('notification_recipients')) {
            Schema::create('notification_recipients', function (Blueprint $table) {
                $table->id();
                $table->string('email', 150);
                $table->string('label', 120)->nullable();
                $table->string('department', 30)->default('ALL');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('added_by_user_id')->nullable();
                $table->timestamps();
                $table->unique(['email', 'department']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
        if (Schema::hasTable('agreements') && Schema::hasColumn('agreements', 'notif_h0_sent_at')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->dropColumn('notif_h0_sent_at');
            });
        }
    }
};
