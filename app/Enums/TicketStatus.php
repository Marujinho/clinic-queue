<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case InService = 'in_service';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Called => 'Called',
            self::InService => 'In Service',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Waiting => 'info',
            self::Called => 'warning',
            self::InService => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Waiting, self::Called, self::InService => true,
            self::Completed, self::Cancelled => false,
        };
    }
}
