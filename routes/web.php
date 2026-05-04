<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

// =====================================================================
// DEFAULT HOMEPAGE
// =====================================================================
Route::get('/', function () {
    return view('welcome');
});

// =====================================================================
// 1. THE "NUKE & PAVE" DATABASE ROUTE
// =====================================================================
Route::get('/run-secret-migrations-2026', function () {
    try {
        // 1. Wipe Laravel's memory so it reads fresh environment settings
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        // 2. Wipe the database and rebuild it perfectly with your migrations
        Artisan::call('migrate:fresh', ['--force' => true]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache cleared and tables built successfully!',
            'output' => Artisan::output()
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'CRITICAL ERROR: ' . $e->getMessage()
        ]);
    }
});

// =====================================================================
// 2. SECRET LINK TO CLEAR TEST USERS
// =====================================================================
Route::get('/secret-clear-users-2026', function () {
    try {
        // This targets everyone except the superadmin and soft-deletes them
        // Note: Change 'superadmin' if your admin role is named differently (e.g., 'admin')
        $deletedCount = User::where('role', '!=', 'superadmin')->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Successfully moved {$deletedCount} test users to the recycle bin!"
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'ERROR: ' . $e->getMessage()
        ]);
    }
});
