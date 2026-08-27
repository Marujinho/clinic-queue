<?php

use App\Enums\AppointmentStatus;
use App\Enums\TicketStatus;
use App\Models\Appointment;
use App\Models\Queue;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $today = Appointment::query()->whereDate('scheduled_at', today());

        return [
            'appointmentsToday' => (clone $today)->count(),
            'completedToday' => (clone $today)->where('status', AppointmentStatus::Completed)->count(),
            'cancelledToday' => (clone $today)->where('status', AppointmentStatus::Cancelled)->count(),
            'queues' => Queue::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Queue $queue): array => [
                    'queue' => $queue,
                    'waiting' => $queue->waitingCount(),
                    'nowServing' => $queue->queueTickets()
                        ->with('patient')
                        ->whereIn('status', [TicketStatus::Called, TicketStatus::InService])
                        ->orderByDesc('called_at')
                        ->first(),
                ]),
        ];
    }
}; ?>

<div class="space-y-6" wire:poll.10s>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card :hero="true" label="Today's appointments" :value="$appointmentsToday" icon="calendar" />
        <x-stat-card label="Completed today" :value="$completedToday" icon="check" />
        <x-stat-card label="Cancelled today" :value="$cancelledToday" icon="x-mark" />
    </div>

    <livewire:queue.stats />

    <x-card title="Queues">
        <x-slot:actions>
            <a href="{{ route('queue.board') }}" class="text-sm font-medium text-primary hover:underline">Open board</a>
        </x-slot:actions>

        @if ($queues->isEmpty())
            <p class="text-sm text-muted">No active queues.</p>
        @else
            <x-data-table>
                <x-slot:head>
                    <th class="py-2 text-left text-xs font-medium text-muted">Queue</th>
                    <th class="py-2 text-left text-xs font-medium text-muted">Waiting</th>
                    <th class="py-2 text-left text-xs font-medium text-muted">Now serving</th>
                </x-slot:head>

                @foreach ($queues as $row)
                    <tr wire:key="queue-row-{{ $row['queue']->id }}" class="border-b border-border hover:bg-hover-surface">
                        <td class="py-3 text-ink font-medium">{{ $row['queue']->name }}</td>
                        <td class="py-3 text-ink">{{ $row['waiting'] }}</td>
                        <td class="py-3 text-ink">
                            @if ($row['nowServing'])
                                <span class="font-semibold">{{ $row['nowServing']->ticket_number }}</span>
                                <span class="text-muted">— {{ $row['nowServing']->patient->full_name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>
</div>
