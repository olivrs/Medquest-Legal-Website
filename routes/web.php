<?php

use App\Http\Controllers\MedquestController;
use Illuminate\Support\Facades\Route;

// Route utama yang mendukung query ?page=... sekaligus akses langsung
Route::match(['get', 'post'], '/', [MedquestController::class, 'handleRoot'])->name('home');

// Route bersih standar Laravel 13
Route::match(['get', 'post'], '/login', [MedquestController::class, 'loginPage'])->name('login');
Route::match(['get', 'post'], '/register', [MedquestController::class, 'registerPage'])->name('register');
Route::get('/register-pending', [MedquestController::class, 'registerPendingPage'])->name('register.pending');
Route::match(['get', 'post'], '/logout', [MedquestController::class, 'logout'])->name('logout');

Route::match(['get', 'post'], '/dashboard', [MedquestController::class, 'agreementsPage'])->name('dashboard');
Route::match(['get', 'post'], '/agreements', [MedquestController::class, 'agreementsPage'])->name('agreements.index');
Route::match(['get', 'post'], '/create', [MedquestController::class, 'createPage'])->name('agreements.create');
Route::match(['get', 'post'], '/edit/{routeId?}', [MedquestController::class, 'editPage'])->name('agreements.edit');
Route::match(['get', 'post'], '/detail/{routeId?}', [MedquestController::class, 'detailPage'])->name('agreements.detail');

Route::match(['get', 'post'], '/notifications', [MedquestController::class, 'notificationsPage'])->name('notifications.index');
Route::match(['get', 'post'], '/smtp-settings', [MedquestController::class, 'smtpSettingsPage'])->name('smtp.settings');
Route::match(['get', 'post'], '/user-approvals', [MedquestController::class, 'userApprovalsPage'])->name('user.approvals');

// Endpoint worker pengecekan otomatis H-90, H-60, H-30, H-14, H-7, H-3
Route::match(['get', 'post'], '/cron_notify.php', [MedquestController::class, 'cronNotify'])->name('cron.legacy');
Route::match(['get', 'post'], '/cron-notify', [MedquestController::class, 'cronNotify'])->name('cron.notify');
