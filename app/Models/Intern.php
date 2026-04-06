<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intern extends Model
{
    use HasFactory;

    // This tells Laravel it's allowed to interact with the user_id column
    // Add any other columns your interns table has here (like 'school', 'hours_rendered', etc.)
    protected $fillable = [
        'user_id',
        'school_id',
        'department_id', // Add this if your DB requires it
        'branch_id',
        'course',
        // 'department_id', // Uncomment or add others if you have them
    ];

    // Optional: Creates the relationship back to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}