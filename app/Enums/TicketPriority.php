<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketPriority: int
{
    case Normal = 0;
    case High = 1;
    case Urgent = 2;

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Normal => 'neutral',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
