<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'intern_id', 'date', 'time_in', 'lunch_out', 'lunch_in', 'time_out', 
        'hours_rendered', 'status', 'image_in', 'image_out', 'is_flagged', 'notes'
    ];

    // 👇 ADD THIS RELATIONSHIP METHOD 👇
    public function intern()
    {
        return $this->belongsTo(Intern::class, 'intern_id');
    }
}