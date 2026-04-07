<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
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
    Route::post('/attendance/log', [AttendanceController::class, 'logAttendance']);
});

/*
|--------------------------------------------------------------------------
| SECRET SUPERADMIN CREATOR
|--------------------------------------------------------------------------
*/
Route::get('/create-admin', function () {
    try {
        $email = 'testadmin123@gmail.com';
        $password = 'testadmin123';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Khen Joshua',
                'last_name'  => 'Verson',
                'password'   => Hash::make($password),
                'role'       => 'superadmin', 
                'status'     => 'active',
            ]
        );

        return "<h1>Success!</h1><p>Superadmin account created for <b>$email</b>.</p><p>Password is: <b>$password</b></p>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});