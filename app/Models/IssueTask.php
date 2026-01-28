<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueTask extends Model
{
    protected $fillable = [
        'issue_id',
        'description',
        'is_complete',
        'position',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'is_complete' => 'boolean',
            'position' => 'integer',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
