<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A course/class (the prototype called this "ClassRoom" in src/types.ts).
 * Table name stays `classes` since that already matches the migration
 * and reads naturally in routes (e.g. /classes/{class}).
 */
class ClassRoom extends Model
{
    protected $table = 'classes';

    public const STATUS_REGISTRATION = 'registration';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'code',
        'title',
        'description',
        'subject_category',
        'teacher_id',
        'status',
        'academic_year',
        'banner_gradient',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'class_id', 'student_id')
            ->withTimestamps();
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'class_id')->orderBy('meeting_number');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REGISTRATION => 'Pendaftaran Dibuka',
            self::STATUS_ACTIVE => 'Sedang Berlangsung',
            self::STATUS_COMPLETED => 'Selesai',
            default => ucfirst($this->status),
        };
    }
}
