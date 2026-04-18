<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class HrController extends Controller
{
    /**
     * Get list of interns for the main dashboard (Attendance focus)
     */
    public function getInternList(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $interns = User::where('role', 'intern')
            // 👇 THIS IS THE FIX: Added 'intern' to the array! 👇
            ->with(['intern', 'attendance_logs' => function($query) use ($today) {
                $query->whereDate('date', $today);
            }])
            ->get();

        return response()->json($interns);
    }

    /**
     * Get Administrative Users (Superadmin, HR & HR Interns)
     */
    public function getAllUsers()
    {
        $users = User::whereIn('role', ['hr', 'hr_intern', 'superadmin'])
            ->orderBy('role', 'asc')
            ->get();

        return response()->json($users);
    }

    /**
     * Create a new HR account
     */
    public function storeSubUser(Request $request)
    {
        // Check for superadmin role instead of a specific email
        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized. Only Superadmins can create accounts.'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:6',
            'role'       => 'required|in:hr_intern,hr,superadmin', 
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'status'     => 'active',
            'permissions' => [] 
        ]);

        return response()->json(['message' => 'Account created successfully!'], 201);
    }

    /**
     * Update roles and status for a specific user
     */
    public function updatePermissions(Request $request, $id)
    {
        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Access Denied. Superadmin required.'], 403);
        }

        $request->validate([
            'role' => 'required|string|in:hr_intern,hr,superadmin',
            'status' => 'required|string|in:active,inactive'
        ]);

        $user = User::findOrFail($id);
        
        // Safety: Prevent the master superadmin from being locked out or demoted
        if ($user->email === 'testadmin123@gmail.com' && $request->role !== 'superadmin') {
            return response()->json(['message' => 'Cannot demote the primary master account.'], 400);
        }

        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'User updated successfully!', 'user' => $user]);
    }

    /* =========================================================
       === NEW METHODS FOR THE ROLE & ACCESS MANAGEMENT UI === 
       ========================================================= */

    /**
     * Get users specifically formatted for the React Role Management table
     */
    public function getRoleUsers()
    {
        // Fetch users who are staff (adjust role names as needed based on your DB)
        $users = User::whereIn('role', ['hr', 'hr_intern', 'superadmin'])
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'permissions', 'assigned_department')
            ->get();
            
        // Transform the data so React can read it perfectly
        $users->transform(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name;
            $user->department = $user->assigned_department ?? 'HR Department';
            
            if (!$user->permissions) {
                $user->permissions = [];
            }
            return $user;
        });

        return response()->json($users);
    }

    /**
     * Update the page permissions array and role from the React Modal
     */
    public function updateUserAccess(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Security check: Don't let anyone downgrade the master Superadmin account
        if ($user->role === 'superadmin' && $request->role !== 'superadmin') {
            return response()->json(['message' => 'Cannot modify the main Superadmin account.'], 403);
        }

        $user->update([
            'role' => $request->role ?? $user->role,
            'permissions' => $request->permissions ?? [],
        ]);

        return response()->json([
            'message' => 'Access updated successfully!',
            'user' => $user
        ]);
    }

    /* =========================================================
       === INTERN MANAGEMENT (ASSIGNMENTS & REQUIREMENTS) === 
       ========================================================= */

    /**
     * Get all regular interns for the management table
     */
    public function getInternsForManagement()
    {
        $interns = User::where('role', 'intern')
            ->select(
                'id', 'first_name', 'last_name', 'email', 'school', 'course', 
                'assigned_branch', 'assigned_department', 'status',
                'has_moa', 'has_endorsement', 'has_pledge', 'has_nda'
            )
            ->get();
            
        // Format the name for React
        $interns->transform(function ($intern) {
            $intern->name = $intern->first_name . ' ' . $intern->last_name;
            return $intern;
        });

        return response()->json($interns);
    }

    /**
     * Save the branch, department, and paperwork checklist
     */
    public function updateInternAssignment(Request $request, $id)
    {
        $intern = User::findOrFail($id);

        // Security check: Make sure we are only editing actual interns
        if ($intern->role !== 'intern') {
            return response()->json(['message' => 'You can only assign branches to Interns.'], 403);
        }

        $intern->update([
            'assigned_branch' => $request->assigned_branch,
            'assigned_department' => $request->assigned_department,
            'status' => $request->status,
            'has_moa' => $request->has_moa,
            'has_endorsement' => $request->has_endorsement,
            'has_pledge' => $request->has_pledge,
            'has_nda' => $request->has_nda,
        ]);

        return response()->json([
            'message' => 'Intern assignment updated successfully!',
            'intern' => $intern
        ]);
    }
}