<?php

use App\Models\Queue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->authorize('manage-queues');
    }

    public function create(): void
    {
        $this->authorize('manage-queues');

        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $queueId): void
    {
        $this->authorize('manage-queues');

        $this->editingId = $queueId;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    #[On('queue-saved')]
    public function onSaved(): void
    {
        $this->closeForm();
    }

    #[On('form-cancelled')]
    public function onCancelled(): void
    {
        $this->closeForm();
    }

    public function toggleActive(int $queueId): void
    {
        $this->authorize('manage-queues');

        $queue = Queue::findOrFail($queueId);
        $queue->update(['active' => ! $queue->active]);
    }

    public function with(): array
    {
        return [
            'queues' => Queue::query()->with('department')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-ink leading-tight">Queues</h1>
            <p class="text-sm text-muted">Configure the clinic's service queues.</p>
        </div>

        <x-button variant="primary" wire:click="create">
            <x-icon name="plus" class="w-4 h-4" />
            New Queue
        </x-button>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-success-tint px-4 py-2 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    <x-card>
        <x-data-table>
            <x-slot:head>
                <th class="text-left px-3 pb-3">Name</th>
                <th class="text-left px-3 pb-3">Description</th>
                <th class="text-left px-3 pb-3">Department</th>
                <th class="text-left px-3 pb-3">Priority</th>
                <th class="text-left px-3 pb-3">Status</th>
                <th class="text-left px-3 pb-3">Waiting</th>
                <th class="text-right px-3 pb-3">Actions</th>
            </x-slot:head>

            @forelse ($queues as $queue)
                <tr wire:key="queue-{{ $queue->id }}" class="border-b border-border hover:bg-hover-surface">
                    <td class="py-3 px-3 text-ink font-medium">{{ $queue->name }}</td>
                    <td class="py-3 px-3 text-muted">{{ $queue->description }}</td>
                    <td class="py-3 px-3 text-ink">{{ $queue->department?->name ?? '—' }}</td>
                    <td class="py-3 px-3 text-ink">
                        @if ($queue->priority_enabled)
                            <x-status-badge variant="info" label="Priority enabled" />
                        @else
                            <x-status-badge variant="neutral" label="Standard" />
                        @endif
                    </td>
                    <td class="py-3 px-3 text-ink">
                        @if ($queue->active)
                            <x-status-badge variant="success" label="Active" />
                        @else
                            <x-status-badge variant="neutral" label="Inactive" />
                        @endif
                    </td>
                    <td class="py-3 px-3 text-ink">{{ $queue->waitingCount() }}</td>
                    <td class="py-3 px-3 text-ink">
                        <div class="flex items-center justify-end gap-2">
                            <x-button size="sm" variant="secondary" wire:click="edit({{ $queue->id }})">Edit</x-button>
                            @if ($queue->active)
                                <x-button size="sm" variant="ghost" wire:click="toggleActive({{ $queue->id }})">Deactivate</x-button>
                            @else
                                <x-button size="sm" variant="ghost" wire:click="toggleActive({{ $queue->id }})">Activate</x-button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-8 px-3 text-center text-sm text-muted">
                        No queues configured yet.
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </x-card>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit Queue' : 'New Queue'"
                 max-width="xl"
                 wire:click.self="closeForm"
                 x-on:close="$wire.closeForm()">
            <livewire:queue.form :queue-id="$editingId" :key="'queue-form-'.($editingId ?? 'new')" />
        </x-modal>
    @endif
</div>
