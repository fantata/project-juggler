<?php

namespace App\Enums;

enum FeedRuleField: string
{
    case Title = 'title';
    case Description = 'description';
    case Location = 'location';

    public function label(): string
    {
        return match ($this) {
            self::Title => 'Title',
            self::Description => 'Description',
            self::Location => 'Location',
        };
    }
}
