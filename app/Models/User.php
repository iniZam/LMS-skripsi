<?php

namespace App\Models;

// Note: this file merges into Laravel's default app/Models/User.php.
// Keep Laravel's default use-statements (HasFactory, Notifiable, Authenticatable)
// and just add the role helpers / relationships shown below if you already have
// a customized User model.

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nip_nis',
        'kelas_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Laravel automatically hashes this attribute whenever it is set,
            // so `User::create(['password' => $plain])` is safe and correct.
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_TEACHER => 'Pengajar',
            self::ROLE_STUDENT => 'Siswa',
            default => ucfirst($this->role),
        };
    }

    /** Classes this user teaches (role = teacher). */
    public function taughtClasses(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'teacher_id');
    }

    /** Classes this user is enrolled in (role = student). */
    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_student', 'student_id', 'class_id')
            ->withTimestamps();
    }

    /** Assignment submissions authored by this user (role = student). */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    /** Submissions graded by this user (role = teacher/admin). */
    public function gradedSubmissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'graded_by');
    }

    /** The homeroom (kelas) this student belongs to, e.g. "Kelas 7A". */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /** Homeroom(s) this user is the wali kelas (homeroom teacher) for. */
    public function waliKelasOf(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }
}
