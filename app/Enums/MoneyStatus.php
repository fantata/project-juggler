<?php

namespace App\Enums;

enum MoneyStatus: string
{
    case Paid = 'paid';
    case Partial = 'partial';
    case Awaiting = 'awaiting';
    case None = 'none';
    case Speculative = 'speculative';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Partial => 'Partial',
            self::Awaiting => 'Awaiting',
            self::None => 'None',
            self::Speculative => 'Speculative',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'green',
            self::Partial => 'yellow',
            self::Awaiting => 'orange',
            self::None => 'gray',
            self::Speculative => 'purple',
        };
    }
}
