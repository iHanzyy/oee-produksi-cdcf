<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProductionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:operator', 'device'])->group(function () {
        Route::get('/dashboard', [ProductionController::class, 'index'])->name('dashboard');
        Route::post('/production-sessions', [ProductionController::class, 'startSession'])->name('machine.start');
        Route::get('/production-sessions/status', [ProductionController::class, 'status'])->name('production.status');
        Route::post('/production-sessions/{productionSession}/takeover', [ProductionController::class, 'takeover'])->name('production.takeover');
        Route::post('/production-sessions/{productionSession}/stop', [ProductionController::class, 'stopMachine'])->name('machine.stop');
        Route::post('/production-sessions/{productionSession}/resume', [ProductionController::class, 'resumeMachine'])->name('machine.resume');
        Route::post('/production-sessions/{productionSession}/complete', [ProductionController::class, 'rejectLegacyCompletion']);
        Route::post('/production-sessions/{productionSession}/complete-confirmed', [ProductionController::class, 'endSession'])->name('machine.end');
        Route::post('/production-sessions/{productionSession}/acknowledge', [ProductionController::class, 'acknowledge'])->name('production.acknowledge');
    });

    Route::middleware('role:spv,manager')->group(function () {
        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/active-sessions', [HistoryController::class, 'activeSessions'])->name('history.active_sessions');
        Route::get('/history/export-excel', [HistoryController::class, 'exportExcel'])->name('history.export');
    });
});

require __DIR__.'/auth.php';
