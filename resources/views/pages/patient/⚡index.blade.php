<?php

use App\Models\Patient;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('manage-patients');

        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $patientId): void
    {
        $this->authorize('manage-patients');

        $this->editingId = $patientId;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    #[On('patient-saved')]
    public function onSaved(): void
    {
        $this->closeForm();
    }

    #[On('form-cancelled')]
    public function onCancelled(): void
    {
        $this->closeForm();
    }

    public function toggleActive(int $patientId): void
    {
        $this->authorize('manage-patients');

        $patient = Patient::findOrFail($patientId);
        $patient->update(['active' => ! $patient->active]);
    }

    public function with(): array
    {
        $search = trim($this->search);

        $patients = Patient::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('medical_record_number', 'like', $like)
                        ->orWhereRaw("(first_name || ' ' || last_name) like ?", [$like]);
                });
            })
            ->when($this->status === 'active', fn (Builder $query) => $query->where('active', true))
            ->when($this->status === 'inactive', fn (Builder $query) => $query->where('active', false))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(10);

        return [
            'patients' => $patients,
            'canManage' => auth()->user()?->can('manage-patients') ?? false,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-ink leading-tight">Patients</h1>
            <p class="text-sm text-muted">Search, register and manage patient records.</p>
        </div>

        @if ($canManage)
            <x-button variant="primary" wire:click="create">
                <x-icon name="plus" class="w-4 h-4" />
                New Patient
            </x-button>
        @endif
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-success-tint px-4 py-2 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    <x-card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">
                    <x-icon name="search" class="w-4 h-4" />
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search by name or MRN…"
                       class="w-full rounded-lg border border-border bg-surface pl-9 pr-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
            </div>

            <select wire:model.live="status"
                    class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <x-data-table>
            <x-slot:head>
                <th class="text-left px-3 pb-3">Name</th>
                <th class="text-left px-3 pb-3">MRN</th>
                <th class="text-left px-3 pb-3">Phone</th>
                <th class="text-left px-3 pb-3">Date of birth</th>
                <th class="text-left px-3 pb-3">Status</th>
                @if ($canManage)
                    <th class="text-right px-3 pb-3">Actions</th>
                @endif
            </x-slot:head>

            @forelse ($patients as $patient)
                <tr wire:key="patient-{{ $patient->id }}" class="border-b border-border hover:bg-hover-surface">
                    <td class="py-3 px-3 text-ink">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$patient->full_name" size="sm" />
                            <span class="font-medium">{{ $patient->full_name }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-3 text-ink">{{ $patient->medical_record_number }}</td>
                    <td class="py-3 px-3 text-ink">{{ $patient->phone }}</td>
                    <td class="py-3 px-3 text-ink">{{ $patient->date_of_birth?->format('M j, Y') }}</td>
                    <td class="py-3 px-3 text-ink">
                        @if ($patient->active)
                            <x-status-badge variant="success" label="Active" />
                        @else
                            <x-status-badge variant="neutral" label="Inactive" />
                        @endif
                    </td>
                    @if ($canManage)
                        <td class="py-3 px-3 text-ink">
                            <div class="flex items-center justify-end gap-2">
                                <x-button size="sm" variant="secondary" wire:click="edit({{ $patient->id }})">Edit</x-button>
                                @if ($patient->active)
                                    <x-button size="sm" variant="ghost" wire:click="toggleActive({{ $patient->id }})">Deactivate</x-button>
                                @else
                                    <x-button size="sm" variant="ghost" wire:click="toggleActive({{ $patient->id }})">Activate</x-button>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 6 : 5 }}" class="py-8 px-3 text-center text-sm text-muted">
                        No patients found.
                    </td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-4">
            {{ $patients->links() }}
        </div>
    </x-card>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit Patient' : 'New Patient'"
                 max-width="2xl"
                 wire:click.self="closeForm"
                 x-on:close="$wire.closeForm()">
            <livewire:patient.form :patient-id="$editingId" :key="'patient-form-'.($editingId ?? 'new')" />
        </x-modal>
    @endif
</div>
