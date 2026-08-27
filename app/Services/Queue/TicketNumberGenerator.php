<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Models\Queue;
use Illuminate\Support\Carbon;

/**
 * Generates human-friendly ticket numbers that are sequential *per queue* and
 * *per calendar day*, formatted like `A001`, `A002`, … .
 *
 * The sequence resets every day and is independent for each queue: a brand-new
 * day, or a different queue, both restart numbering at `A001`.
 */
class TicketNumberGenerator
{
    /**
     * Compute the next ticket number for the given queue on the given day
     * (defaults to today). Counts the tickets already checked into this queue on
     * that calendar date and returns the next value, zero-padded to three digits.
     */
    public function next(Queue $queue, ?Carbon $on = null): string
    {
        $on ??= Carbon::now();

        $countToday = $queue->queueTickets()
            ->whereDate('checked_in_at', $on->toDateString())
            ->count();

        $sequence = $countToday + 1;

        return 'A'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
