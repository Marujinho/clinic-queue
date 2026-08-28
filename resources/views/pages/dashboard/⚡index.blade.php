<?php

use App\Enums\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Role dispatcher: providers get the "current patient" console, admins and
// receptionists get the reception overview. Keeping a single /dashboard route
// preserves the post-login redirect and the sidebar nav item.
new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array
    {
        $user = auth()->user();

        return [
            'user' => $user,
            'isProviderDashboard' => $user?->role === Role::Provider,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-ink leading-tight">Hello, {{ $user?->name }}!</h1>
        <p class="text-sm text-muted">
            {{ $isProviderDashboard ? 'Your patient console for today.' : 'Today at a glance across appointments and queues.' }}
        </p>
    </div>

    @if ($isProviderDashboard)
        <livewire:dashboard.provider />
    @else
        <livewire:dashboard.reception />
    @endif
</div>
