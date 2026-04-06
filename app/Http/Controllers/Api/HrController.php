<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class HrController extends Controller
{
    public function getInternList(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $interns = User::where('role', 'intern')
            ->with(['intern', 'attendance_logs' => function($query) use ($today) {
                $query->whereDate('date', $today);
            }])
            ->get();

        return response()->json($interns);
    }
}