<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // ✨ Allow the school name to be saved
    ];

    // If you have a relationship with requirement settings
    public function requirements()
    {
        return $this->hasMany(RequirementSetting::class);
    }
}