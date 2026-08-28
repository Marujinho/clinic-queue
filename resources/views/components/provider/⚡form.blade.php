<?php

use App\Models\HealthcareProvider;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?int $providerId = null;

    public string $name = '';

    public string $specialty = '';

    public string $license_number = '';

    public bool $active = true;

    /**
     * Load an existing provider into the form when a `providerId` is supplied
     * (edit mode); otherwise the form starts blank (create mode).
     */
    public function mount(?int $providerId = null): void
    {
        if ($providerId !== null) {
            $provider = HealthcareProvider::findOrFail($providerId);

            $this->providerId = $provider->id;
            $this->name = $provider->name;
            $this->specialty = $provider->specialty;
            $this->license_number = $provider->license_number;
            $this->active = $provider->active;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'specialty' => 'required|string',
            'license_number' => [
                'required',
                'string',
                Rule::unique('healthcare_providers', 'license_number')->ignore($this->providerId),
            ],
            'active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->authorize('manage-providers');

        $data = $this->validate();

        if ($this->providerId !== null) {
            HealthcareProvider::findOrFail($this->providerId)->update($data);
        } else {
            HealthcareProvider::create($data);
        }

        session()->flash('status', $this->providerId !== null
            ? 'Provider updated successfully.'
            : 'Provider created successfully.');

        $this->dispatch('provider-saved');
    }
};
?>

<div>
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-1.5">
            <label for="provider-name" class="text-xs font-medium text-muted">Name</label>
            <input
                id="provider-name"
                type="text"
                wire:model="name"
                class="w-full rounded-lg border border-border bg-surface px-4 py-2 text-sm text-ink placeholder:text-muted-soft focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                placeholder="Dr. Jane Doe"
            />
            @error('name') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label for="provider-specialty" class="text-xs font-medium text-muted">Specialty</label>
            <select
                id="provider-specialty"
                wire:model="specialty"
                class="w-full rounded-lg border border-border bg-surface px-4 py-2 text-sm text-ink focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
            >
                <option value="">Select a specialty</option>
                @foreach (['Cardiology', 'Pediatrics', 'General Practice', 'Dermatology', 'Orthopedics'] as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @error('specialty') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label for="provider-license" class="text-xs font-medium text-muted">License #</label>
            <input
                id="provider-license"
                type="text"
                wire:model="license_number"
                class="w-full rounded-lg border border-border bg-surface px-4 py-2 text-sm text-ink placeholder:text-muted-soft focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                placeholder="CRM-123456"
            />
            @error('license_number') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink">
            <input
                type="checkbox"
                wire:model="active"
                class="rounded border-border text-primary focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
            />
            <span>Active</span>
        </label>
        @error('active') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror

        <div class="mt-6 flex items-center justify-end gap-2">
            <x-button variant="secondary" type="button" wire:click="$dispatch('provider-form-cancelled')">Cancel</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $providerId ? 'Save changes' : 'Create provider' }}</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-button>
        </div>
    </form>
</div>
