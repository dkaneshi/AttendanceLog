<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected attendance API routes
Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    // Clock actions
    Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/start-lunch', [AttendanceController::class, 'startLunch']);
    Route::post('/end-lunch', [AttendanceController::class, 'endLunch']);
    Route::post('/clock-out', [AttendanceController::class, 'clockOut']);

    // Vacation and sick hours logging
    Route::post('/log-vacation', [AttendanceController::class, 'logVacation']);
    Route::post('/log-sick', [AttendanceController::class, 'logSick']);

    // Status and information
    Route::get('/status', [AttendanceController::class, 'status']);
    Route::get('/history', [AttendanceController::class, 'history']);

    // Update attendance entry
    Route::put('/{attendanceLog}', [AttendanceController::class, 'update']);
    Route::put('/{attendanceLog}/vacation', [AttendanceController::class, 'updateVacation']);
    Route::put('/{attendanceLog}/sick', [AttendanceController::class, 'updateSick']);
});
