<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

// Placeholder dashboard shipped with the foundation so the app is navigable and
// post-login redirects resolve. Phase 3 replaces this with the role-specific
// Reception and Provider dashboards.
new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'user' => auth()->user(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-ink leading-tight">Hello, {{ $user?->name }}!</h1>
        <p class="text-sm text-muted">Welcome to the Clinic Queue console.</p>
    </div>

    <x-card title="Getting started">
        <p class="text-sm text-muted">
            Use the sidebar to manage patients, appointments, providers and queues, or open the
            live Queue Board to call the next patient. Your role is
            <span class="font-medium text-ink">{{ $user?->role?->label() }}</span>.
        </p>
    </x-card>
</div>
