<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    // BARIS INI YANG SEBELUMNYA HILANG/KURANG LENGKAP
    protected $fillable = ['quiz_id', 'question_text', 'points'];

    public function quiz() { 
        return $this->belongsTo(Quiz::class); 
    }
    
    public function options() { 
        return $this->hasMany(QuestionOption::class); 
    }
}