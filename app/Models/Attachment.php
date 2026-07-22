<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class Attachment extends Model
{
    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'guest_key',
        'guest_name',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Don't orphan the stored file when the record goes.
        static::deleted(function (Attachment $attachment): void {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function url(): string
    {
        return route('attachments.show', $this);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * Playable as audio? True for real audio MIMEs, and also for browser voice
     * memos: MediaRecorder writes into a webm/mp4 *container*, so the server-side
     * MIME guess comes back as video/* even though it's an audio-only recording.
     * Those are always named "voice-memo.<ext>", so we recognise them by name.
     */
    public function isAudio(): bool
    {
        return str_starts_with((string) $this->mime_type, 'audio/')
            || str_starts_with((string) $this->original_name, 'voice-memo.');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
