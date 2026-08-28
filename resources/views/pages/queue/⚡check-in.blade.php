<?php

use App\Enums\AppointmentStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Services\Queue\TicketNumberGenerator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $patientSearch = '';

    public ?int $patient_id = null;

    public ?int $queue_id = null;

    public ?int $appointment_id = null;

    public int $priority = TicketPriority::Normal->value;

    public function mount(): void
    {
        $this->authorize('check-in');
    }

    public function selectPatient(int $patientId): void
    {
        $this->patient_id = $patientId;
        $this->appointment_id = null;
        $this->resetErrorBag('patient_id');
    }

    public function clearPatient(): void
    {
        $this->patient_id = null;
        $this->appointment_id = null;
    }

    public function updatedQueueId(): void
    {
        // A queue without priority support always files tickets as Normal.
        if ($this->selectedQueue()?->priority_enabled !== true) {
            $this->priority = TicketPriority::Normal->value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'queue_id' => ['required', 'integer', Rule::exists('queues', 'id')->where('active', true)],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];
    }

    public function save(): void
    {
        $this->authorize('check-in');

        $validated = $this->validate();

        $patient = Patient::findOrFail($validated['patient_id']);
        $queue = Queue::findOrFail($validated['queue_id']);

        // BR-02: inactive patients cannot be checked in.
        if (! $patient->active) {
            $this->addError('patient_id', 'This patient is inactive and cannot be checked in.');

            return;
        }

        // BR-05: a patient may hold only one active ticket at a time.
        $hasActiveTicket = $patient->queueTickets()
            ->whereIn('status', [TicketStatus::Waiting, TicketStatus::Called, TicketStatus::InService])
            ->exists();

        if ($hasActiveTicket) {
            $this->addError('patient_id', 'This patient already has an active ticket in a queue.');

            return;
        }

        // The optional appointment must belong to this patient and still be open.
        if ($validated['appointment_id'] !== null) {
            $appointmentIsValid = $patient->appointments()
                ->whereKey($validated['appointment_id'])
                ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])
                ->exists();

            if (! $appointmentIsValid) {
                $this->addError('appointment_id', 'Select one of this patient\'s open appointments.');

                return;
            }
        }

        $priority = $queue->priority_enabled
            ? TicketPriority::from((int) $validated['priority'])
            : TicketPriority::Normal;

        $ticket = DB::transaction(fn (): QueueTicket => QueueTicket::create([
            'queue_id' => $queue->id,
            'patient_id' => $patient->id,
            'appointment_id' => $validated['appointment_id'],
            'ticket_number' => app(TicketNumberGenerator::class)->next($queue),
            'priority' => $priority,
            'status' => TicketStatus::Waiting,
            'checked_in_at' => now(),
        ]));

        session()->flash('status', sprintf(
            '%s checked into %s as ticket %s.',
            $patient->full_name,
            $queue->name,
            $ticket->ticket_number,
        ));

        $this->reset('patientSearch', 'patient_id', 'queue_id', 'appointment_id');
        $this->priority = TicketPriority::Normal->value;
    }

    private function selectedQueue(): ?Queue
    {
        return $this->queue_id !== null ? Queue::find($this->queue_id) : null;
    }

    public function with(): array
    {
        $search = trim($this->patientSearch);

        $results = collect();

        if ($this->patient_id === null && $search !== '') {
            $like = '%'.$search.'%';

            $results = Patient::query()
                ->active()
                ->where(function (Builder $query) use ($like): void {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('medical_record_number', 'like', $like)
                        ->orWhereRaw("(first_name || ' ' || last_name) like ?", [$like]);
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(8)
                ->get();
        }

        $selectedPatient = $this->patient_id !== null
            ? Patient::find($this->patient_id)
            : null;

        $appointments = $selectedPatient?->appointments()
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])
            ->orderBy('scheduled_at')
            ->get() ?? collect();

        return [
            'results' => $results,
            'selectedPatient' => $selectedPatient,
            'queues' => Queue::query()->active()->orderBy('name')->get(),
            'selectedQueue' => $this->selectedQueue(),
            'appointments' => $appointments,
            'priorities' => TicketPriority::cases(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-ink leading-tight">Patient Check-in</h1>
        <p class="text-sm text-muted">Check a patient into a queue and issue their ticket.</p>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-success-tint px-4 py-2 text-sm text-success">
            {{ session('status') }}
        </div>
    @endif

    <x-card class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="block text-xs font-medium text-muted mb-1">Patient</label>

                @if ($selectedPatient)
                    <div class="flex items-center justify-between rounded-lg border border-border bg-surface px-3 py-2">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$selectedPatient->full_name" size="sm" />
                            <div>
                                <p class="text-sm font-medium text-ink">{{ $selectedPatient->full_name }}</p>
                                <p class="text-xs text-muted">{{ $selectedPatient->medical_record_number }}</p>
                            </div>
                        </div>
                        <x-button type="button" size="sm" variant="ghost" wire:click="clearPatient">Change</x-button>
                    </div>
                @else
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="patientSearch"
                               placeholder="Search active patients by name or MRN…"
                               class="w-full rounded-lg border border-border bg-surface pl-9 pr-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                    </div>

                    @if ($results->isNotEmpty())
                        <ul class="mt-2 divide-y divide-border rounded-lg border border-border">
                            @foreach ($results as $patient)
                                <li wire:key="patient-result-{{ $patient->id }}">
                                    <button type="button" wire:click="selectPatient({{ $patient->id }})"
                                            class="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-hover-surface">
                                        <x-avatar :name="$patient->full_name" size="sm" />
                                        <span class="text-sm font-medium text-ink">{{ $patient->full_name }}</span>
                                        <span class="ml-auto text-xs text-muted">{{ $patient->medical_record_number }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @elseif (trim($patientSearch) !== '')
                        <p class="mt-2 text-xs text-muted">No active patients match "{{ $patientSearch }}".</p>
                    @endif
                @endif

                @error('patient_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="check-in-queue" class="block text-xs font-medium text-muted mb-1">Queue</label>
                <select id="check-in-queue" wire:model.live="queue_id"
                        class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                    <option value="">Select a queue…</option>
                    @foreach ($queues as $queue)
                        <option value="{{ $queue->id }}">{{ $queue->name }}</option>
                    @endforeach
                </select>
                @error('queue_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            @if ($selectedPatient && $appointments->isNotEmpty())
                <div>
                    <label for="check-in-appointment" class="block text-xs font-medium text-muted mb-1">
                        Appointment <span class="text-muted">(optional)</span>
                    </label>
                    <select id="check-in-appointment" wire:model="appointment_id"
                            class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                        <option value="">Walk-in (no appointment)</option>
                        @foreach ($appointments as $appointment)
                            <option value="{{ $appointment->id }}">
                                {{ $appointment->scheduled_at->format('M j, Y H:i') }} — {{ $appointment->reason }}
                            </option>
                        @endforeach
                    </select>
                    @error('appointment_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($selectedQueue?->priority_enabled)
                <div>
                    <label for="check-in-priority" class="block text-xs font-medium text-muted mb-1">Priority</label>
                    <select id="check-in-priority" wire:model="priority"
                            class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                        @foreach ($priorities as $priorityOption)
                            <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('priority') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="flex justify-end">
                <x-button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Check in</span>
                    <span wire:loading wire:target="save">Checking in…</span>
                </x-button>
            </div>
        </form>
    </x-card>
</div>
