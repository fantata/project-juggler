<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Blocked = 'blocked';
    case Complete = 'complete';
    case Killed = 'killed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Blocked => 'Blocked',
            self::Complete => 'Complete',
            self::Killed => 'Killed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Paused => 'yellow',
            self::Blocked => 'red',
            self::Complete => 'blue',
            self::Killed => 'gray',
        };
    }
}
