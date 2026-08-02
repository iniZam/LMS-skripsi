<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'meeting_id',
        'student_id',
        'file_name',
        'file_path',
        'file_size',
        'student_note',
        'submitted_at',
        'is_late',
        'grade',
        'feedback',
        'graded_at',
        'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'grade' => 'integer',
            'graded_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->grade !== null;
    }

    public function statusLabel(): string
    {
        return $this->is_late ? 'Terlambat' : 'Tepat Waktu';
    }
}
