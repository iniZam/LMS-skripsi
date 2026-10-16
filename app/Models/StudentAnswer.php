<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $fillable = ['attempt_id', 'question_id', 'option_id', 'is_correct'];
    
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt() { 
        return $this->belongsTo(QuizAttempt::class, 'attempt_id'); 
    }
    
    public function question() { 
        return $this->belongsTo(Question::class); 
    }
    
    public function option() { 
        return $this->belongsTo(QuestionOption::class, 'option_id'); 
    }
}