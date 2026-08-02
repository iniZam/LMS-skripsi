<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A homeroom / rombongan belajar, e.g. "Kelas 7A". This is the student's
 * fixed grade + section for the school year — separate from `ClassRoom`,
 * which represents a Mata Pelajaran (subject) taught to a homeroom.
 */
class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'tingkat',
        'nama',
        'wali_kelas_id',
        'tahun_ajaran',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
        ];
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_id');
    }

    /** Human-readable label, e.g. "Kelas 7A". */
    public function label(): string
    {
        return "Kelas {$this->tingkat}{$this->nama}";
    }
}
