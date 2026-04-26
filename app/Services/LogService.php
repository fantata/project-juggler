<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectLog;

class LogService
{
    public function log(Project $project, string $entry, ?float $hours = null): ProjectLog
    {
        $log = $project->logs()->create([
            'entry' => $entry,
            'hours' => $hours,
        ]);

        $project->markTouched();

        return $log;
    }
}
