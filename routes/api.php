<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DeviceLogController;

Route::prefix('devices')->group(function () {
    Route::post('/register', [DeviceController::class, 'register']);
    Route::post('/heartbeat/{device}', [DeviceController::class, 'heartbeat']);
    Route::get('/', [DeviceController::class, 'index']);
    Route::delete('/{device}', [DeviceController::class, 'destroy']);

    // Device logging
    Route::post('/log', [DeviceLogController::class, 'store']);
    Route::post('/logs/batch', [DeviceLogController::class, 'storeBatch']);
    Route::get('/logs', [DeviceLogController::class, 'index']);
});

Route::prefix('commands')->group(function () {
    Route::post('/send-to-all', [CommandController::class, 'sendToAll']);
    Route::post('/send-to-device/{device}', [CommandController::class, 'sendToDevice']);
    Route::post('/send-to-group', [CommandController::class, 'sendToGroup']);
});
