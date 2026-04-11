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

        // 1. Find the intern profile (Created during Registration)
        $intern = Intern::where('user_id', $user->id)->first();
        
        // ✨ THE FIX: Fail gracefully instead of creating fake profiles ✨
        if (!$intern) {
            return response()->json([
                'message' => 'Intern profile not found. Please contact HR to complete your registration setup.'
            ], 403);
        }

        // Image Handling
        $img = $request->image;
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $fileName = 'attendance/' . $user->id . '_' . $request->type . '_' . time() . '.jpg';
        Storage::disk('public')->put($fileName, base64_decode($img));

        $log = AttendanceLog::firstOrCreate(
            ['intern_id' => $intern->id, 'date' => $today],
            ['status' => 'Incomplete']
        );

        switch ($request->type) {
            case 'time_in':
                if ($log->time_in) return response()->json(['message' => 'Already timed in!'], 400);
                $log->time_in = $now; 
                $log->latitude_in = $request->lat;
                $log->longitude_in = $request->lng;
                $log->time_in_selfie = $fileName;
                
                $lateThreshold = Carbon::createFromFormat('H:i', '08:15');
                $log->status = $now->gt($lateThreshold) ? 'Late' : 'Present';
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
                
                // Calculate Hours safely
                $amMin = 0; $pmMin = 0;
                if ($log->time_in && $log->lunch_out) {
                    $amMin = Carbon::parse($log->time_in)->diffInMinutes(Carbon::parse($log->lunch_out));
                }
                if ($log->lunch_in && $log->time_out) {
                    $pmMin = Carbon::parse($log->lunch_in)->diffInMinutes(Carbon::parse($log->time_out));
                }

                $log->hours_rendered = ($amMin + $pmMin) / 60; 
                break;
        }

        $log->save();

        return response()->json([
            'message' => 'Attendance recorded successfully!',
            'status' => $log->status
        ]);
    }
}