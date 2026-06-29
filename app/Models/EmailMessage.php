<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessage extends Model
{
    protected $fillable = [
        'email_account_id',
        'uid',
        'message_id',
        'in_reply_to',
        'from_name',
        'from_email',
        'to_email',
        'subject',
        'body_text',
        'body_html',
        'received_at',
        'folder',
        'is_read',
        'is_junk',
        'priority',
        'action_needed',
        'ai_summary',
        'suggested_reply',
        'triaged_at',
    ];

    protected function casts(): array
    {
        return [
            'uid' => 'integer',
            'received_at' => 'datetime',
            'is_read' => 'boolean',
            'is_junk' => 'boolean',
            'action_needed' => 'boolean',
            'triaged_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    public function isTriaged(): bool
    {
        return $this->triaged_at !== null;
    }
}
