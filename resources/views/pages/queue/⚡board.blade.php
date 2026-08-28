<?php

use App\Enums\TicketStatus;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Services\Queue\InvalidTicketTransition;
use App\Services\Queue\TicketStateMachine;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $errorMessage = null;

    public function callNext(int $queueId): void
    {
        $this->authorize('call-patient');

        $this->errorMessage = null;

        $queue = Queue::findOrFail($queueId);

        app(TicketStateMachine::class)->callNext($queue);
    }

    public function start(int $ticketId): void
    {
        $this->authorize('start-service');

        $this->applyTransition($ticketId, 'start');
    }

    public function complete(int $ticketId): void
    {
        $this->authorize('complete-service');

        $this->applyTransition($ticketId, 'complete');
    }

    public function cancel(int $ticketId): void
    {
        $this->authorize('cancel-ticket');

        $this->applyTransition($ticketId, 'cancel');
    }

    /**
     * Run one state-machine transition, surfacing an illegal move (e.g. a
     * concurrent update between polls) as a message instead of a server error.
     */
    private function applyTransition(int $ticketId, string $method): void
    {
        $this->errorMessage = null;

        $ticket = QueueTicket::findOrFail($ticketId);

        try {
            app(TicketStateMachine::class)->{$method}($ticket);
        } catch (InvalidTicketTransition $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function with(): array
    {
        $queues = Queue::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Queue $queue): array => [
                'queue' => $queue,
                'nowServing' => $queue->queueTickets()
                    ->with('patient')
                    ->whereIn('status', [TicketStatus::Called, TicketStatus::InService])
                    ->orderByDesc('called_at')
                    ->first(),
                'waiting' => $queue->queueTickets()
                    ->with('patient')
                    ->where('status', TicketStatus::Waiting)
                    ->orderByDesc('priority')
                    ->orderBy('checked_in_at')
                    ->orderBy('id')
                    ->get(),
            ]);

        $user = auth()->user();

        return [
            'boards' => $queues,
            'canCall' => $user?->can('call-patient') ?? false,
            'canStart' => $user?->can('start-service') ?? false,
            'canComplete' => $user?->can('complete-service') ?? false,
            'canCancel' => $user?->can('cancel-ticket') ?? false,
        ];
    }
}; ?>

<div class="space-y-6" wire:poll.5s>
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-ink leading-tight">Queue Board</h1>
        <p class="text-sm text-muted">Live view of every active queue. Refreshes automatically.</p>
    </div>

    @if ($errorMessage)
        <div class="rounded-lg bg-danger-tint px-4 py-2 text-sm text-danger">
            {{ $errorMessage }}
        </div>
    @endif

    <livewire:queue.stats />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @forelse ($boards as $board)
            <x-card wire:key="board-{{ $board['queue']->id }}">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">{{ $board['queue']->name }}</h2>
                        <p class="text-xs text-muted">{{ $board['waiting']->count() }} waiting</p>
                    </div>
                    @if ($canCall)
                        <x-button size="sm" variant="primary"
                                  wire:click="callNext({{ $board['queue']->id }})"
                                  :disabled="$board['waiting']->isEmpty()">
                            Call Next
                        </x-button>
                    @endif
                </div>

                <div class="mb-4 rounded-xl bg-primary-tint px-4 py-3">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide">Now serving</p>
                    @if ($board['nowServing'])
                        @php($serving = $board['nowServing'])
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl font-bold text-primary">{{ $serving->ticket_number }}</span>
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ $serving->patient->full_name }}</p>
                                    <x-status-badge :variant="$serving->status->badge()" :label="$serving->status->label()" />
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($serving->status === \App\Enums\TicketStatus::Called)
                                    @if ($canStart)
                                        <x-button size="sm" variant="secondary" wire:click="start({{ $serving->id }})">Start</x-button>
                                    @endif
                                    @if ($canCancel)
                                        <x-button size="sm" variant="ghost" wire:click="cancel({{ $serving->id }})">Cancel</x-button>
                                    @endif
                                @elseif ($serving->status === \App\Enums\TicketStatus::InService)
                                    @if ($canComplete)
                                        <x-button size="sm" variant="secondary" wire:click="complete({{ $serving->id }})">Complete</x-button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-muted">No patient being served.</p>
                    @endif
                </div>

                <div>
                    <p class="mb-2 text-xs font-medium text-muted uppercase tracking-wide">Waiting</p>
                    @if ($board['waiting']->isEmpty())
                        <p class="text-sm text-muted">The queue is empty.</p>
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($board['waiting'] as $ticket)
                                <li wire:key="ticket-{{ $ticket->id }}" class="flex items-center gap-3 py-2">
                                    <span class="w-14 text-sm font-semibold text-ink">{{ $ticket->ticket_number }}</span>
                                    <span class="text-sm text-ink">{{ $ticket->patient->full_name }}</span>
                                    <x-status-badge :variant="$ticket->priority->badge()" :label="$ticket->priority->label()" />
                                    <span class="ml-auto text-xs text-muted">{{ $ticket->checked_in_at?->format('H:i') }}</span>
                                    @if ($canCancel)
                                        <x-button size="sm" variant="ghost" wire:click="cancel({{ $ticket->id }})">Cancel</x-button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </x-card>
        @empty
            <x-card>
                <p class="text-sm text-muted">No active queues.</p>
            </x-card>
        @endforelse
    </div>
</div>
