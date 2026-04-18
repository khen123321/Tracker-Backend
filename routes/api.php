<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;               // 👈 IMPORT ADDED FOR PUBLIC ROUTES
use App\Models\RequirementSetting;   // 👈 IMPORT ADDED FOR PUBLIC ROUTES

// Include ALL Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\InternDashboardController;
use App\Http\Controllers\Api\FormRequestController;
use App\Http\Controllers\HR\DashboardController;
use App\Http\Controllers\Api\SettingsController;
use Exception;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// Public Routes
// ==========================================
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// HR Public Stats
Route::get('/hr/dashboard-stats', [DashboardController::class, 'getStats']);

// 👇 NEW: PUBLIC ROUTES FOR SIGN-UP PAGE DROPDOWNS 👇
Route::get('/public/schools', function() {
    // Return all schools in alphabetical order
    return response()->json(School::orderBy('name', 'asc')->get());
});

Route::get('/public/courses/{school_id}', function($school_id) {
    // Return only the distinct courses that this specific school offers based on HR settings
    $courses = RequirementSetting::where('school_id', $school_id)
                    ->select('course_name')
                    ->distinct()
                    ->get();
    return response()->json($courses);
});


// ==========================================
// Protected Routes (Requires Login Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth ---
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    
    // --- Attendance System ---
    Route::post('/attendance/log', [AttendanceController::class, 'logAttendance']);
    Route::get('/attendance/history', [AttendanceController::class, 'getHistory']);

    // --- Notifications ---
    Route::get('/notifications', function (Request $request) {
        return $request->user()->notifications()->orderBy('created_at', 'desc')->get();
    });
    
    Route::post('/notifications/mark-as-read', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Notifications marked as read']);
    });

    // --- HR Management ---
    Route::get('/hr/interns',    [HrController::class, 'getInternList']);
    Route::get('/hr/all-users',  [HrController::class, 'getAllUsers']);
    Route::post('/hr/sub-users', [HrController::class, 'storeSubUser']);
    Route::post('/hr/update-permissions/{id}', [HrController::class, 'updatePermissions']);
    Route::get('/hr/users-roles', [HrController::class, 'getRoleUsers']);
    Route::put('/hr/users-roles/{id}', [HrController::class, 'updateUserAccess']);
    Route::post('/hr/users', [HrController::class, 'storeSubUser']);
    
    // --- Events ---
    Route::get('/events',   [EventController::class, 'index']);
    Route::post('/events',  [EventController::class, 'store']);
    Route::get('/hr/interns/{id}/attendance', [AttendanceController::class, 'getInternAttendance']);
    Route::get('/hr/interns/{id}/attendance', [App\Http\Controllers\Api\AttendanceController::class, 'getInternAttendanceForHR']);
    Route::get('/hr/attendance/verification', [App\Http\Controllers\Api\AttendanceController::class, 'getVerificationLogs']);
    Route::post('/hr/attendance/{id}/verify', [App\Http\Controllers\Api\AttendanceController::class, 'verifyLog']);
    
    // --- Intern Dashboard & Forms ---
    Route::get('/intern/dashboard-stats', [InternDashboardController::class, 'getStats']);
    Route::post('/intern/forms/submit',   [FormRequestController::class, 'store']);
    Route::get('/event-filters', [EventController::class, 'getFilters']);

    // --- HR Settings Routes ---
    Route::get('/hr/settings/requirements', [SettingsController::class, 'getRequirements']);
    Route::post('/hr/settings/requirements', [SettingsController::class, 'storeRequirement']);
    Route::delete('/hr/settings/requirements/{id}', [SettingsController::class, 'deleteRequirement']);
    Route::get('/hr/settings/schools', [SettingsController::class, 'getSchools']);

});

// ==========================================
// Setup Utility
// ==========================================
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