<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Services\Appointment\AppointmentScheduler;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?int $appointmentId = null;

    public ?int $patient_id = null;

    public ?int $healthcare_provider_id = null;

    public ?string $scheduled_at = null;

    public ?string $reason = null;

    public ?string $notes = null;

    public function mount(?int $appointmentId = null): void
    {
        $this->appointmentId = $appointmentId;

        if ($appointmentId !== null) {
            $appointment = Appointment::findOrFail($appointmentId);

            $this->patient_id = $appointment->patient_id;
            $this->healthcare_provider_id = $appointment->healthcare_provider_id;
            $this->scheduled_at = $appointment->scheduled_at?->format('Y-m-d\TH:i');
            $this->reason = $appointment->reason;
            $this->notes = $appointment->notes;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('active', true),
            ],
            'healthcare_provider_id' => [
                'required',
                'integer',
                Rule::exists('healthcare_providers', 'id')->where('active', true),
            ],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'patient_id.exists' => 'The selected patient is not an active patient.',
            'healthcare_provider_id.exists' => 'The selected provider is not an active provider.',
        ];
    }

    public function save(AppointmentScheduler $scheduler): void
    {
        $this->authorize('manage-appointments');

        $validated = $this->validate();

        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        // BR-03: a provider cannot have two active appointments within ±30 minutes.
        if ($scheduler->hasConflict((int) $validated['healthcare_provider_id'], $scheduledAt, $this->appointmentId)) {
            $this->addError('scheduled_at', 'This provider already has an appointment within 30 minutes of that time.');

            return;
        }

        $validated['scheduled_at'] = $scheduledAt;

        if ($this->appointmentId !== null) {
            $appointment = Appointment::findOrFail($this->appointmentId);
            $appointment->update($validated);
        } else {
            Appointment::create($validated + ['status' => AppointmentStatus::Scheduled]);
        }

        session()->flash('status', $this->appointmentId !== null
            ? 'Appointment updated successfully.'
            : 'Appointment scheduled successfully.');

        $this->dispatch('appointment-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('form-cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'patients' => Patient::query()->active()->orderBy('first_name')->orderBy('last_name')->get(),
            'providers' => HealthcareProvider::query()->active()->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <form wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="patient_id" class="block text-xs font-medium text-muted mb-1">Patient</label>
                <select id="patient_id" wire:model="patient_id"
                        class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                    <option value="">Select a patient…</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}">{{ $patient->full_name }} — {{ $patient->medical_record_number }}</option>
                    @endforeach
                </select>
                @error('patient_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="healthcare_provider_id" class="block text-xs font-medium text-muted mb-1">Provider</label>
                <select id="healthcare_provider_id" wire:model="healthcare_provider_id"
                        class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                    <option value="">Select a provider…</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }} — {{ $provider->specialty }}</option>
                    @endforeach
                </select>
                @error('healthcare_provider_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="scheduled_at" class="block text-xs font-medium text-muted mb-1">Scheduled at</label>
                <input type="datetime-local" id="scheduled_at" wire:model="scheduled_at"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('scheduled_at') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="reason" class="block text-xs font-medium text-muted mb-1">Reason <span class="text-muted">(optional)</span></label>
                <input type="text" id="reason" wire:model="reason"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('reason') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="notes" class="block text-xs font-medium text-muted mb-1">Notes <span class="text-muted">(optional)</span></label>
            <textarea id="notes" wire:model="notes" rows="3"
                      class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"></textarea>
            @error('notes') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
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
