<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLog extends Model
{
    protected $fillable = [
        'project_id',
        'entry',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
