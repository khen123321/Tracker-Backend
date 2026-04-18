<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementSetting extends Model
{
    protected $fillable = ['school_id', 'course_name', 'required_hours'];

    public function school() {
        return $this->belongsTo(School::class);
    }
}