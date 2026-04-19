<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Intern;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrController extends Controller
{
    /**
     * Get list of interns for the main dashboard (Attendance focus)
     */
    public function getInternList(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $interns = User::where('role', 'intern')
            ->with(['intern', 'attendance_logs' => function($query) use ($today) {
                $query->whereDate('date', $today);
            }])
            // 👇 THIS IS THE MAGIC LINE FOR THE PROGRESS BAR 👇
            // It tells Laravel to add up all 'hours_rendered' from the logs and attach it to the user
            ->withSum('attendance_logs', 'hours_rendered')
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
        
        if ($user->email === 'testadmin123@gmail.com' && $request->role !== 'superadmin') {
            return response()->json(['message' => 'Cannot demote the primary master account.'], 400);
        }

        $user->role = $request->role;
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'User updated successfully!', 'user' => $user]);
    }

    /**
     * Get users specifically formatted for the React Role Management table
     */
    public function getRoleUsers()
    {
        $users = User::whereIn('role', ['hr', 'hr_intern', 'superadmin'])
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'permissions', 'assigned_department')
            ->get();
            
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

    /**
     * Get all regular interns for the management table
     */
    public function getInternsForManagement()
    {
        $interns = User::where('role', 'intern')
            ->with('intern') // Load the intern data to get required_hours
            ->withSum('attendance_logs', 'hours_rendered') // Added here too just in case!
            ->select(
                'id', 'first_name', 'last_name', 'email', 'status'
            )
            ->get();
            
        $interns->transform(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name;
            return $user;
        });

        return response()->json($interns);
    }

    /**
     * Save the branch, department, and paperwork checklist
     */
    public function updateInternAssignment(Request $request, $id)
    {
        $intern = User::findOrFail($id);

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

    /* =========================================================
       === BULK ACTION METHODS (REMOVE, EXPORT, ADD HOURS) === 
       ========================================================= */

    public function bulkRemove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $userIds = $request->ids;
            Intern::whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
            DB::commit();
            return response()->json(['message' => count($userIds) . ' interns removed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to remove interns.', 'error' => $e->getMessage()], 500);
        }
    }

    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        $users = User::with('intern')->whereIn('id', $request->ids)->get();
        $fileName = 'Interns_Export_' . date('Y-m-d') . '.csv';
        
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = ['ID', 'First Name', 'Last Name', 'Email', 'Role', 'Status'];

        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    $user->role,
                    $user->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkAddHours(Request $request)
    {
        $request->validate([
            'intern_ids' => 'required|array',
            'date' => 'required|date',
            'time_in' => 'required',
            'time_out' => 'required',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->intern_ids as $userId) {
                $intern = Intern::where('user_id', $userId)->first();
                
                if ($intern) {
                    $timeIn = Carbon::parse($request->date . ' ' . $request->time_in);
                    $timeOut = Carbon::parse($request->date . ' ' . $request->time_out);
                    
                    $newHours = round($timeIn->diffInMinutes($timeOut) / 60, 2);
                    $noteAddition = trim(($request->reason ?? '') . ' ' . ($request->notes ?? ''));

                    $existingLog = AttendanceLog::where('intern_id', $intern->id)
                                                ->where('date', $request->date)
                                                ->first();

                    if ($existingLog) {
                        $existingLog->hours_rendered += $newHours;
                        if ($noteAddition) {
                            $existingLog->notes = $existingLog->notes ? $existingLog->notes . ' | Add: ' . $noteAddition : 'Add: ' . $noteAddition;
                        }
                        $existingLog->save();
                    } else {
                        AttendanceLog::create([
                            'intern_id' => $intern->id,
                            'date' => $request->date,
                            'time_in' => $timeIn->toDateTimeString(),
                            'time_out' => $timeOut->toDateTimeString(),
                            'hours_rendered' => $newHours,
                            'status' => 'Present',
                            'notes' => $noteAddition ?: null
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['message' => 'Hours added successfully to selected interns.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add hours.', 'error' => $e->getMessage()], 500);
        }
    }
}