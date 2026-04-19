<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException; // 👈 Required for catching 404s properly

class InternController extends Controller
{
    public function show($id)
    {
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