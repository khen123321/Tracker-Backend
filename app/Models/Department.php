<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'supervisor_name',
    ];

    /**
     * Relationship: A department has many interns.
     */
    public function interns()
    {
        // This links the Department to the Intern model
        return $this->hasMany(Intern::class, 'department_id');
    }

    /**
     * Relationship: A department can also be linked directly to Users 
     * if you are using the department_id on the users table.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}