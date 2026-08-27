<?php

use App\Enums\TicketStatus;
use App\Models\QueueTicket;
use App\Services\Queue\InvalidTicketTransition;
use App\Services\Queue\TicketStateMachine;
use Livewire\Component;

new class extends Component
{
    public ?string $errorMessage = null;

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
     * Delegate to the state machine, surfacing an illegal move (e.g. a
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
        $user = auth()->user();

        return [
            'current' => QueueTicket::query()
                ->with(['patient', 'queue'])
                ->whereIn('status', [TicketStatus::Called, TicketStatus::InService])
                ->orderByDesc('called_at')
                ->first(),
            'canStart' => $user?->can('start-service') ?? false,
            'canComplete' => $user?->can('complete-service') ?? false,
            'canCancel' => $user?->can('cancel-ticket') ?? false,
        ];
    }
}; ?>

<div class="space-y-6" wire:poll.5s>
    @if ($errorMessage)
        <div class="rounded-lg bg-danger-tint px-4 py-2 text-sm text-danger">
            {{ $errorMessage }}
        </div>
    @endif

    <livewire:queue.stats />

    <x-card title="Current patient">
        <x-slot:actions>
            <a href="{{ route('queue.board') }}" class="text-sm font-medium text-primary hover:underline">Open board</a>
        </x-slot:actions>

        @if ($current)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="text-3xl font-bold text-primary">{{ $current->ticket_number }}</span>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-ink">{{ $current->patient->full_name }}</p>
                        <p class="text-xs text-muted">{{ $current->queue->name }}</p>
                        <x-status-badge :variant="$current->status->badge()" :label="$current->status->label()" />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($current->status === \App\Enums\TicketStatus::Called)
                        @if ($canStart)
                            <x-button size="sm" variant="primary" wire:click="start({{ $current->id }})">Start</x-button>
                        @endif
                        @if ($canCancel)
                            <x-button size="sm" variant="ghost" wire:click="cancel({{ $current->id }})">Cancel</x-button>
                        @endif
                    @elseif ($current->status === \App\Enums\TicketStatus::InService)
                        @if ($canComplete)
                            <x-button size="sm" variant="primary" wire:click="complete({{ $current->id }})">Complete</x-button>
                        @endif
                    @endif
                </div>
            </div>
        @else
            <p class="text-sm text-muted">No patient has been called. Use the Queue Board to call the next patient.</p>
        @endif
    </x-card>
</div>
