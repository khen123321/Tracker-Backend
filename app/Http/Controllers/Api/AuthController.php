<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Intern; // 👈 Added Intern Model
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Exception;

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
            'course_program' => 'required|string', // 👈 Ensure course is required
        ]);

        // 1. Create the base User account
        $user = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'intern',
            'status' => 'active',
            'course_program' => $request->course_program,
            'school_university' => $request->school_university,
            'assigned_branch' => $request->assigned_branch,
            'assigned_department' => $request->assigned_department,
            'date_started' => $request->date_started,
        ]);

        // 2. ✨ Create the Intern Profile for the Graph & Attendance ✨
        // We map the course_program from the form directly into the course column
        Intern::create([
            'user_id' => $user->id,
            'course' => $request->course_program, 
            'school_id' => 1, // You can make these dynamic later if you have tables for them
            'branch_id' => 1,
            'department_id' => 1,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'user' => $user
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }
}