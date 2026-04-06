<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Intern;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function logAttendance(Request $request)
    {
        $request->validate([
            'type' => 'required|in:time_in,lunch_out,lunch_in,time_out',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'image' => 'required' 
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $intern = Intern::where('user_id', $user->id)->first();
        if (!$intern) {
            $intern = Intern::create([
                'user_id' => $user->id,
                'school_id' => 1,
                'branch_id' => 1,
                'department_id' => 1,
                'course' => 'BS in IT'
            ]);
        }

        $img = $request->image;
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $fileName = 'attendance/' . $user->id . '_' . $request->type . '_' . time() . '.jpg';
        Storage::disk('public')->put($fileName, base64_decode($img));

        $log = AttendanceLog::firstOrCreate(
            ['intern_id' => $intern->id, 'date' => $today],
            ['status' => 'incomplete']
        );

        switch ($request->type) {
            case 'time_in':
                if ($log->time_in) return response()->json(['message' => 'Already timed in for AM!'], 400);
                $log->time_in = $now;
                $log->latitude_in = $request->lat;
                $log->longitude_in = $request->lng;
                $log->time_in_selfie = $fileName;
                
                $startTime = Carbon::createFromFormat('H:i', '08:15');
                $log->status = $now->gt($startTime) ? 'late' : 'present';
                break;

            case 'lunch_out':
                $log->lunch_out = $now;
                $log->lunch_out_lat = $request->lat;
                $log->lunch_out_lng = $request->lng;
                $log->lunch_out_selfie = $fileName;
                break;

            case 'lunch_in':
                $log->lunch_in = $now;
                $log->lunch_in_lat = $request->lat;
                $log->lunch_in_lng = $request->lng;
                $log->lunch_in_selfie = $fileName;
                break;

            case 'time_out':
    $log->time_out = $now;

    // 1. Calculate AM Minutes (Time In to Lunch Out)
    $amMinutes = 0;
    if ($log->time_in && $log->lunch_out) {
        $amMinutes = \Carbon\Carbon::parse($log->time_in)->diffInMinutes(\Carbon\Carbon::parse($log->lunch_out));
    }

    // 2. Calculate PM Minutes (Lunch In to Time Out)
    $pmMinutes = 0;
    if ($log->lunch_in && $log->time_out) {
        $pmMinutes = \Carbon\Carbon::parse($log->lunch_in)->diffInMinutes(\Carbon\Carbon::parse($log->time_out));
    }

    // 3. ADD THEM TOGETHER
    $log->hours_rendered = $amMinutes + $pmMinutes; 
    
    $log->save();
    break;}

        $log->save();

        return response()->json([
            'message' => 'Attendance recorded successfully!',
            'status' => $log->status
        ]);
    }
}