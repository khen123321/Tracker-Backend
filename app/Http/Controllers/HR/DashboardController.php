<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Intern;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $today = Carbon::today()->toDateString();

        // ==========================================
        // 1. THE FILTER WALL (Valid Interns Only)
        // ==========================================
        // Lowercase check to catch 'intern' or 'hr intern' regardless of formatting
        $validUserIds = User::whereIn(DB::raw('LOWER(role)'), ['intern', 'hr intern'])->pluck('id');
        $totalInterns = count($validUserIds);
        
        // Get the specific Intern Profile IDs for these valid users
        $validInternIds = Intern::whereIn('user_id', $validUserIds)->pluck('id');

        // ==========================================
        // 2. FETCH ATTENDANCE LOGS (Filtered)
        // ==========================================
        $todayLogs = DB::table('attendance_logs')
            ->whereDate('date', $today)
            ->whereIn('intern_id', $validInternIds) // 🛡️ Completely ignores SuperAdmins/HR Heads
            ->get();

        // ==========================================
        // 3. STRICT CATEGORIES (No Overlap)
        // ==========================================
        // 'Present' now ONLY means they were strictly on time.
        $presentToday = $todayLogs->filter(function($log) {
            return strtolower(trim($log->status)) === 'present';
        })->count();

        $lateToday = $todayLogs->filter(function($log) {
            return strtolower(trim($log->status)) === 'late';
        })->count();

        $excusedToday = $todayLogs->filter(function($log) {
            return strtolower(trim($log->status)) === 'excused';
        })->count();

        // Absent = Total Interns minus everyone who has a log today (Present + Late + Excused)
        $absentToday = max(0, $totalInterns - ($presentToday + $lateToday + $excusedToday));

        // ==========================================
        // 4. METRICS & MATH
        // ==========================================
        // Anyone physically in the building today
        $totalInBuilding = $presentToday + $lateToday;
        
        // Attendance Rate: Percentage of total interns who showed up (even if late)
        $attendanceRate = $totalInterns > 0 ? round(($totalInBuilding / $totalInterns) * 100) : 0;
        
        // On Time %: Percentage of present interns who were actually on time
        $onTimePercentage = $totalInBuilding > 0 ? round(($presentToday / $totalInBuilding) * 100) : 0;
        
        // Total Hours: Summed up ONLY for valid interns
        $totalHours = DB::table('attendance_logs')
            ->whereIn('intern_id', $validInternIds)
            ->sum('hours_rendered') ?? 0;

        // ==========================================
        // 5. PIE GRAPH LOGIC (Filtered)
        // ==========================================
        // Groups exact course names (e.g., 'BSIT', 'BSHM') and counts them
        $courseDistribution = Intern::whereIn('id', $validInternIds)
            ->select('course as name', DB::raw('count(*) as value'))
            ->groupBy('course')
            ->get();

        // ==========================================
        // 6. RETURN DATA TO REACT
        // ==========================================
        return response()->json([
            'total_interns' => $totalInterns,
            'attendance_rate' => $attendanceRate,
            'total_hours' => round($totalHours, 1),
            'on_time_percentage' => max(0, $onTimePercentage),
            'today' => [
                'present' => $presentToday,
                'absent' => $absentToday,
                'excused' => $excusedToday,
                'late' => $lateToday
            ],
            'course_distribution' => $courseDistribution
        ], 200);
    }
}