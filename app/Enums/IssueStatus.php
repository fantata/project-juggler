<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Done => 'Done',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'red',
            self::InProgress => 'yellow',
            self::Done => 'green',
        };
    }
}
