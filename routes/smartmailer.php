<?php

use Illuminate\Support\Facades\Route;
use SmartMailer\Http\Controllers\MailLogController;

Route::post('/logs/bulk-retry', [MailLogController::class, 'bulkRetry'])->name('bulk-retry');
Route::get('/logs/{log}', [MailLogController::class, 'show'])->name('show');
Route::post('/logs/{log}/retry', [MailLogController::class, 'retry'])->name('retry');
Route::get('/', [MailLogController::class, 'index'])->name('dashboard'); 