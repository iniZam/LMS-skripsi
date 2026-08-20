<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['meeting_id', 'student_id', 'status'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}