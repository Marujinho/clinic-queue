<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Enums\TicketStatus;
use App\Models\Queue;
use App\Models\QueueTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        return $this->transition($ticket, TicketStatus::Waiting, TicketStatus::Called, 'called_at');
    }

    /**
     * Called → InService. Stamps service_started_at.
     */
    public function start(QueueTicket $ticket): QueueTicket
    {
        return $this->transition($ticket, TicketStatus::Called, TicketStatus::InService, 'service_started_at');
    }

    /**
     * InService → Completed. Stamps completed_at.
     */
    public function complete(QueueTicket $ticket): QueueTicket
    {
        return $this->transition($ticket, TicketStatus::InService, TicketStatus::Completed, 'completed_at');
    }

    /**
     * Waiting|Called → Cancelled. Rejected from InService/Completed/Cancelled.
     */
    public function cancel(QueueTicket $ticket): QueueTicket
    {
        $from = [TicketStatus::Waiting, TicketStatus::Called];

        if (! in_array($ticket->status, $from, true)) {
            throw InvalidTicketTransition::for($ticket, TicketStatus::Cancelled);
        }

        return $this->transition($ticket, $from, TicketStatus::Cancelled);
    }

    /**
     * Select and call the next waiting ticket in the queue using the BR-04
     * ordering (priority DESC, then checked_in_at ASC). Returns null when the
     * queue has no waiting tickets.
     */
    public function callNext(Queue $queue): ?QueueTicket
    {
        // Serialize concurrent "Call Next" clicks on the same queue: the queue
        // row is locked for the duration of the transaction so two providers
        // cannot both pick (and call) the same waiting ticket.
        return DB::transaction(function () use ($queue): ?QueueTicket {
            Queue::query()->whereKey($queue->getKey())->lockForUpdate()->first();

            $ticket = $this->nextWaiting($queue);

            if ($ticket === null) {
                return null;
            }

            return $this->call($ticket);
        });
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

    /**
     * Atomically move the ticket from one of the allowed `$from` statuses to
     * `$to`. The status check is part of the UPDATE's WHERE clause (compare-and-
     * set), so a ticket changed by a concurrent request between read and write
     * is detected (zero rows affected) and rejected instead of overwritten.
     *
     * @param  TicketStatus|list<TicketStatus>  $from
     */
    private function transition(
        QueueTicket $ticket,
        TicketStatus|array $from,
        TicketStatus $to,
        ?string $timestampColumn = null,
    ): QueueTicket {
        $from = is_array($from) ? $from : [$from];

        if (! in_array($ticket->status, $from, true)) {
            throw InvalidTicketTransition::for($ticket, $to);
        }

        $attributes = ['status' => $to];

        if ($timestampColumn !== null) {
            $attributes[$timestampColumn] = Carbon::now();
        }

        $updated = QueueTicket::query()
            ->whereKey($ticket->getKey())
            ->whereIn('status', $from)
            ->update($attributes + ['updated_at' => Carbon::now()]);

        if ($updated === 0) {
            throw InvalidTicketTransition::for($ticket->refresh(), $to);
        }

        return $ticket->refresh();
    }
}
