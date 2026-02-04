<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Dashboard route
Route::get('/', [DashboardController::class, 'index']);
Route::get('/dashboard', [DashboardController::class, 'index']);

// Command sending route
Route::post('/send-command', [DashboardController::class, 'sendCommand'])
    ->name('send.command');

// Your API routes remain separate
require __DIR__.'/api.php';
