<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Clinic Queue' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-background text-ink font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-60 shrink-0 bg-surface border-r border-border sticky top-0 h-screen flex flex-col">
            {{-- Logo --}}
            <div class="h-16 flex items-center gap-2 px-6 border-b border-border">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary"></span>
                <span class="font-semibold text-ink">Clinic Queue</span>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-6">
                {{-- Overview --}}
                <div class="space-y-1">
                    <p class="px-3 mb-1 text-xs font-medium uppercase tracking-wide text-muted-soft">Overview</p>
                    <x-nav-item href="/dashboard" icon="dashboard"
                        :active="request()->is('/') || request()->is('dashboard*')">Dashboard</x-nav-item>
                    <x-nav-item href="/queue-board" icon="board"
                        :active="request()->is('queue-board*')">Queue Board</x-nav-item>
                    @can('check-in')
                        <x-nav-item href="/check-in" icon="plus"
                            :active="request()->is('check-in*')">Check-in</x-nav-item>
                    @endcan
                </div>

                {{-- Records --}}
                <div class="space-y-1">
                    <p class="px-3 mb-1 text-xs font-medium uppercase tracking-wide text-muted-soft">Records</p>
                    <x-nav-item href="/patients" icon="patients"
                        :active="request()->is('patients*')">Patients</x-nav-item>
                    <x-nav-item href="/appointments" icon="calendar"
                        :active="request()->is('appointments*')">Appointments</x-nav-item>
                </div>

                {{-- Management --}}
                @canany(['manage-providers', 'manage-queues'])
                    <div class="space-y-1">
                        <p class="px-3 mb-1 text-xs font-medium uppercase tracking-wide text-muted-soft">Management</p>
                        @can('manage-providers')
                            <x-nav-item href="/providers" icon="provider"
                                :active="request()->is('providers*')">Providers</x-nav-item>
                        @endcan
                        @can('manage-queues')
                            <x-nav-item href="/queues" icon="queue"
                                :active="request()->is('queues*')">Queues</x-nav-item>
                        @endcan
                    </div>
                @endcanany
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <header class="h-16 shrink-0 border-b border-border bg-surface flex items-center justify-between px-6 gap-4">
                {{-- Search --}}
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <div class="flex items-center gap-2 w-full bg-hover-surface border border-border rounded-lg px-3 py-2">
                        <x-icon name="search" class="w-4 h-4 text-muted" />
                        <input type="text" placeholder="Search…"
                            class="w-full bg-transparent text-sm text-ink placeholder:text-muted-soft focus:outline-none" />
                    </div>
                </div>

                {{-- Right side --}}
                <div class="flex items-center gap-4">
                    <button type="button" aria-label="Notifications"
                        class="text-muted hover:text-ink hover:bg-hover-surface rounded-lg p-2 focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                        <x-icon name="bell" class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-3">
                        <x-avatar :name="auth()->user()?->name ?? 'Guest'" size="md" />
                        <div class="leading-tight">
                            <p class="text-sm font-medium text-ink">{{ auth()->user()?->name ?? 'Guest' }}</p>
                            <p class="text-xs text-muted">{{ auth()->user()?->role ?? 'Staff' }}</p>
                        </div>
                    </div>

                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" aria-label="Log out"
                            class="text-muted hover:text-ink hover:bg-hover-surface rounded-lg p-2 focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:outline-none">
                            <x-icon name="logout" class="w-5 h-5" />
                        </button>
                    </form>
                </div>
            </header>

            {{-- Flash messages --}}
            @if (session('status'))
                <div class="mx-6 mt-4 rounded-lg bg-success-tint text-success text-sm font-medium px-4 py-3 flex items-center justify-between gap-3">
                    <span>{{ session('status') }}</span>
                    <x-icon name="check" class="w-4 h-4 shrink-0" />
                </div>
            @endif

            @if (session('error'))
                <div class="mx-6 mt-4 rounded-lg bg-danger-tint text-danger text-sm font-medium px-4 py-3 flex items-center justify-between gap-3">
                    <span>{{ session('error') }}</span>
                    <x-icon name="x-mark" class="w-4 h-4 shrink-0" />
                </div>
            @endif

            {{-- Content --}}
            <main class="p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
