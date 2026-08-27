<?php

use App\Enums\TicketStatus;
use App\Models\Queue;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

new class extends Component
{
    public ?Queue $queue = null;

    /**
     * Base ticket query, scoped to a single queue when one was given.
     *
     * @return Builder<QueueTicket>
     */
    private function tickets(): Builder
    {
        return QueueTicket::query()
            ->when($this->queue !== null, fn (Builder $query) => $query->where('queue_id', $this->queue->id));
    }

    /**
     * Average wait in whole minutes (called_at − checked_in_at) across every
     * ticket that has been called, or null when no ticket was called yet.
     */
    private function averageWaitMinutes(): ?int
    {
        $tickets = $this->tickets()
            ->whereNotNull('called_at')
            ->whereNotNull('checked_in_at')
            ->get(['checked_in_at', 'called_at']);

        if ($tickets->isEmpty()) {
            return null;
        }

        $totalSeconds = $tickets->sum(
            fn (QueueTicket $ticket): float => $ticket->checked_in_at->diffInSeconds($ticket->called_at)
        );

        return (int) round($totalSeconds / $tickets->count() / 60);
    }

    public function with(): array
    {
        return [
            'waiting' => $this->tickets()->where('status', TicketStatus::Waiting)->count(),
            'called' => $this->tickets()->where('status', TicketStatus::Called)->count(),
            'inService' => $this->tickets()->where('status', TicketStatus::InService)->count(),
            'completedToday' => $this->tickets()
                ->where('status', TicketStatus::Completed)
                ->whereDate('completed_at', today())
                ->count(),
            'averageWait' => $this->averageWaitMinutes(),
        ];
    }
}; ?>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <x-stat-card label="Waiting" :value="$waiting" icon="queue" />
    <x-stat-card label="Called" :value="$called" icon="bell" />
    <x-stat-card label="In service" :value="$inService" icon="clock" />
    <x-stat-card label="Completed today" :value="$completedToday" icon="check" />
    <x-stat-card label="Avg. wait" :value="$averageWait !== null ? $averageWait.' min' : '—'" icon="clock" />
</div>
