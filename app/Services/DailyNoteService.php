<?php

namespace App\Services;

use App\Models\DailyNote;
use Illuminate\Database\Eloquent\Collection;

class DailyNoteService
{
    private const VALID_ENERGY = ['low', 'medium', 'high'];

    public function create(string $body, ?string $energy = null): DailyNote
    {
        return DailyNote::create([
            'body' => $body,
            'energy_level' => in_array($energy, self::VALID_ENERGY, true) ? $energy : null,
        ]);
    }

    public function recent(int $days = 14): Collection
    {
        $days = max(1, min(365, $days));

        return DailyNote::where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get(['id', 'body', 'energy_level', 'created_at']);
    }
}
