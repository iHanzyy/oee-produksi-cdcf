<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\HistoryController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProductionController::class, 'index'])->name('dashboard');
    
    // Rute OEE Controller Lengkap
    Route::post('/machine/start', [ProductionController::class, 'startSession'])->name('machine.start');
    Route::post('/machine/stop', [ProductionController::class, 'stopMachine'])->name('machine.stop');
    Route::post('/machine/resume', [ProductionController::class, 'resumeMachine'])->name('machine.resume');
    Route::post('/machine/end', [ProductionController::class, 'endSession'])->name('machine.end');

    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/export-excel', [HistoryController::class, 'exportExcel'])->name('history.export');
});

require __DIR__.'/auth.php';