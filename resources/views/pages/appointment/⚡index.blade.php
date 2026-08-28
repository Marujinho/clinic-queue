<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Services\Appointment\AppointmentTransitioner;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $status = 'all';

    public string $provider = 'all';

    public string $date = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProvider(): void
    {
        $this->resetPage();
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('manage-appointments');

        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $appointmentId): void
    {
        $this->authorize('manage-appointments');

        $this->editingId = $appointmentId;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    #[On('appointment-saved')]
    public function onSaved(): void
    {
        $this->closeForm();
    }

    #[On('form-cancelled')]
    public function onCancelled(): void
    {
        $this->closeForm();
    }

    public function changeStatus(int $appointmentId, string $status): void
    {
        $this->authorize('manage-appointments');

        $appointment = Appointment::findOrFail($appointmentId);
        $to = AppointmentStatus::from($status);

        try {
            app(AppointmentTransitioner::class)->transition($appointment, $to);

            session()->flash('status', "Appointment marked as {$to->label()}.");
        } catch (\InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function with(): array
    {
        $appointments = Appointment::query()
            ->with(['patient', 'provider'])
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->provider !== 'all', fn (Builder $query) => $query->where('healthcare_provider_id', $this->provider))
            ->when($this->date !== '', fn (Builder $query) => $query->whereDate('scheduled_at', $this->date))
            ->orderBy('scheduled_at')
            ->paginate(10);

        return [
            'appointments' => $appointments,
            'providers' => HealthcareProvider::query()->orderBy('name')->get(),
            'statuses' => AppointmentStatus::cases(),
            'canManage' => auth()->user()?->can('manage-appointments') ?? false,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-ink leading-tight">Appointments</h1>
            <p class="text-sm text-muted">Schedule and track patient appointments.</p>
        </div>

        @if ($canManage)
            <x-button variant="primary" wire:click="create">
                <x-icon name="plus" class="w-4 h-4" />
                New Appointment
            </x-button>
        @endif
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-success-tint px-4 py-2 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg bg-danger-tint px-4 py-2 text-sm text-danger">
            {{ session('error') }}
        </div>
    @endif

    <x-card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <select wire:model.live="status"
                    class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                <option value="all">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="provider"
                    class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                <option value="all">All providers</option>
                @foreach ($providers as $providerOption)
                    <option value="{{ $providerOption->id }}">{{ $providerOption->name }}</option>
                @endforeach
            </select>

            <input type="date" wire:model.live="date"
                   class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
        </div>

        <x-data-table>
            <x-slot:head>
                <th class="text-left px-3 pb-3">Patient</th>
                <th class="text-left px-3 pb-3">Provider</th>
                <th class="text-left px-3 pb-3">Scheduled at</th>
                <th class="text-left px-3 pb-3">Status</th>
                <th class="text-left px-3 pb-3">Reason</th>
                @if ($canManage)
                    <th class="text-right px-3 pb-3">Actions</th>
                @endif
            </x-slot:head>

            @forelse ($appointments as $appointment)
                <tr wire:key="appointment-{{ $appointment->id }}" class="border-b border-border hover:bg-hover-surface">
                    <td class="py-3 px-3 text-ink">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$appointment->patient->full_name" size="sm" />
                            <span class="font-medium">{{ $appointment->patient->full_name }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-3 text-ink">{{ $appointment->provider->name }}</td>
                    <td class="py-3 px-3 text-ink">{{ $appointment->scheduled_at?->format('M j, Y H:i') }}</td>
                    <td class="py-3 px-3 text-ink">
                        <x-status-badge :variant="$appointment->status->badge()" :label="$appointment->status->label()" />
                    </td>
                    <td class="py-3 px-3 text-ink">{{ $appointment->reason }}</td>
                    @if ($canManage)
                        <td class="py-3 px-3 text-ink">
                            <div class="flex items-center justify-end gap-2">
                                @if ($appointment->status === App\Enums\AppointmentStatus::Scheduled)
                                    <x-button size="sm" variant="secondary" wire:click="changeStatus({{ $appointment->id }}, 'confirmed')">Confirm</x-button>
                                @endif
                                @if ($appointment->status === App\Enums\AppointmentStatus::Confirmed)
                                    <x-button size="sm" variant="secondary" wire:click="changeStatus({{ $appointment->id }}, 'in_progress')">Start</x-button>
                                @endif
                                @if ($appointment->status === App\Enums\AppointmentStatus::InProgress)
                                    <x-button size="sm" variant="secondary" wire:click="changeStatus({{ $appointment->id }}, 'completed')">Complete</x-button>
                                @endif
                                @if (in_array($appointment->status, [App\Enums\AppointmentStatus::Scheduled, App\Enums\AppointmentStatus::Confirmed], true))
                                    <x-button size="sm" variant="secondary" wire:click="edit({{ $appointment->id }})">Edit</x-button>
                                    <x-button size="sm" variant="ghost" wire:click="changeStatus({{ $appointment->id }}, 'cancelled')">Cancel</x-button>
                                    <x-button size="sm" variant="ghost" wire:click="changeStatus({{ $appointment->id }}, 'no_show')">No-show</x-button>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 6 : 5 }}" class="py-8 px-3 text-center text-sm text-muted">
                        No appointments found.
                    </td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-4">
            {{ $appointments->links() }}
        </div>
    </x-card>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit Appointment' : 'New Appointment'"
                 max-width="2xl"
                 wire:click.self="closeForm"
                 x-on:close="$wire.closeForm()">
            <livewire:appointment.form :appointment-id="$editingId" :key="'appointment-form-'.($editingId ?? 'new')" />
        </x-modal>
    @endif
</div>
