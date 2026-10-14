<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Production & container health probe endpoint
Route::get('/healthz', [HealthController::class, 'check'])->name('healthz');

Route::middleware('web')->group(function () {
    Route::get('/admin/impersonate/leave', [ImpersonationController::class, 'leave'])
        ->name('impersonate.leave');
});
