<?php

use App\Models\Department;
use App\Models\Queue;
use Livewire\Component;

new class extends Component
{
    public ?int $queueId = null;

    public string $name = '';

    public ?string $description = null;

    public bool $priority_enabled = false;

    public bool $active = true;

    public ?int $department_id = null;

    public function mount(?int $queueId = null): void
    {
        $this->queueId = $queueId;

        if ($queueId !== null) {
            $queue = Queue::findOrFail($queueId);

            $this->name = $queue->name;
            $this->description = $queue->description;
            $this->priority_enabled = $queue->priority_enabled;
            $this->active = $queue->active;
            $this->department_id = $queue->department_id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'priority_enabled' => ['boolean'],
            'active' => ['boolean'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }

    public function save(): void
    {
        $this->authorize('manage-queues');

        $validated = $this->validate();

        if ($this->queueId !== null) {
            Queue::findOrFail($this->queueId)->update($validated);
        } else {
            Queue::create($validated);
        }

        session()->flash('status', $this->queueId !== null
            ? 'Queue updated successfully.'
            : 'Queue created successfully.');

        $this->dispatch('queue-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('form-cancelled');
    }

    public function with(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div>
    <form wire:submit="save" class="space-y-4">
        <div>
            <label for="queue-name" class="block text-xs font-medium text-muted mb-1">Name</label>
            <input type="text" id="queue-name" wire:model="name"
                   class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
            @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="queue-description" class="block text-xs font-medium text-muted mb-1">Description <span class="text-muted">(optional)</span></label>
            <textarea id="queue-description" wire:model="description" rows="2"
                      class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"></textarea>
            @error('description') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="queue-department" class="block text-xs font-medium text-muted mb-1">Department <span class="text-muted">(optional)</span></label>
            <select id="queue-department" wire:model="department_id"
                    class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                <option value="">No department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" wire:model="priority_enabled"
                       class="rounded border-border text-primary focus-visible:ring-2 focus-visible:ring-primary/40" />
                Priority enabled
            </label>

            <label class="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" wire:model="active"
                       class="rounded border-border text-primary focus-visible:ring-2 focus-visible:ring-primary/40" />
                Active
            </label>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="cancel">Cancel</x-button>
            <x-button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-button>
        </div>
    </form>
</div>
