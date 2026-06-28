<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'imap_host',
        'imap_port',
        'imap_username',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'password',
        'from_addresses',
        'color',
        'is_active',
        'last_uid',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            // Credentials are encrypted at rest, never stored or shown in plain.
            'password' => 'encrypted',
            'from_addresses' => 'array',
            'is_active' => 'boolean',
            'last_uid' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'password',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /** Only accounts belonging to the given user — the privacy boundary. */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /** The SMTP login falls back to the IMAP one when not set separately. */
    public function smtpUsername(): string
    {
        return $this->smtp_username ?: $this->imap_username;
    }

    public function smtpHost(): string
    {
        return $this->smtp_host ?: $this->imap_host;
    }
}
