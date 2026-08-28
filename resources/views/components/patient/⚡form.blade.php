<?php

use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?int $patientId = null;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $date_of_birth = null;

    public ?string $email = null;

    public string $phone = '';

    public string $medical_record_number = '';

    public bool $active = true;

    public function mount(?int $patientId = null): void
    {
        $this->patientId = $patientId;

        if ($patientId !== null) {
            $patient = Patient::findOrFail($patientId);

            $this->first_name = $patient->first_name;
            $this->last_name = $patient->last_name;
            $this->date_of_birth = $patient->date_of_birth?->format('Y-m-d');
            $this->email = $patient->email;
            $this->phone = $patient->phone;
            $this->medical_record_number = $patient->medical_record_number;
            $this->active = $patient->active;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'email' => ['nullable', 'email'],
            'phone' => ['required', 'string'],
            'medical_record_number' => [
                'required',
                'string',
                Rule::unique('patients', 'medical_record_number')->ignore($this->patientId),
            ],
            'active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->authorize('manage-patients');

        $validated = $this->validate();

        if ($this->patientId !== null) {
            $patient = Patient::findOrFail($this->patientId);
            $patient->update($validated);
        } else {
            Patient::create($validated);
        }

        session()->flash('status', $this->patientId !== null
            ? 'Patient updated successfully.'
            : 'Patient created successfully.');

        $this->dispatch('patient-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('form-cancelled');
    }
}; ?>

<div>
    <form wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="block text-xs font-medium text-muted mb-1">First name</label>
                <input type="text" id="first_name" wire:model="first_name"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('first_name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="last_name" class="block text-xs font-medium text-muted mb-1">Last name</label>
                <input type="text" id="last_name" wire:model="last_name"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('last_name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date_of_birth" class="block text-xs font-medium text-muted mb-1">Date of birth</label>
                <input type="date" id="date_of_birth" wire:model="date_of_birth"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('date_of_birth') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-medium text-muted mb-1">Phone</label>
                <input type="text" id="phone" wire:model="phone"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('phone') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-muted mb-1">Email <span class="text-muted">(optional)</span></label>
                <input type="email" id="email" wire:model="email"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="medical_record_number" class="block text-xs font-medium text-muted mb-1">Medical record number</label>
                <input type="text" id="medical_record_number" wire:model="medical_record_number"
                       class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none" />
                @error('medical_record_number') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" wire:model="active"
                   class="rounded border-border text-primary focus-visible:ring-2 focus-visible:ring-primary/40" />
            Active
        </label>

        <div class="mt-6 flex items-center justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="cancel">Cancel</x-button>
            <x-button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-button>
        </div>
    </form>
</div>
