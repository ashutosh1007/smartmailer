<?php

use Illuminate\Support\Facades\Route;
use SmartMailer\Http\Controllers\MailLogController;

Route::post('debug-bulk-retry', [MailLogController::class, 'bulkRetry'])->name('smartmailer.bulk-retry');
Route::prefix('smartmailer')->name('smartmailer.')->group(function () {
    Route::get('/', [MailLogController::class, 'index'])->name('dashboard');
    Route::get('/logs/{log}', [MailLogController::class, 'show'])->name('show');
    Route::post('/logs/{log}/retry', [MailLogController::class, 'retry'])->name('retry');
}); 