<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Enums\TicketStatus;
use App\Models\Queue;
use App\Models\QueueTicket;
use Illuminate\Support\Carbon;

/**
 * Centralizes every legal lifecycle transition for a queue ticket. Each method
 * validates the ticket's current status and throws {@see InvalidTicketTransition}
 * on an illegal move (BR-06), otherwise flips the status and stamps the relevant
 * timestamp. Keeping this here means components never mutate status directly.
 *
 * Transition table (BR-06):
 *   Waiting   → Called      (call)
 *   Called    → InService   (start)
 *   InService → Completed   (complete)
 *   Waiting   → Cancelled   (cancel)
 *   Called    → Cancelled   (cancel)
 */
class TicketStateMachine
{
    /**
     * Waiting → Called. Stamps called_at.
     */
    public function call(QueueTicket $ticket): QueueTicket
    {
        $this->assert($ticket, TicketStatus::Waiting, TicketStatus::Called);

        $ticket->forceFill([
            'status' => TicketStatus::Called,
            'called_at' => Carbon::now(),
        ])->save();

        return $ticket;
    }

    /**
     * Called → InService. Stamps service_started_at.
     */
    public function start(QueueTicket $ticket): QueueTicket
    {
        $this->assert($ticket, TicketStatus::Called, TicketStatus::InService);

        $ticket->forceFill([
            'status' => TicketStatus::InService,
            'service_started_at' => Carbon::now(),
        ])->save();

        return $ticket;
    }

    /**
     * InService → Completed. Stamps completed_at.
     */
    public function complete(QueueTicket $ticket): QueueTicket
    {
        $this->assert($ticket, TicketStatus::InService, TicketStatus::Completed);

        $ticket->forceFill([
            'status' => TicketStatus::Completed,
            'completed_at' => Carbon::now(),
        ])->save();

        return $ticket;
    }

    /**
     * Waiting|Called → Cancelled. Rejected from InService/Completed/Cancelled.
     */
    public function cancel(QueueTicket $ticket): QueueTicket
    {
        if (! in_array($ticket->status, [TicketStatus::Waiting, TicketStatus::Called], true)) {
            throw InvalidTicketTransition::for($ticket, TicketStatus::Cancelled);
        }

        $ticket->forceFill([
            'status' => TicketStatus::Cancelled,
        ])->save();

        return $ticket;
    }

    /**
     * Select and call the next waiting ticket in the queue using the BR-04
     * ordering (priority DESC, then checked_in_at ASC). Returns null when the
     * queue has no waiting tickets.
     */
    public function callNext(Queue $queue): ?QueueTicket
    {
        $ticket = $this->nextWaiting($queue);

        if ($ticket === null) {
            return null;
        }

        return $this->call($ticket);
    }

    /**
     * Resolve the next waiting ticket per BR-04: highest priority first, and among
     * equal priorities the earliest arrival wins.
     */
    public function nextWaiting(Queue $queue): ?QueueTicket
    {
        return $queue->queueTickets()
            ->where('status', TicketStatus::Waiting)
            ->orderByDesc('priority')
            ->orderBy('checked_in_at')
            ->orderBy('id')
            ->first();
    }

    private function assert(QueueTicket $ticket, TicketStatus $from, TicketStatus $to): void
    {
        if ($ticket->status !== $from) {
            throw InvalidTicketTransition::for($ticket, $to);
        }
    }
}
