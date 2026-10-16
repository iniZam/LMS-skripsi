<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single "Pertemuan" (session/module) within a class: video, materials,
 * and an optional assignment with a deadline.
 */
class Meeting extends Model
{
    protected $fillable = [
        'class_id',
        'meeting_number',
        'title',
        'description',
        'youtube_url',
        'has_assignment',
        'assignment_title',
        'assignment_instructions',
        'assignment_deadline',
    ];

    protected function casts(): array
    {
        return [
            'has_assignment' => 'boolean',
            'assignment_deadline' => 'datetime',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isPastDeadline(): bool
    {
        return $this->assignment_deadline !== null && now()->greaterThan($this->assignment_deadline);
    }

    /**
     * Convert a youtube.com/watch, youtu.be, or /shorts/ URL into an /embed/
     * URL suitable for an <iframe>. Mirrors convertToEmbedUrl() from server.ts.
     */
    public function youtubeEmbedUrl(): ?string
    {
        $url = $this->youtube_url;
        if (! $url) {
            return null;
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        $videoId = '';
        if (str_contains($url, 'youtu.be/')) {
            $videoId = explode('?', explode('youtu.be/', $url)[1] ?? '')[0];
        } elseif (str_contains($url, 'youtube.com/watch')) {
            preg_match('/v=([^&]+)/', $url, $matches);
            $videoId = $matches[1] ?? '';
        } elseif (str_contains($url, 'youtube.com/shorts/')) {
            $videoId = explode('?', explode('youtube.com/shorts/', $url)[1] ?? '')[0];
        }

        return $videoId !== '' ? "https://www.youtube.com/embed/{$videoId}" : $url;
    }
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
