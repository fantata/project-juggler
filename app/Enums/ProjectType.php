<?php

namespace App\Enums;

enum ProjectType: string
{
    case Client = 'client';
    case Personal = 'personal';
    case Speculative = 'speculative';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Personal => 'Personal',
            self::Speculative => 'Speculative',
        };
    }
}
