<?php

namespace App\Enums;

enum FeedRuleAction: string
{
    case MarkRelevant = 'mark_relevant';
    case Background = 'background';
    case SetNote = 'set_note';

    public function label(): string
    {
        return match ($this) {
            self::MarkRelevant => 'Mark as relevant',
            self::Background => 'Background (grey out)',
            self::SetNote => 'Set a note',
        };
    }
}
