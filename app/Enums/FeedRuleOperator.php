<?php

namespace App\Enums;

enum FeedRuleOperator: string
{
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case MatchesRegex = 'matches_regex';

    public function label(): string
    {
        return match ($this) {
            self::Contains => 'Contains',
            self::StartsWith => 'Starts with',
            self::MatchesRegex => 'Matches regex',
        };
    }

    public function test(string $haystack, string $needle): bool
    {
        return match ($this) {
            self::Contains => str_contains(strtolower($haystack), strtolower($needle)),
            self::StartsWith => str_starts_with(strtolower($haystack), strtolower($needle)),
            self::MatchesRegex => (bool) preg_match($needle, $haystack),
        };
    }
}
