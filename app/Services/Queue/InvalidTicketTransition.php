<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Enums\TicketStatus;
use App\Models\QueueTicket;
use DomainException;

/**
 * Thrown when a ticket is asked to make a transition that is not permitted from
 * its current status (see BR-06 transition table).
 */
class InvalidTicketTransition extends DomainException
{
    public static function for(QueueTicket $ticket, TicketStatus $to): self
    {
        return new self(sprintf(
            'Cannot transition ticket %s from %s to %s.',
            $ticket->ticket_number ?? '(unsaved)',
            $ticket->status->label(),
            $to->label(),
        ));
    }
}
