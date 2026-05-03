<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// =====================================================================
// TEMPORARY SECURE MIGRATION ROUTE
// TODO: Delete this route completely after your login works!
// =====================================================================
Route::get('/run-secret-migrations-2026', function () {
    try {
        // This forces the migration to run on your live Aiven database
        Artisan::call('migrate', ['--force' => true]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Database tables built successfully! You can now log in.',
            'output' => Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Migration failed: ' . $e->getMessage()
        ]);
    }
});