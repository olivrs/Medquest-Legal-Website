<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('kode_dept', 30)->unique();
                $table->string('nama_dept', 120);
                $table->string('deskripsi', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('agreements')) {
            Schema::create('agreements', function (Blueprint $table) {
                $table->id();
                $table->string('doc_type', 30)->default('COOPERATION');
                $table->integer('no_urut_dokumen')->nullable()->comment('Nomor urut form, contoh 731, 732');
                $table->string('department', 30)->index();
                $table->text('document_title')->nullable();
                $table->text('document_number');
                $table->string('issued_by', 255)->nullable();
                $table->string('principal_name', 255)->nullable();
                $table->string('customer', 255);
                $table->text('product_description');
                $table->string('serial_number', 150)->default('N/A');
                $table->date('effective_date')->nullable();
                $table->boolean('effective_date_na')->default(false);
                $table->date('expiry_date')->nullable()->index();
                $table->boolean('expiry_date_na')->default(false);
                $table->text('note')->nullable();
                $table->string('file_attachment', 255)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->index();
                $table->string('created_by_code', 20)->comment('Singkatan pembuat data, misal FJR');
                $table->text('created_by_log')->comment('Riwayat tanda input, misal: FJR, 21 OCT 2026');
                foreach ([90, 60, 30, 14, 7, 3, 1] as $h) {
                    $table->dateTime("notif_h{$h}_sent_at")->nullable();
                }
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('agreement_histories')) {
            Schema::create('agreement_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id');
                $table->string('kode_singkatan', 20);
                $table->date('tanggal_input');
                $table->text('catatan')->nullable();
                $table->enum('aksi', ['CREATE', 'ADD_NOTE', 'EDIT'])->default('CREATE');
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('email_notifications')) {
            Schema::create('email_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id');
                $table->string('recipient_email', 150);
                $table->string('recipient_code', 20);
                $table->string('tipe_notif', 20);
                $table->integer('days_remaining');
                $table->string('subject', 255);
                $table->longText('body_html');
                $table->string('status_kirim', 50)->default('SENT');
                $table->text('keterangan_status')->nullable();
                $table->boolean('is_read')->default(false);
                $table->dateTime('sent_at')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('smtp_settings')) {
            Schema::create('smtp_settings', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->boolean('smtp_enabled')->default(false);
                $table->string('smtp_host', 150)->default('smtp.gmail.com');
                $table->integer('smtp_port')->default(587);
                $table->string('smtp_encryption', 20)->default('tls');
                $table->string('smtp_username', 150)->nullable()->default('');
                $table->text('smtp_password')->nullable()->comment('Disimpan di database pengaturan SMTP');
                $table->string('sender_name', 150)->default('Medquest Agreement Reminder');
                $table->string('sender_email', 150)->default('noreply@medquest.co.id');
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
        Schema::dropIfExists('agreement_histories');
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('smtp_settings');
    }
};
