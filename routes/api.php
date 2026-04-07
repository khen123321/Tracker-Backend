<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No Token Needed)
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Token Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::get('/hr/interns',   [HrController::class, 'getInternList']);
    Route::get('/events',       [EventController::class, 'index']);
    Route::post('/events',      [EventController::class, 'store']);
    Route::get('/hr/all-users', [HrController::class, 'getAllUsers']);
    Route::post('/hr/sub-users', [HrController::class, 'storeSubUser']);
    Route::post('/hr/update-permissions/{id}', [HrController::class, 'updatePermissions']);

    // Attendance
    Route::post('/attendance/log', [AttendanceController::class, 'logAttendance']);
});

/*
|--------------------------------------------------------------------------
| SECRET REMOTE MIGRATION ROUTE (Temporary)
|--------------------------------------------------------------------------
*/
Route::get('/run-migration', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "<h1>Migration Success!</h1><pre>" . Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "<h1>Migration Failed</h1><p>" . $e->getMessage() . "</p>";
    }
});

/*
|--------------------------------------------------------------------------
| SECRET ADMIN CREATOR (Temporary)
|--------------------------------------------------------------------------
*/
Route::get('/create-admin', function () {
    try {
        // We use your USTP email here so the login matches your identity
        $adminEmail = 'testadmin123@gmail.com';
        $adminPassword = 'admin123'; 

        // Check if this specific user already exists
        $user = User::where('email', $adminEmail)->first();

        if (!$user) {
            User::create([
                'name'     => 'Khen Joshua Verson',
                'email'    => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role'     => 'admin', // Ensure your migration has a 'role' column
            ]);
            return "<h1>Success!</h1><p>Admin account created for <b>$adminEmail</b>. <br>Your password is: <b>$adminPassword</b></p>";
        }

        return "<h1>Note</h1><p>Admin account already exists for $adminEmail. No changes made.</p>";
    } catch (\Exception $e) {
        return "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
    }
});