<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TeacherAttendance extends Model
{
    protected $fillable = [
        'teacher_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function durationLabel(): string
    {
        if (! $this->check_in_time || ! $this->check_out_time) {
            return '-';
        }

        try {
            $in = Carbon::createFromFormat('H:i:s', strlen($this->check_in_time) === 5 ? $this->check_in_time . ':00' : $this->check_in_time);
            $out = Carbon::createFromFormat('H:i:s', strlen($this->check_out_time) === 5 ? $this->check_out_time . ':00' : $this->check_out_time);

            if ($out->lessThan($in)) {
                return '-';
            }

            $diffMinutes = $in->diffInMinutes($out);
            $hours = intdiv($diffMinutes, 60);
            $mins = $diffMinutes % 60;

            if ($hours > 0 && $mins > 0) {
                return "{$hours}j {$mins}m";
            } elseif ($hours > 0) {
                return "{$hours} jam";
            }

            return "{$mins} menit";
        } catch (\Throwable $e) {
            return '-';
        }
    }
}

