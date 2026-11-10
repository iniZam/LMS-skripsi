<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_UTS = 'uts';
    public const TYPE_UAS = 'uas';

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'class_id',
        'meeting_id',
        'type',
        'approval_status',
        'approved_by',
        'approved_at',
        'title',
        'description',
        'duration_minutes',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isExam(): bool
    {
        return in_array($this->type, [self::TYPE_UTS, self::TYPE_UAS], true);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_UTS => 'UTS (Ujian Tengah Semester)',
            self::TYPE_UAS => 'UAS (Ujian Akhir Semester)',
            default => 'Kuis',
        };
    }

    public function shortTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_UTS => 'UTS',
            self::TYPE_UAS => 'UAS',
            default => 'Kuis',
        };
    }

    public function approvalStatusLabel(): string
    {
        return match ($this->approval_status) {
            self::STATUS_APPROVED => 'Disetujui (Rilis)',
            self::STATUS_PENDING => 'Menunggu ACC Admin',
            self::STATUS_REJECTED => 'Ditolak Admin',
            default => ucfirst((string) $this->approval_status),
        };
    }
}
