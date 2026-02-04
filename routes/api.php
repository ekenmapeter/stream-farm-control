<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\CommandController;

Route::prefix('devices')->group(function () {
    Route::post('/register', [DeviceController::class, 'register']);
    Route::post('/heartbeat/{device}', [DeviceController::class, 'heartbeat']);
    Route::get('/', [DeviceController::class, 'index']);
    Route::delete('/{device}', [DeviceController::class, 'destroy']);
});

Route::prefix('commands')->group(function () {
    Route::post('/send-to-all', [CommandController::class, 'sendToAll']);
    Route::post('/send-to-device/{device}', [CommandController::class, 'sendToDevice']);
    Route::post('/send-to-group', [CommandController::class, 'sendToGroup']);
});
