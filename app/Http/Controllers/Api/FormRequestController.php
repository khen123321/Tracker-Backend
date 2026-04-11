<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternRequest;
use App\Models\User;
use App\Notifications\NewFormRequestAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Added this
use Illuminate\Support\Facades\Notification;

class FormRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'date_of_absence' => 'required|date',
            'reason' => 'required|string',
            'additional_details' => 'nullable|string',
        ]);

        // Use Auth::id() instead of auth()->id() to fix the IDE error
        $internRequest = InternRequest::create([
            'user_id' => Auth::id(), 
            'type' => $validated['type'],
            'date_of_absence' => $validated['date_of_absence'],
            'reason' => $validated['reason'],
            'additional_details' => $request->additional_details,
            'status' => 'Pending',
        ]);

        $admins = User::whereIn('role', ['hr', 'admin', 'superadmin'])->get();

        // Use Auth::user() to fix the IDE error
        if (Auth::user()) {
            Notification::send($admins, new NewFormRequestAlert($internRequest, Auth::user()));
        }

        return response()->json([
            'message' => 'Form submitted successfully!',
            'data' => $internRequest
        ], 201);
    }
}