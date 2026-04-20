<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InternController extends Controller
{
    /**
     * Display a listing of all interns (For HR Dashboard).
     */
    public function index()
    {
        try {
            // Fetch all users who are interns, and eagerly load their details
            $interns = User::where('role', 'intern')
                ->with([
                    'intern',       // The intern profile details
                    'school',       // The actual school name
                    'branch',       // The branch name
                    'department',   // The department name
                ])
                ->get();

            return response()->json($interns, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch interns list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified intern profile (For HR or the Intern themselves).
     */
    public function show($id)
    {
        // ✨ Intercept "me" and grab the exact user securely from the database token
        if ($id === 'me') {
            $id = auth('sanctum')->id(); 
        }

        // Safety Net: If the token is expired or missing, stop them here.
        if (!$id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please log in again.'
            ], 401);
        }

        try {
            // Eager load all the relationships your React app expects
            $intern = User::with([
                'intern',       
                'school',       
                'branch',       
                'department',   
                'attendance_logs' 
            ])->findOrFail($id);

            // Calculate total hours safely
            if ($intern->relationLoaded('attendance_logs')) {
                $totalHours = $intern->attendance_logs->sum('hours_rendered');
                $intern->setAttribute('attendance_logs_sum_hours_rendered', $totalHours);
            }

            return response()->json($intern, 200);

        } catch (ModelNotFoundException $e) {
            // This ONLY triggers if the ID does not exist in the users table
            return response()->json([
                'status' => 'error',
                'message' => 'Intern not found in the database.'
            ], 404);
            
        } catch (\Exception $e) {
            // 🚨 THIS REVEALS THE REAL CRASH (e.g., missing column, bad relationship) 🚨
            return response()->json([
                'status' => 'error',
                'message' => 'Server Crash: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }
}