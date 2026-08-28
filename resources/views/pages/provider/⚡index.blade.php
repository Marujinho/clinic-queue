<?php

use App\Models\HealthcareProvider;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public bool $showModal = false;

    public ?int $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('manage-providers');

        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(int $providerId): void
    {
        $this->authorize('manage-providers');

        $this->editingId = $providerId;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    /**
     * Toggle a provider's active flag. The business rule that an inactive
     * provider receives no new appointments is enforced by the appointment
     * domain; here we only flip the flag.
     */
    public function toggleActive(int $providerId): void
    {
        $this->authorize('manage-providers');

        $provider = HealthcareProvider::findOrFail($providerId);
        $provider->update(['active' => ! $provider->active]);

        session()->flash('status', $provider->active
            ? 'Provider activated.'
            : 'Provider deactivated.');
    }

    #[On('provider-saved')]
    public function onProviderSaved(): void
    {
        $this->closeModal();
    }

    #[On('provider-form-cancelled')]
    public function onProviderFormCancelled(): void
    {
        $this->closeModal();
    }

    /**
     * @return array{providers: LengthAwarePaginator<int, HealthcareProvider>}
     */
    public function with(): array
    {
        $providers = HealthcareProvider::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('specialty', 'like', $term);
                });
            })
            ->when($this->status === 'active', fn (Builder $query) => $query->where('active', true))
            ->when($this->status === 'inactive', fn (Builder $query) => $query->where('active', false))
            ->orderBy('name')
            ->paginate(10);

        return ['providers' => $providers];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-ink leading-tight">Providers</h1>
            <p class="text-sm text-muted">Manage the clinic's healthcare providers.</p>
        </div>
        <x-button wire:click="create">
            <x-icon name="plus" class="w-4 h-4" />
            New Provider
        </x-button>
    </div>

    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-3 flex items-center text-muted">
                    <x-icon name="search" class="w-4 h-4" />
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name or specialty…"
                    class="w-full rounded-lg border border-border bg-surface pl-9 pr-4 py-2 text-sm text-ink placeholder:text-muted-soft focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                />
            </div>

            <div class="flex items-center gap-2">
                @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setStatus('{{ $value }}')"
                        @class([
                            'inline-flex items-center gap-2 text-xs font-medium rounded-lg px-3 py-1.5 border',
                            'focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none',
                            'bg-primary text-surface border-primary' => $status === $value,
                            'bg-surface text-ink border-border hover:bg-hover-surface' => $status !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($providers->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto mb-3 flex w-10 h-10 items-center justify-center rounded-full bg-hover-surface text-muted">
                    <x-icon name="provider" class="w-5 h-5" />
                </div>
                <p class="text-sm font-medium text-ink">No providers found</p>
                <p class="text-sm text-muted">Try adjusting your search or add a new provider.</p>
            </div>
        @else
            <x-data-table>
                <x-slot:head>
                    <th class="text-left py-3">Name</th>
                    <th class="text-left py-3">Specialty</th>
                    <th class="text-left py-3">License #</th>
                    <th class="text-left py-3">Status</th>
                    <th class="text-right py-3">Actions</th>
                </x-slot:head>

                @foreach ($providers as $provider)
                    <tr class="border-b border-border hover:bg-hover-surface" wire:key="provider-{{ $provider->id }}">
                        <td class="py-3 text-ink">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$provider->name" size="sm" />
                                <span class="font-medium">{{ $provider->name }}</span>
                            </div>
                        </td>
                        <td class="py-3 text-ink">{{ $provider->specialty }}</td>
                        <td class="py-3 text-ink">{{ $provider->license_number }}</td>
                        <td class="py-3 text-ink">
                            @if ($provider->active)
                                <x-status-badge variant="success" label="Active" />
                            @else
                                <x-status-badge variant="neutral" label="Inactive" />
                            @endif
                        </td>
                        <td class="py-3 text-ink">
                            <div class="flex items-center justify-end gap-2">
                                <x-button variant="ghost" size="sm" wire:click="edit({{ $provider->id }})">Edit</x-button>
                                <x-button variant="secondary" size="sm" wire:click="toggleActive({{ $provider->id }})">
                                    {{ $provider->active ? 'Deactivate' : 'Activate' }}
                                </x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <div class="mt-4">
                {{ $providers->links() }}
            </div>
        @endif
    </x-card>

    @if ($showModal)
        <x-modal
            :title="$editingId ? 'Edit Provider' : 'New Provider'"
            wire:click.self="closeModal"
            x-on:close="$wire.closeModal()"
        >
            <livewire:provider.form :providerId="$editingId" :key="'provider-form-'.($editingId ?? 'new')" />
        </x-modal>
    @endif
</div>
