<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceLog extends Model
{
    use HasFactory;

    // 👇 This allows our Controller to save these specific columns securely 👇
    protected $fillable = [
        'intern_id', 
        'date', 
        'time_in', 
        'lunch_out', 
        'lunch_in', 
        'time_out', 
        'hours_rendered', 
        'status', 
        'image_in', 
        'image_out', 
        'is_flagged', 
        'notes'
    ];

    // 👇 Defines the relationship so an Attendance Log belongs to an Intern 👇
    public function intern()
    {
        return $this->belongsTo(Intern::class, 'intern_id');
    }
}