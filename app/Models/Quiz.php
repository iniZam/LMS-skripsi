<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['meeting_id', 'title', 'description', 'duration_minutes', 'deadline'];
    
    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function meeting() { 
        return $this->belongsTo(Meeting::class); 
    }
    
    public function questions() { 
        return $this->hasMany(Question::class); 
    }
    
    public function attempts() { 
        return $this->hasMany(QuizAttempt::class); 
    }
}