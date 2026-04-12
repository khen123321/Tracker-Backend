<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\Intern;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Office Coordinates
    private $officeLat = 8.5042;
    private $officeLng = 124.6143;
    private $radiusInMeters = 5000; // Allowed distance from office (Set high for testing)

    /**
     * 1. GET HISTORY (DTR Format for "My Logs" Page)
     */
    public function getHistory(Request $request)
    {
        try {
            $user = $request->user();
            $intern = Intern::where('user_id', $user->id)->first();

            if (!$intern) {
                return response()->json(['message' => 'Intern profile not found.'], 404);
            }

            $logs = AttendanceLog::where('intern_id', $intern->id)
                ->orderBy('date', 'desc')
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        // Formats "2026-04-12" to "April 12, 2026"
                        'formatted_date' => $log->date ? Carbon::parse($log->date)->format('F j, Y') : 'N/A',
                        
                        // Map the 4 DTR columns
                        'time_in_am'  => $log->time_in ? Carbon::parse($log->time_in)->format('g:i A') : '-',
                        'time_out_am' => $log->lunch_out ? Carbon::parse($log->lunch_out)->format('g:i A') : '-',
                        'time_in_pm'  => $log->lunch_in ? Carbon::parse($log->lunch_in)->format('g:i A') : '-',
                        'time_out_pm' => $log->time_out ? Carbon::parse($log->time_out)->format('g:i A') : '-',
                        
                        'hours_rendered' => $log->hours_rendered ?? 0,
                        'status' => Str::title($log->status ?? 'pending'),
                    ];
                });

            return response()->json($logs, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. LOG ATTENDANCE (Clock In/Out)
     */
    public function logAttendance(Request $request)
    {
        $request->validate([
            'type'  => 'required|in:time_in,lunch_out,lunch_in,time_out',
            'lat'   => 'required|numeric',
            'lng'   => 'required|numeric',
            'image' => 'required' 
        ]);

        $user = $request->user();
        $intern = Intern::where('user_id', $user->id)->first();

        if (!$intern) {
            return response()->json(['message' => 'Intern profile not found.'], 404);
        }

        // Check Distance
        $distance = $this->calculateDistance($request->lat, $request->lng, $this->officeLat, $this->officeLng);
        
        if ($distance > $this->radiusInMeters) {
            return response()->json([
                'message' => "You are too far from the premises ($distance meters away)."
            ], 403);
        }

        // Handle Image Storage
        $image = $request->image;
        $image = str_replace('data:image/jpeg;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'attendance/' . $user->id . '_' . time() . '.jpg';
        Storage::disk('public')->put($imageName, base64_decode($image));

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $log = AttendanceLog::firstOrNew([
            'intern_id' => $intern->id,
            'date'      => $today
        ]);

        switch ($request->type) {
            case 'time_in':
                if ($log->time_in) return response()->json(['message' => 'Already timed in for today.'], 400);
                $log->time_in = $now->toDateTimeString(); // SQL Safe Datetime
                $log->status = $now->hour > 8 || ($now->hour == 8 && $now->minute > 15) ? 'late' : 'present';
                $log->image_in = $imageName;
                break;

            case 'lunch_out':
                $log->lunch_out = $now->toDateTimeString(); 
                break;

            case 'lunch_in':
                $log->lunch_in = $now->toDateTimeString(); 
                break;

            case 'time_out':
                if (!$log->time_in) return response()->json(['message' => 'You must time in first.'], 400);
                $log->time_out = $now->toDateTimeString(); 
                $log->image_out = $imageName;
                
                // Calculate hours
                $start = Carbon::parse($log->time_in);
                $end = $now;
                $totalMinutes = $end->diffInMinutes($start);
                $hours = ($totalMinutes > 300) ? ($totalMinutes - 60) / 60 : $totalMinutes / 60;
                $log->hours_rendered = round($hours, 2);
                break;
        }

        $log->save();

        return response()->json([
            'message' => Str::title(str_replace('_', ' ', $request->type)) . ' recorded successfully!',
            'log' => $log
        ], 200);
    }

    /**
     * 3. DISTANCE HELPER
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c);
    }
}