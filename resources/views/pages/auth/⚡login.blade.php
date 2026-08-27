<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
};
?>

<div class="w-full">
    <x-card>
        <div class="space-y-1 mb-6">
            <h1 class="text-2xl font-bold text-ink leading-tight">Welcome back</h1>
            <p class="text-sm text-muted">Sign in to the Clinic Queue console.</p>
        </div>

        <form wire:submit="login" class="space-y-4">
            <div class="space-y-1.5">
                <label for="email" class="text-xs font-medium text-muted">Email</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="username"
                    class="w-full rounded-lg border border-border bg-surface px-4 py-2 text-sm text-ink placeholder:text-muted-soft focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                    placeholder="you@clinic.test"
                />
                @error('email') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label for="password" class="text-xs font-medium text-muted">Password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-border bg-surface px-4 py-2 text-sm text-ink placeholder:text-muted-soft focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none"
                    placeholder="••••••••"
                />
                @error('password') <p class="text-xs font-medium text-danger">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" wire:model="remember" class="rounded border-border text-primary focus-visible:ring-primary/40" />
                Remember me
            </label>

            <x-button type="submit" variant="primary" class="w-full justify-center" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">Signing in…</span>
            </x-button>
        </form>
    </x-card>
</div>
