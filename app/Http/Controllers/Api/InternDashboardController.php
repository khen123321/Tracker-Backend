<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Intern;
use App\Models\AttendanceLog;
use Carbon\Carbon;

class InternDashboardController extends Controller
{
    public function getStats(Request $request)
    {
        // Force Philippine Timezone for accurate daily/weekly math
        date_default_timezone_set('Asia/Manila');
        
        $user = $request->user();
        $intern = Intern::where('user_id', $user->id)->first();

        if (!$intern) {
            return response()->json(['message' => 'Intern profile not found'], 404);
        }

        // Setup Dates
        $today = Carbon::today()->toDateString();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        // ==========================================
        // 1. OJT PROGRESS (Accuracy: 486 Hours)
        // ==========================================
        $requiredHours = 486; 
        $totalHoursRendered = AttendanceLog::where('intern_id', $intern->id)->sum('hours_rendered') ?? 0;

        // ==========================================
        // 2. TODAY'S STATUS (Accuracy: Live Tracking)
        // ==========================================
        $todayLog = AttendanceLog::where('intern_id', $intern->id)->whereDate('date', $today)->first();
        
        $todayStatus = 'Not Timed In';
        $todayClockIn = '--:--';
        $todayHours = 0;

        if ($todayLog) {
            $todayStatus = $todayLog->status ?? 'Timed In';
            $todayClockIn = $todayLog->time_in ? Carbon::parse($todayLog->time_in)->format('h:i A') : '--:--';
                
            // ✨ LIVE CALCULATION: If they haven't timed out, calculate hours from time_in to NOW
            if ($todayLog->time_in && !$todayLog->time_out) {
                $startTime = Carbon::parse($todayLog->time_in);
                $todayHours = round(Carbon::now()->diffInMinutes($startTime) / 60, 1);
            } else {
                $todayHours = round($todayLog->hours_rendered ?? 0, 1);
            }
        }

        // ==========================================
        // 3. THIS WEEK SUMMARY (Accuracy: Include Incomplete)
        // ==========================================
        $weekLogs = AttendanceLog::where('intern_id', $intern->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->get();

        $weekDaysPresent = $weekLogs->filter(function($log) {
            $status = strtolower(trim($log->status));
            // Count today even if it's still 'incomplete'
            return in_array($status, ['present', 'late', 'incomplete']);
        })->count();

        // Add today's live hours to the weekly total rendered
        $weekHoursRendered = round($weekLogs->sum('hours_rendered') + ($todayLog && !$todayLog->time_out ? $todayHours : 0), 1);

        // Est. Completion Date Math
        $remainingHours = max(0, $requiredHours - ($totalHoursRendered + ($todayLog && !$todayLog->time_out ? $todayHours : 0)));
        $daysLeft = ceil($remainingHours / 8); 
        $completionDate = ($totalHoursRendered + $todayHours) > 0 
            ? Carbon::now()->addWeekdays($daysLeft)->format('M. d, Y') 
            : 'TBD';

        return response()->json([
            'totalHoursRequired' => $requiredHours,
            'hoursRendered'      => round($totalHoursRendered, 1),
            'completionDate'     => $completionDate,
            'todayStatus'        => $todayStatus,
            'todayClockIn'       => $todayClockIn,
            'todayOfficial'      => '08:15 AM', 
            'todayHours'         => $todayHours,
            'weekDaysPresent'    => $weekDaysPresent,
            'weekHoursRendered'  => $weekHoursRendered,
        ], 200);
    }
}