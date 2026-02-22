<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCalendarEvent extends Model
{
    protected $fillable = [
        'source',
        'uid',
        'title',
        'start',
        'end',
        'location',
        'all_day',
    ];

    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
            'all_day' => 'boolean',
        ];
    }
}
