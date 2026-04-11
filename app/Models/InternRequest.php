<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'date_of_absence', 'reason', 
        'additional_details', 'attachment_path', 'status'
    ];

    // Link it back to the user who submitted it
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}