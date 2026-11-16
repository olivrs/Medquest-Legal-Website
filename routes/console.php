<?php

use App\Services\NotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('medquest:notify-deadlines', function () {
    $results = NotificationService::runAutoExpiryNotifications();
    $this->info('=== MEDQUEST EXPIRY NOTIFICATION CHECK (H-90 to H-3) ===');
    $this->info('Time: ' . date('Y-m-d H:i:s'));
    $this->info('New Emails Sent: ' . count($results));
    foreach ($results as $r) {
        $this->line(" - [{$r['tipe']}] -> {$r['recipient']} ({$r['kode']}) : {$r['status']}");
    }
})->purpose('Automatically check and send Medquest contract deadline reminder emails');

Schedule::command('medquest:notify-deadlines')->hourly();
