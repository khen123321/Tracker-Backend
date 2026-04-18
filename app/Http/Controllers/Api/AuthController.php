<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Intern;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // 👇 MUST IMPORT DB FACADE FOR TRANSACTIONS

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|string' 
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        // --- ROLE VALIDATION ---
        if ($request->role === 'hr') {
            if ($user->role !== 'hr' && $user->role !== 'hr_intern' && $user->role !== 'superadmin') {
                return response()->json(['message' => 'Access denied. Privileged account required.'], 403);
            }
        } 
        else if ($request->role === 'intern') {
            if ($user->role !== 'intern') {
                return response()->json(['message' => 'Access denied. This is not an Intern account.'], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // 🛡️ SAFETY NET: Ensure permissions is always an array, never null!
        $user->permissions = $user->permissions ?? [];

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'role' => $user->role
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 👇 Use a Database Transaction. If Intern creation fails, User creation rolls back!
        DB::beginTransaction();

        try {
            // 1. Create the base User account (ONLY COLUMNS THAT EXIST IN `users` TABLE)
            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'intern',
                'status' => 'active',
                'permissions' => [], 
            ]);

            // 👇 NEW AUTOMATION LOGIC: Auto-fetch required hours based on School and Course 👇
            $actualCourse = $request->course_program ?? $request->course ?? 'N/A';
            $actualSchoolId = $request->school_id ?? 1;

            $setting = \App\Models\RequirementSetting::where('school_id', $actualSchoolId)
                        ->where('course_name', $actualCourse)
                        ->first();

            $hoursNeeded = $setting ? $setting->required_hours : 0; // Default to 0 if not found

            // 2. Create the Intern Profile (ALL SPECIFIC INTERN DATA GOES HERE)
            Intern::create([
                'user_id' => $user->id,
                
                // 👇 ADDED 'N/A' FALLBACKS SO MYSQL DOESN'T CRASH 👇
                'course' => $actualCourse, 
                'school' => $request->school_university ?? $request->school ?? 'N/A',
                
                // 👇 ADDED DEFAULT '1' FOR IDs SO IT PASSES THE REQUIRED INTEGER CHECK 👇
                'school_id' => $actualSchoolId,
                'branch_id' => $request->branch_id ?? $request->assigned_branch ?? 1,
                'department_id' => $request->department_id ?? $request->assigned_department ?? 1,
                
                // 👇 INJECT AUTOMATED HOURS HERE 👇
                'required_hours' => $hoursNeeded,
                'rendered_hours' => 0, // Fresh interns always start at 0

                'date_started' => $request->date_started ?? now(),

                'emergency_name' => $request->emergency_name,
                'emergency_number' => $request->emergency_number,
                'emergency_address' => $request->emergency_address,
            ]);

            DB::commit(); // Save everything to the database

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'access_token' => $token,
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Cancel everything if there was an error
            return response()->json([
                'message' => 'Registration failed. Check your database columns.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        // 🛡️ SAFETY NET HERE TOO
        $user->permissions = $user->permissions ?? [];
        
        return response()->json($user);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }
}