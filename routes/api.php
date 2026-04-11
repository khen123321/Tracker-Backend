<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\HR\DashboardController;
use Illuminate\Http\Request;
use Exception;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Dashboard Stats (Currently Public)
Route::get('/hr/dashboard-stats', [DashboardController::class, 'getStats']);

// Protected Routes (Requires Login)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    
    // Notifications Logic (Fixes the 404 Error)
    Route::get('/notifications', function (Request $request) {
        // Fetches notifications for the authenticated HR/Admin
        return $request->user()->notifications()->orderBy('created_at', 'desc')->get();
    });

    Route::post('/notifications/mark-as-read', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Notifications marked as read']);
    });

    // HR Management
    Route::get('/hr/interns',   [HrController::class, 'getInternList']);
    Route::get('/hr/all-users', [HrController::class, 'getAllUsers']);
    Route::post('/hr/sub-users', [HrController::class, 'storeSubUser']);
    Route::post('/hr/update-permissions/{id}', [HrController::class, 'updatePermissions']);
    
    // Attendance & Events
    Route::get('/events',       [EventController::class, 'index']);
    Route::post('/events',      [EventController::class, 'store']);
    Route::post('/attendance/log', [AttendanceController::class, 'logAttendance']);
    
    // Intern Form Submissions
    Route::post('/intern/forms/submit', [App\Http\Controllers\Api\FormRequestController::class, 'store']);
});

// Temporary Route for Admin Creation
Route::get('/create-admin', function () {
    try {
        $user = User::updateOrCreate(
            ['email' => 'testadmin123@gmail.com'],
            [
                'first_name' => 'Khen Joshua',
                'last_name'  => 'Verson',
                'password'   => Hash::make('testadmin123'),
                'role'       => 'superadmin', 
                'status'     => 'active',
            ]
        );
        return "<h1>Success!</h1><p>Superadmin account created for testadmin123@gmail.com.</p>";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
});