<?php

use App\Services\NotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('medquest:notify-deadlines', function () {
    $results = NotificationService::runAutoExpiryNotifications();
    $this->info('=== MEDQUEST EXPIRY NOTIFICATION CHECK (H-90 s/d H-3) ===');
    $this->info('Waktu: ' . date('Y-m-d H:i:s'));
    $this->info('Total Email Dikirim Baru: ' . count($results));
    foreach ($results as $r) {
        $this->line(" - [{$r['tipe']}] -> {$r['recipient']} ({$r['kode']}) : {$r['status']}");
    }
})->purpose('Cek dan kirim email pengingat deadline kontrak Medquest secara otomatis');

Schedule::command('medquest:notify-deadlines')->hourly();
