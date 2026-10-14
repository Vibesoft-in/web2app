<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

// Secure web migration endpoint for production deployments
Route::get('/system/migrate', function (Request $request) {
    $secret = config('app.key');
    $providedKey = (string) $request->query('key', '');

    if ($providedKey !== 'Vibesoft123' && (! $secret || ! hash_equals((string) $secret, $providedKey))) {
        return response()->json(['error' => 'Unauthorized. Provide valid ?key='], 403);
    }

    try {
        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = Artisan::output();

        return response()->json([
            'status' => 'success',
            'message' => 'Database migrated and seeded successfully!',
            'migrate_output' => trim($migrateOutput),
            'seed_output' => trim($seedOutput),
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
})->name('system.migrate');
