<?php

namespace App\Enums;

enum DueBucket: string
{
    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case ThisWeek = 'this_week';
    case NextWeek = 'next_week';
    case Whenever = 'whenever';

    /**
     * Human-friendly label for display. No dev shorthand, this is read by Danny.
     */
    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Tomorrow => 'Tomorrow',
            self::ThisWeek => 'This week',
            self::NextWeek => 'Next week',
            self::Whenever => 'Whenever',
        };
    }

    /**
     * Sort order for grouping on the Mission Control screen.
     * Lower comes first; "whenever" sits at the end.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::Today => 1,
            self::Tomorrow => 2,
            self::ThisWeek => 3,
            self::NextWeek => 4,
            self::Whenever => 5,
        };
    }
}
